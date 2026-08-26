<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            'Workers??? Site Amenities' => 'Workers’ Site Amenities',
            'Heat Island Effect ??? Greenscape and Water Bodies' => 'Heat Island Effect – Greenscape and Water Bodies',
            'Heat Island Effect ??? Roof' => 'Heat Island Effect – Roof',
        ];

        foreach ($replacements as $corrupted => $corrected) {
            DB::table('items')
                ->where('description', $corrupted)
                ->update(['description' => $corrected]);
        }
    }

    public function down(): void
    {
        $replacements = [
            'Workers’ Site Amenities' => 'Workers??? Site Amenities',
            'Heat Island Effect – Greenscape and Water Bodies' => 'Heat Island Effect ??? Greenscape and Water Bodies',
            'Heat Island Effect – Roof' => 'Heat Island Effect ??? Roof',
        ];

        foreach ($replacements as $corrected => $corrupted) {
            DB::table('items')
                ->where('description', $corrected)
                ->update(['description' => $corrupted]);
        }
    }
};
