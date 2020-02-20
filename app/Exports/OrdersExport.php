<?php
/**
 * Created by PhpStorm.
 * User: TinyPoro
 * Date: 2/19/20
 * Time: 9:48 PM
 */

namespace App\Exports;


use App\Models\Order;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;

class OrdersExport implements FromArray
{
    public function array(): array
    {
        $data = [];

        $orders = Order::whereDate('order_date', Carbon::now())->get();

        foreach ($orders as $order) {
            $data[] = [
                $order->order_date,
                $order->order_number,
                $order->customer_name,
                $order->customer_email,
                $order->link_to_gd,
                $order->total_price,
            ];
        }

        return $data;
    }
}