<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Order extends Model
{
    CONST SENT_TO_FC_STATUS = 0;
    CONST DESIGNING_STATUS = 1;
    CONST DONE_STATUS = 2;
    CONST SHIPPED_STATUS = 3;
    CONST PREVIEW_STATUS = 4;
    CONST TRACKING_SENT_STATUS = 5;
    CONST NIR_STATUS = 6;
    CONST FIXING_STATUS = 7;
    CONST REFUND_STATUS = 8;

    const DATA_STATUS_ARRAY = [
        Order::SENT_TO_FC_STATUS => 'Sent to FC ',
        Order::DESIGNING_STATUS => 'Designing',
        Order::DONE_STATUS => 'Done',
        Order::SHIPPED_STATUS => 'Shipped',
        Order::PREVIEW_STATUS => 'Preview',
        Order::TRACKING_SENT_STATUS => 'Tracking Sent ',
        Order::NIR_STATUS => 'NIR',
        Order::FIXING_STATUS => 'Fixing',
        Order::REFUND_STATUS => 'Refund',
    ];

    use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'orders';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
     protected $fillable = [
         'order_number',
         'customer_name',
         'customer_email',
         'link_to_order',
         'link_to_gd',
         'order_date',
         'status',
     ];
    // protected $hidden = [];
     protected $dates = ['order_date'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public function getDateString()
    {
        return $this->order_date->format('M d');
    }

    public function getNumberOfItem()
    {
        $numberOfItem = 0;

        foreach ($this->items as $item) {
            $numberOfItem += $item->number_of_item;
        }

        return $numberOfItem;
    }

    public function getItemName()
    {
        $itemName = "";

        foreach ($this->items as $item) {
            $itemName .= $item->item_name . "\n";
        }

        return $itemName;
    }

    public function getStatusText()
    {
        $status = $this->status;

        return Arr::get(self::DATA_STATUS_ARRAY, $status, 'N/A');
    }

    public function getLinkToOrder()
    {
        return "https://noble-pawtrait.myshopify.com/admin/orders/$this->order_number";
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany('App\Models\Item');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
