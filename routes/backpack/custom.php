<?php

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => ['web', config('backpack.base.middleware_key', 'admin'), 'role:Admin|Staff'],
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('order', 'OrderCrudController');
    Route::crud('shopifyimage', 'ShopifyImageCrudController');
    Route::crud('item', 'ItemCrudController');

    Route::get('/order/export', 'OrderCrudController@export');
    Route::put('/order/status/{id}', 'OrderCrudController@updateStatus')->name('order.update_status');
}); // this should be the absolute last line of this file