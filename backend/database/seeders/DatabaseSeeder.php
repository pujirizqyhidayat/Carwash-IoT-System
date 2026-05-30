<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\ParkingLocation;
use App\Models\UltrasonicSensor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@carwash.test'],
            [
                'full_name' => 'System Admin',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        $owner = User::updateOrCreate(
            ['email' => 'owner@carwash.test'],
            [
                'full_name' => 'Carwash Owner',
                'username' => 'owner',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'cashier@carwash.test'],
            [
                'full_name' => 'Cashier Operator',
                'username' => 'cashier',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
                'status' => 'active',
            ]
        );

        $location = ParkingLocation::updateOrCreate(
            ['location_name' => 'Rizki Car Wash'],
            [
                'owner_id' => $owner->id,
                'address' => 'Jl. Sudirman No. 15',
                'capacity' => 20,
            ]
        );

        UltrasonicSensor::updateOrCreate(
            ['sensor_code' => 'ENTRANCE-001'],
            [
                'location_id' => $location->id,
                'sensor_name' => 'Entrance Sensor 1',
                'sensor_position' => 'entry',
                'status' => 'active',
                'threshold_distance' => 40,
                'installed_at' => now(),
                'last_seen_at' => now(),
            ]
        );
    }
}
