<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationIndexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];
        $locationIndex = require base_path('config/locationIndex.php');
        foreach ($locationIndex as $structure => $locations) {
            foreach ($locations as $location => $multiplier) {
                if ($multiplier !== null) {
                    $data[] = [
                        'structure' => (int)$structure,
                        'location' => $location,
                        'multiplier' => $multiplier,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
        DB::table('location_indices')->insert($data);
    }
}
