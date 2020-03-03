<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\FailJobRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class FailJobCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class FailJobCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        $this->crud->setModel('App\Models\FailJob');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/failjob');
        $this->crud->setEntityNameStrings('failjob', 'fail_jobs');

        $this->crud->operation(['list'], function() {
            $this->crud->removeButtons(['create', 'show', 'update', 'delete']);
        });
    }

    protected function setupListOperation()
    {
        $this->crud->addColumn([
            'name' => "id",
            "type" => "text",
            "title" => "Id"
        ]);

        $this->crud->addColumn([
            'name' => "order_number",
            "type" => "model_function_custom",
            "function_name" => "getOrderNumber"
        ]);

        $this->crud->addColumn([
            'name' => "exception",
            "type" => "text",
            "title" => "Lỗi"
        ]);

        $this->crud->addColumn([
            'name' => "failed_at",
            "type" => "datetime",
            "title" => "Fail time"
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->crud->setValidation(FailJobRequest::class);

        // TODO: remove setFromDb() and manually define Fields
        $this->crud->setFromDb();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
