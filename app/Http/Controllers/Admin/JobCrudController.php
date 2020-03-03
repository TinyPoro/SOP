<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\JobRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class JobCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class JobCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        $this->crud->setModel('App\Models\Job');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/job');
        $this->crud->setEntityNameStrings('job', 'jobs');

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
            'name' => "created_at",
            "type" => "datetime",
            "title" => "Ngày tạo"
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->crud->setValidation(JobRequest::class);

        // TODO: remove setFromDb() and manually define Fields
        $this->crud->setFromDb();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
