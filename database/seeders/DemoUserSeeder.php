<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('DEMO_USER_PASSWORD', 'demo-password');

        User::updateOrCreate(
            ['email' => 'loan.officer@delta.test'],
            [
                'name' => 'Demo Loan Officer',
                'password' => Hash::make($password),
                'role' => 'loan_officer',
                'authority_limit' => 0,
            ]
        );

        User::updateOrCreate(
            ['email' => 'credit.officer@delta.test'],
            [
                'name' => 'Demo Credit Officer',
                'password' => Hash::make($password),
                'role' => 'credit_officer',
                'authority_limit' => 250000,
            ]
        );

        User::updateOrCreate(
            ['email' => 'senior.credit@delta.test'],
            [
                'name' => 'Senior Credit Officer',
                'password' => Hash::make($password),
                'role' => 'credit_officer',
                'authority_limit' => 500000,
            ]
        );
    }
}
