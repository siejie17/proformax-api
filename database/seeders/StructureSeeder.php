<?php

namespace Database\Seeders;

use App\Models\Structure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $structures = config('formDataMappings.structures');

        foreach ($structures as $name => $code) {
            Structure::create([
                'name' => $name,
                'code' => $code,
            ]);
        }
    }
}
