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
        Schema::create('visibility_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('three_d_object_id');
            $table->enum('trigger_type', ['ITEM', 'SUBITEM']);
            $table->unsignedBigInteger('trigger_id');
            $table->index('three_d_object_id');
            $table->foreign('three_d_object_id')->references('id')->on('three_d_objects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visibility_triggers');
    }
};
