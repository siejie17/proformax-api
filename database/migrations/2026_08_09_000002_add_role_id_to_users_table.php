<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add the role_id column (nullable first so existing rows can be updated).
            $table->foreignId('role_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        // Assign a default role to any existing users that don't have one.
        $defaultRoleId = DB::table('roles')->where('name', 'member')->value('id');
        if ($defaultRoleId) {
            DB::table('users')
                ->whereNull('role_id')
                ->update(['role_id' => $defaultRoleId]);
        }

        // Make role_id non-nullable now that all rows have a value.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
