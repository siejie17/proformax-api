<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $memberRoleId = DB::table('roles')->where('name', 'member')->value('id');
        $ownerRoleId = DB::table('roles')->where('name', 'gbi_facilitator')->value('id');

        if (! $memberRoleId || ! $ownerRoleId) {
            return;
        }

        DB::table('projects')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunkById(500, function ($projects) use ($ownerRoleId) {
                foreach ($projects as $project) {
                    DB::table('project_members')
                        ->where('project_id', $project->id)
                        ->where('user_id', $project->user_id)
                        ->whereNull('role_id')
                        ->update(['role_id' => $ownerRoleId]);
                }
            });

        DB::table('project_members')
            ->whereNull('role_id')
            ->update(['role_id' => $memberRoleId]);
    }

    public function down(): void
    {
        // Role backfills are intentionally retained on rollback.
    }
};
