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
        Schema::create('prev_project_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prev_project_id');
            $table->string('code');
            $table->string('description');
            $table->decimal('cost_per_gfa', 15, 2)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level');
            $table->timestamps();

            $table->foreign('prev_project_id')->references('id')->on('prev_projects')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('prev_project_costs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prev_project_costs');
    }
};
