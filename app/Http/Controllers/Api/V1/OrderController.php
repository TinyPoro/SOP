<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\CreateOrderJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;

class OrderController extends Controller
{
    public function create(Request $request){
        $result = [
            "success" => false,
            "message" => ""
        ];

        Log::info(print_r($request->all(), true));

        try {
            $name = $request->get('name');
            $product = $request->get('product');
            $type = $request->get('type');
            $size = $request->get('size');
            $images = $request->get('images');
            $orderDate = $request->get('order_date');

            Queue::push(new CreateOrderJob(
                $name,
                $product,
                $type,
                $size,
                $images,
                $orderDate
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
