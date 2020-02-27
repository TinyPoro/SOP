<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OrderRequest;
use App\Models\Order;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Auth;

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

        $this->crud->addFilter([
            'type' => 'dropdown',
            'name' => 'status',
            'label'=> 'Trạng thái'
        ],
            Order::ORDER_STATUS_ARRAY,
            function($value) {
                $this->crud->addClause('where', 'status', '=', $value);
            });

        $this->crud->addFilter([
            'type' => 'text',
            'name' => 'order_number',
            'label'=> 'Mã đơn'
        ],
            false,
            function($value) {
                $this->crud->addClause('where', 'order_number', '=', $value);
            });

        $this->crud->addFilter([
            'type' => 'date',
            'name' => 'order_date',
            'label'=> 'Ngày'
        ],
            false,
            function($value) {
                $this->crud->addClause('where', 'order_date', '=', $value);
            });

        $this->crud->addFilter([
            'type' => 'text',
            'name' => 'customer_name',
            'label'=> 'Tên khách hàng'
        ],
            false,
            function($value) {
                $this->crud->addClause('where', 'customer_name', 'like', "%$value%");
            });

        $this->crud->addFilter([
            'type' => 'text',
            'name' => 'number_of_item',
            'label'=> 'Số items'
        ],
            false,
            function($value) {
                $this->crud->query = $this->crud->query->whereRaw("(SELECT SUM(number_of_item) from items where items.order_id = orders.id) = $value");
            });

        $this->crud->addFilter([
            'type' => 'text',
            'name' => 'item_name',
            'label'=> 'Tên item'
        ],
            false,
            function($value) {
                $this->crud->query = $this->crud->query->whereHas('items', function ($query) use ($value) {
                    $query->where('item_name', 'like', "%$value%");
                });
            });

        $this->crud->addFilter([
            'type' => 'text',
            'name' => 'shipping_method',
            'label'=> 'Phương thức ship'
        ],
            false,
            function($value) {
                $this->crud->addClause('where', 'shipping_method', '=', $value);
            });

        $this->crud->operation(['show'], function() {
            $this->crud->removeButton('create');

            $user = Auth::user();

            if($user and $user->hasRole('Staff')) {
                $this->crud->removeButton('delete');
            }
        });

        $this->crud->operation(['list'], function() {
            $this->crud->removeButtons(['create', 'show', 'update', 'delete']);

            $this->crud->addButton("line", "custom_dropdown", "view", 'crud::buttons.custom_dropdown');
        });
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        $this->crud->addColumn([
            'name' => "shipping_method",
            "type" => "text",
            "title" => "Shipping Method"
        ]);

        $this->crud->addColumn([
            'name' => "total_price",
            "type" => "text",
            "title" => "Revenue"
        ]);

        $this->crud->addColumn([
            'name' => "customer_email",
            "type" => "text",
            "title" => "Customer Email"
        ]);

        $this->crud->addColumn([
            'name' => "shipping_address",
            "type" => "text",
            "title" => "Shipping Address"
        ]);
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
            "type" => "model_function_custom",
            "function_name" => "getLinkToOrder"
        ]);

        $this->crud->addColumn([
            'name' => "link_to_gd",
            "type" => "model_function_custom",
            "function_name" => "getLinkToGd"
        ]);

        $this->crud->addColumn([
            'name' => "#_of_item",
            "type" => "model_function_custom",
            "function_name" => "getNumberOfItem",
        ]);

        $this->crud->addColumn([
            'name' => "item_name",
            "type" => "model_function_custom",
            "function_name" => "getItemName",
        ]);

        $this->crud->addColumn([
            'name' => "status",
            "type" => "model_function_custom",
            "function_name" => "getStatusText",
        ]);

        $this->crud->addColumn([
            'name' => "note",
            "type" => "model_function_custom",
            "function_name" => "getNoteText",
        ]);

        $this->crud->addColumn([
            'name' => "internal_remark",
            "type" => "text",
            "title" => "Internal Remark"
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->crud->setValidation(OrderRequest::class);

        $this->crud->addField([
            'name' => "status",
            'label' => "Status",
            "type" => "select2_from_array",
            "options" => Order::ORDER_STATUS_ARRAY,
            'allows_null' => false,
        ]);

        $this->crud->addField([
            'name' => "internal_remark",
            'label' => "Internal Remark",
            "type" => "textarea",
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
