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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_type_id')->constrained('building_types')->cascadeOnDelete();
            $table->string('category'); // Shopslot etc.
            $table->decimal('size', 15, 2);
            $table->year('year');
            $table->string('location');
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->timestamps();
            $table->integer('rating')->default(0);
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('adjusted_cost', 15, 2)->nullable();
            $table->enum('cost_preview_way', ["Brief", "Detailed"]);
            $table->string('target_certification')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
