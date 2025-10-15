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
            // Drop the old structure column
            $table->dropColumn('structure');
            // Add the new structure_id foreign key column
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop the foreign key and structure_id column
            $table->dropForeign(['structure_id']);
            $table->dropColumn('structure_id');
            // Re-add the original structure column
            $table->string('structure');
        });
    }
};
