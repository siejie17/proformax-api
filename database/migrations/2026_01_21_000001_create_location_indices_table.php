<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_indices', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('structure'); // 1-10
            $table->char('location', 1); // A-L
            $table->float('multiplier');
            $table->timestamps();
            $table->unique(['structure', 'location']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_indices');
    }
};
