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
use Illuminate\Support\Facades\Storage;

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
            $orderNumber = $request->get("order_number");
            $date = $request->get("created_at");
            $date = Carbon::createFromTimeString($date);

            $customer = $request->get("customer");
            $customerName = Arr::get($customer, 'first_name', '') . " " . Arr::get($customer, 'last_name', '');
            $customerEmail = Arr::get($customer, 'email', '');
            $linkToOrder = $request->get("order_status_url");

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

            Queue::push(new CreateOrderJob(
                $orderNumber,
                $date,
                $customerName,
                $customerEmail,
                $linkToOrder,
                $items
            ));

            $result["success"] = true;

            return response()->json($result);

        } catch (\Exception $e){
            Log::error($e->getMessage());

            $result["message"] = $e->getMessage();

            return response()->json($result);
        }
    }
}
