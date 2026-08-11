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
        if (! Schema::hasTable('prev_projects')) {
            Schema::create('prev_projects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id');
                $table->integer('year');
                $table->unsignedBigInteger('location_id');
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            });

            return;
        }

        Schema::table('prev_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('prev_projects', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('prev_projects')) {
            return;
        }

        Schema::table('prev_projects', function (Blueprint $table) {
            if (Schema::hasColumn('prev_projects', 'location_id')) {
                $table->dropColumn('location_id');
            }
        });
    }
};
