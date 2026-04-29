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
            $table->unsignedBigInteger('classification_id')->nullable()->after('building_type_id');
            $table->boolean('has_management')->nullable()->after('classification_id');

            $table->foreign('classification_id')->references('id')->on('building_classifications')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['classification_id']);
            $table->dropColumn(['classification_id', 'has_management']);
        });
    }
};
