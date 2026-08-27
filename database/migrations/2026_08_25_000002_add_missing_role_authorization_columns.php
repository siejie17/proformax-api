<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'level')) {
                $table->unsignedInteger('level')->default(10)->after('description');
            }

            if (! Schema::hasColumn('roles', 'permissions')) {
                $table->json('permissions')->nullable()->after('level');
            }
        });

        $roles = [
            'member' => [10, ['view_messages', 'view_members']],
            'developer' => [20, ['view_messages', 'view_members', 'send_messages', 'upload_attachments']],
            'quantity_surveyor' => [30, ['view_messages', 'view_members', 'send_messages', 'upload_attachments', 'manage_members']],
            'gbi_facilitator' => [40, ['view_messages', 'view_members', 'send_messages', 'upload_attachments', 'manage_members', 'manage_roles', 'admin']],
        ];

        foreach ($roles as $name => [$level, $permissions]) {
            DB::table('roles')->where('name', $name)->update([
                'level' => $level,
                'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'permissions')) {
                $table->dropColumn('permissions');
            }

            if (Schema::hasColumn('roles', 'level')) {
                $table->dropColumn('level');
            }
        });
    }
};
