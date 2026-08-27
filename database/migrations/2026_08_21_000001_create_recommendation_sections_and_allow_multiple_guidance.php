<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_sections', function (Blueprint $table) {
            $table->id();
            $table->string('certification_level', 80)->unique();
            $table->string('title');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $existing = DB::table('recommendations')
            ->select('certification_level', DB::raw('MIN(title) as title'))
            ->groupBy('certification_level')
            ->get();

        foreach ($existing as $item) {
            DB::table('recommendation_sections')->insert([
                'certification_level' => $item->certification_level,
                'title' => $item->title,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropUnique(['certification_level']);
            $table->index('certification_level');
        });
    }

    public function down(): void
    {
        $duplicateLevels = DB::table('recommendations')
            ->select('certification_level')
            ->groupBy('certification_level')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('certification_level');

        foreach ($duplicateLevels as $level) {
            $keep = DB::table('recommendations')->where('certification_level', $level)->min('id');
            DB::table('recommendations')->where('certification_level', $level)->where('id', '!=', $keep)->delete();
        }

        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropIndex(['certification_level']);
            $table->unique('certification_level');
        });

        Schema::dropIfExists('recommendation_sections');
    }
};
