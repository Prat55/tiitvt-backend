<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserWithRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_role = Role::create(['name' => RolesEnum::ADMIN->value]);
        $admin_user =  User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'email_verified_at' => now(),
        ]);
        $admin_user->assignRole($admin_role);

        $center_role = Role::create(['name' => RolesEnum::CENTER->value]);
        $center_user =  User::factory()->create([
            'name' => 'Center',
            'email' => 'center@mail.com',
            'email_verified_at' => now(),
        ]);
        $center_user->assignRole($center_role);

        $student_role = Role::create(['name' => RolesEnum::STUDENT->value]);
        $student_user =  User::factory()->create([
            'name' => 'Student',
            'email' => 'student@mail.com',
            'email_verified_at' => now(),
        ]);
        $student_user->assignRole($student_role);
    }
}
