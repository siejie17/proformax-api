<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Map old enum values to new role names.
        $roleMap = [
            'viewer' => 'member',
            'member' => 'member',
            'editor' => 'developer',
        ];

        // Add the role_id column (nullable first so existing rows can be updated).
        Schema::table('project_members', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        // Populate role_id from the old enum column.
        foreach ($roleMap as $enumValue => $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                DB::table('project_members')
                    ->where('role', $enumValue)
                    ->update(['role_id' => $roleId]);
            }
        }

        // Set a default for any rows that didn't match (shouldn't happen, but be safe).
        $defaultRoleId = DB::table('roles')->where('name', 'member')->value('id');
        if ($defaultRoleId) {
            DB::table('project_members')
                ->whereNull('role_id')
                ->update(['role_id' => $defaultRoleId]);
        }

        // Make role_id non-nullable now that all rows have a value.
        Schema::table('project_members', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
        });

        // Drop the old enum column.
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        // Restore the enum column.
        Schema::table('project_members', function (Blueprint $table) {
            $table->enum('role', ['viewer', 'member', 'editor'])->default('member')->after('added_by');
        });

        // Restore enum values from role_id.
        $roleMap = [
            'member'   => 'member',
            'developer' => 'editor',
        ];

        foreach ($roleMap as $roleName => $enumValue) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                DB::table('project_members')
                    ->where('role_id', $roleId)
                    ->update(['role' => $enumValue]);
            }
        }

        // Set default for any rows without a matching role.
        DB::table('project_members')
            ->whereNull('role')
            ->orWhere('role', '')
            ->update(['role' => 'member']);

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
