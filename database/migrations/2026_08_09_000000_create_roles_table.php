<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();          // e.g. 'member', 'developer'
            $table->string('display_name');             // e.g. 'Member', 'Developer'
            $table->text('description')->nullable();
            $table->unsignedInteger('level')->default(10); // hierarchy: higher = more power
            $table->json('permissions')->nullable();     // granular permission flags
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
