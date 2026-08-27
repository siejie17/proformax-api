<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ownerRoleId = DB::table('roles')->where('name', 'gbi_facilitator')->value('id');

        if (! $ownerRoleId) {
            return;
        }

        DB::table('projects')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunkById(500, function ($projects) use ($ownerRoleId) {
                $now = now();
                $memberships = $projects->map(fn ($project) => [
                    'project_id' => $project->id,
                    'user_id' => $project->user_id,
                    'added_by' => $project->user_id,
                    'role_id' => $ownerRoleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('project_members')->insertOrIgnore($memberships);
            });
    }

    public function down(): void
    {
        // Data repair is intentionally retained on rollback.
    }
};
