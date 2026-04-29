<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['id' => 1, 'code' => 'A', 'location_name' => 'Pulau Pinang', 'parent_location_id' => null],
            ['id' => 2, 'code' => 'A', 'location_name' => 'Kedah', 'parent_location_id' => null],
            ['id' => 3, 'code' => 'A', 'location_name' => 'Perlis', 'parent_location_id' => null],
            ['id' => 4, 'code' => 'B', 'location_name' => 'Perak', 'parent_location_id' => null],
            ['id' => 5, 'code' => 'C', 'location_name' => 'Selangor', 'parent_location_id' => null],
            ['id' => 6, 'code' => 'C', 'location_name' => 'W.P. Kuala Lumpur', 'parent_location_id' => null],
            ['id' => 7, 'code' => 'C', 'location_name' => 'Melaka', 'parent_location_id' => null],
            ['id' => 8, 'code' => 'C', 'location_name' => 'Negeri Sembilan', 'parent_location_id' => null],
            ['id' => 9, 'code' => 'D', 'location_name' => 'Johor', 'parent_location_id' => null],
            ['id' => 10, 'code' => 'E', 'location_name' => 'Pahang', 'parent_location_id' => null],
            ['id' => 11, 'code' => 'F', 'location_name' => 'Kelantan', 'parent_location_id' => null],
            ['id' => 12, 'code' => 'F', 'location_name' => 'Terengganu', 'parent_location_id' => null],
            ['id' => 13, 'code' => null, 'location_name' => 'Sabah', 'parent_location_id' => null],
            ['id' => 14, 'code' => null, 'location_name' => 'Sarawak', 'parent_location_id' => null],
            ['id' => 15, 'code' => 'G', 'location_name' => 'Kota Kinabalu', 'parent_location_id' => 13],
            ['id' => 16, 'code' => 'H', 'location_name' => 'Sandakan', 'parent_location_id' => 13],
            ['id' => 17, 'code' => 'I', 'location_name' => 'Tawau', 'parent_location_id' => 13],
            ['id' => 18, 'code' => 'J', 'location_name' => 'Kuching', 'parent_location_id' => 14],
            ['id' => 19, 'code' => 'K', 'location_name' => 'Sibu', 'parent_location_id' => 14],
            ['id' => 20, 'code' => 'L', 'location_name' => 'Miri', 'parent_location_id' => 14],
        ];

        foreach ($locations as $location) {
            DB::table('locations')->updateOrInsert(
                ['id' => $location['id']],
                array_merge($location, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
