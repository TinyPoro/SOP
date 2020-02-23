<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ShopifyHelpers;
use App\Http\Controllers\Controller;
use App\Jobs\CreateOrderJob;
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
            $orderData = $request->all();

            Queue::push(new CreateOrderJob($orderData));

            $result["success"] = true;

            return response()->json($result, 200);

        } catch (\Exception $e){
            Log::error($e->getMessage());

            $result["message"] = $e->getMessage();

            return response()->json($result, 500);
        }
    }
}
