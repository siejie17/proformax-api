<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_members', 'role')) {
            return;
        }

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_members', 'role')) {
            return;
        }

        Schema::table('project_members', function (Blueprint $table) {
            $table->string('role')->nullable();
        });
    }
};
