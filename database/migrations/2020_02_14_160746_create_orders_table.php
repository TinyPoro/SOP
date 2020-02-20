<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id')->unique();
            $table->string('order_number')->unique();
            $table->string('customer_name')->index();
            $table->string('customer_email')->index();
            $table->text('link_to_gd')->nullable();
            $table->date('order_date')->index();
            $table->string('total_price');
            $table->string('shipping_method')->index();
            $table->text('internal_remark')->nullable();
            $table->integer('status')->default(\App\Models\Order::DESIGNING_STATUS)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
