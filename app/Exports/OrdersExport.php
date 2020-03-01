<?php
/**
 * Created by PhpStorm.
 * User: TinyPoro
 * Date: 2/19/20
 * Time: 9:48 PM
 */

namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromArray;

class OrdersExport implements FromArray
{
    private $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function array(): array
    {
        return $this->orders;
    }
}