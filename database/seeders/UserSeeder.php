<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Create an admin user
         User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'role' => 'super_admin',
            'address' => 'Dhaka, Bangladesh',
            'password' => Hash::make('12345678'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Support Agent',
            'email' => 'supportAgent@gmail.com',
            'role' => 'support_agent',
            'address' => 'Dhaka, Bangladesh',
            'password' => Hash::make('12345678'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'Location Employee',
            'email' => 'locationEmployee@gmail.com',
            'role' => 'location_employee',
            'address' => 'Dhaka, Bangladesh',
            'password' => Hash::make('12345678'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'Third Party',
            'email' => 'thirdParty@gmail.com',
            'role' => 'third_party',
            'address' => 'Dhaka, Bangladesh',
            'password' => Hash::make('12345678'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'Organization',
            'email' => 'Organization@gmail.com',
            'role' => 'organization',
            'address' => 'Dhaka, Bangladesh',
            'password' => Hash::make('12345678'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'Technician',
            'email' => 'Technician@gmail.com',
            'role' => 'technician',
            'address' => 'Dhaka, Bangladesh',
            'password' => Hash::make('12345678'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'user',
            'email' => 'user@gmail.com',
            'role' => 'user',
            'address' => 'Dhaka, Bangladesh',
            'password' => Hash::make('12345678'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
