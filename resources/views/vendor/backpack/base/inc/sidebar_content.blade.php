@hasanyrole('Admin|Staff')
<!-- This file is used to store sidebar items, starting with Backpack\Base 0.9.0 -->
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="fa fa-dashboard nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
{{--<li class=nav-item><a class=nav-link href="{{ backpack_url('elfinder') }}"><i class="nav-icon fa fa-files-o"></i> <span>{{ trans('backpack::crud.file_manager') }}</span></a></li>--}}
<li class='nav-item'><a class='nav-link' href='{{ backpack_url('order') }}'><i class='nav-icon fa fa-question'></i> Orders</a></li>
{{--<li class='nav-item'><a class='nav-link' href='{{ backpack_url('item') }}'><i class='nav-icon fa fa-question'></i> Items</a></li>--}}
{{--<li class='nav-item'><a class='nav-link' href='{{ backpack_url('shopifyimage') }}'><i class='nav-icon fa fa-question'></i> ShopifyImages</a></li>--}}

@role('Admin')
<li class='nav-item'><a class='nav-link' href='{{ backpack_url('job') }}'><i class='nav-icon fa fa-question'></i> Jobs</a></li>
<li class='nav-item'><a class='nav-link' href='{{ backpack_url('failjob') }}'><i class='nav-icon fa fa-question'></i> FailJobs</a></li>
@endrole


@endhasanyrole

@role('Admin')
<!-- Users, Roles, Permissions -->
<li class="nav-item nav-dropdown">
    <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon fa fa-group"></i> Authentication</a>
    <ul class="nav-dropdown-items">
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('user') }}"><i class="nav-icon fa fa-user"></i> <span>Users</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('role') }}"><i class="nav-icon fa fa-group"></i> <span>Roles</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ backpack_url('permission') }}"><i class="nav-icon fa fa-key"></i> <span>Permissions</span></a></li>
    </ul>
</li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('log') }}'><i class='nav-icon fa fa-terminal'></i> Logs</a></li>
@endrole