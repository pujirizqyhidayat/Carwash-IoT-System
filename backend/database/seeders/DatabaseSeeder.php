<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\ParkingLocation;
use App\Models\UltrasonicSensor;
use App\Models\User;
use App\Models\WashService;
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
                'location_id' => null,
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
                'location_id' => null,
                'status' => 'active',
            ]
        );

        $location = ParkingLocation::updateOrCreate(
            ['location_name' => 'R Moda Car Wash'],
            [
                'owner_id' => $owner->id,
                'address' => 'Jl. Sudirman No. 15',
                'capacity' => 20,
            ]
        );

        User::updateOrCreate(
            ['email' => 'cashier@carwash.test'],
            [
                'full_name' => 'Cashier Operator',
                'username' => 'cashier',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
                'location_id' => $location->id,
                'status' => 'active',
            ]
        );


        foreach ([
            ['service_name' => 'Cuci Biasa', 'price' => 30000],
            ['service_name' => 'Cuci Total', 'price' => 50000],
            ['service_name' => 'Cuci + Vacuum', 'price' => 40000],
        ] as $service) {
            WashService::updateOrCreate(
                ['service_name' => $service['service_name']],
                ['price' => $service['price'], 'is_active' => true]
            );
        }
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
