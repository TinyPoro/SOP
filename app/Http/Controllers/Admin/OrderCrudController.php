<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OrderRequest;
use App\Models\Order;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class OrderCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OrderCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        $this->crud->setModel('App\Models\Order');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/order');
        $this->crud->setEntityNameStrings('order', 'orders');

        $this->crud->operation(['list', 'show'], function() {
            $this->setupListOperation();
        });
    }

    protected function setupListOperation()
    {
        $this->crud->addColumn([
            'name' => "order_number",
            "type" => "text",
            "title" => "Order #"
        ]);

        $this->crud->addColumn([
            'name' => "date",
            "type" => "model_function_custom",
            "function_name" => "getDateString",
        ]);

        $this->crud->addColumn([
            'name' => "customer_name",
            "type" => "text",
            "title" => "Customer Name"
        ]);

        $this->crud->addColumn([
            'name' => "link_to_order",
            "type" => "text",
            "title" => "Link To Order"
        ]);

        $this->crud->addColumn([
            'name' => "link_to_gd",
            "type" => "text",
            "title" => "Link To gdrive"
        ]);

        $this->crud->addColumn([
            'name' => "# of item",
            "type" => "model_function_custom",
            "function_name" => "getNumberOfItem",
        ]);

        $this->crud->addColumn([
            'name' => "Item name",
            "type" => "model_function_custom",
            "function_name" => "getItemName",
        ]);

        $this->crud->addColumn([
            'name' => "Status",
            "type" => "model_function_custom",
            "function_name" => "getStatusText",
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->crud->setValidation(OrderRequest::class);

        $this->crud->addField([
            'name' => "status",
            'label' => "Status",
            "type" => "select2_from_array",
            "options" => Order::DATA_STATUS_ARRAY,
            'allows_null' => false,
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
