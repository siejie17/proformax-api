<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('selection_groups', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['classification_id']);

            // Then drop the column
            $table->dropColumn('classification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selection_groups', function (Blueprint $table) {
            // Recreate the column
            $table->foreignId('classification_id')
                  ->nullable()
                  ->constrained('building_classifications')
                  ->nullOnDelete();
        });
    }
};
