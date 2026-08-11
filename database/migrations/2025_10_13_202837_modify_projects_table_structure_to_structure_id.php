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
        Schema::table('projects', function (Blueprint $table) {
            // Drop the old structure column only if it still exists in older databases.
            if (Schema::hasColumn('projects', 'structure')) {
                $table->dropColumn('structure');
            }

            // Add the new structure_id foreign key column only when it is missing.
            if (! Schema::hasColumn('projects', 'structure_id')) {
                $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop the foreign key and structure_id column only if they exist.
            if (Schema::hasColumn('projects', 'structure_id')) {
                $table->dropForeign(['structure_id']);
                $table->dropColumn('structure_id');
            }

            // Re-add the original structure column only when it is missing.
            if (! Schema::hasColumn('projects', 'structure')) {
                $table->string('structure');
            }
        });
    }
};
