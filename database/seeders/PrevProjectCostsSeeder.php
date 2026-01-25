<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrevProjectCostsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prevProjects = require base_path('config/prevProjects.php');
        $prevProjectsTable = DB::table('prev_projects')->get(['id', 'category_id', 'year', 'location_id']);
        $categoryMap = [];
        foreach ($prevProjectsTable as $row) {
            $categoryMap[$row->category_id . '-' . $row->year . '-' . $row->location_id] = $row->id;
        }

        $index = 1;

        foreach ($prevProjects as $categoryName => $project) {
            $prevProjectId = $index;
            $this->seedCostBreakdown($project['cost_breakdown'], $prevProjectId);
            $index++;
        }
    }

    private function seedCostBreakdown(array $breakdown, int $prevProjectId, $parentId = null, $level = 1)
    {
        foreach ($breakdown as $code => $node) {
            $cost = [
                'prev_project_id' => $prevProjectId,
                'code' => $code,
                'description' => $node['description'] ?? '',
                'cost_per_gfa' => $node['cost_per_gfa'] ?? null,
                'parent_id' => $parentId,
                'level' => $level,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $id = DB::table('prev_project_costs')->insertGetId($cost);
            if (isset($node['children'])) {
                $this->seedCostBreakdown($node['children'], $prevProjectId, $id, $level + 1);
            }
        }
    }
}
