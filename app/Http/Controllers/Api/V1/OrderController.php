<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ShopifyHelpers;
use App\Http\Controllers\Controller;
use App\Jobs\CreateOrderJob;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;

class OrderController extends Controller
{
    /**
     * ShopifyHelpers
     *
     * @var ShopifyHelpers
     */
    private $shopifyHelpers;

    /**
     * Create a new command instance.
     *
     * @param ShopifyHelpers $shopifyHelpers
     * @return void
     */
    public function __construct(ShopifyHelpers $shopifyHelpers)
    {
        $this->shopifyHelpers = $shopifyHelpers;
    }

    public function create(Request $request){
        $result = [
            "success" => false,
            "message" => ""
        ];

        Log::info(print_r($request->all(), true));

        try {
            $orderId = $request->get("id");
            $orderNumber = $request->get("order_number");
            $date = $request->get("created_at");
            $date = Carbon::createFromTimeString($date);

            $customer = $request->get("customer");
            $customerName = Arr::get($customer, 'first_name', '') . " " . Arr::get($customer, 'last_name', '');
            $customerEmail = Arr::get($customer, 'email', '');

            $totalPrice = $request->get('total_price', '');

            $lineItems = $request->get("line_items");

            $items = [];
            foreach ($lineItems as $lineItem) {
                $itemTitle = Arr::get($lineItem, 'title', '');
                $numberOfItem = Arr::get($lineItem, 'quantity', 0);
                $itemVariantTitle = Arr::get($lineItem, 'variant_title', '');

                $properties = Arr::get($lineItem, 'properties', []);

                $images = [];
                $notes = [];

                foreach ($properties as $property) {
                    $name = Arr::get($property, 'name', null);
                    $value = Arr::get($property, 'value', null);

                    if($name) {
                        if(preg_match("/^Uploaded image/", $name)) {
                            $imageSrc = $this->shopifyHelpers->getImageSrcFromCdnUrl($value);

                            $images[] = $imageSrc;
                        }

                        if(preg_match("/^Note/", $name)) {
                            $notes[] = $value;
                        }
                    }
                }

                $items[] = [
                    'itemTitle' => $itemTitle,
                    'numberOfItem' => $numberOfItem,
                    'itemVariantTitle' => $itemVariantTitle,
                    'images' => $images,
                    'notes' => $notes,
                ];
            }

            $shippingMethods = [];
            $shippingLines = $request->get("shipping_lines");
            foreach ($shippingLines as $shippingLine) {
                $title = Arr::get($shippingLine, 'title', '');

                $shippingMethods[] = $title;
            }

            $shippingAddress = $request->get("shipping_address");
            $shippingName = Arr::get($shippingAddress, 'name', '');
            $shippingAddress1 = Arr::get($shippingAddress, 'address1', '');
            $shippingCity = Arr::get($shippingAddress, 'city', '');
            $shippingProvinceCode = Arr::get($shippingAddress, 'province_code', '');
            $shippingZip = Arr::get($shippingAddress, 'zip', '');
            $shippingCountry = Arr::get($shippingAddress, 'country', '');
            $shippingPhone = Arr::get($shippingAddress, 'phone', '');

            $shippingAddressText = "$shippingName\n$shippingAddress1\n$shippingCity $shippingProvinceCode $shippingZip\n$shippingCountry\n$shippingPhone";


            Queue::push(new CreateOrderJob(
                $orderId,
                $orderNumber,
                $date,
                $customerName,
                $customerEmail,
                $totalPrice,
                $shippingMethods,
                $items,
                $shippingAddressText
            ));

            $result["success"] = true;

            return response()->json($result, 200);

        } catch (\Exception $e){
            Log::error($e->getMessage());

            $result["message"] = $e->getMessage();

            return response()->json($result, 500);
        }
    }
}
