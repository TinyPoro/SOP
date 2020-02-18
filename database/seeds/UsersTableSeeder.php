<?php

use \Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use \Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Staff']);

        $this->createDataProduction();
    }

    private function createDataProduction ()
    {
        $user = \App\User::create([
            'name' => 'Trần Minh',
            'email' => 'minhtran9691@gmail.com',
            'password' => Hash::make('123456'),
        ]);

        $user->syncRoles('Admin');
    }
}
