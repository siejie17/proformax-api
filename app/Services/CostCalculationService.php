<?php

namespace App\Services;

use App\Models\Category;
use App\Models\LocationIndex;
use App\Models\TenderPriceIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostCalculationService
{
    public function calculateCost($mappedData)
    {
        // Extract form data
        $category = $mappedData['category'];
        $year = $mappedData['year'];
        $buildingSize = $mappedData['buildingSize'];
        $structure = $mappedData['structure'];
        $location = $mappedData['location'];
        $certifiedRatingScale = $mappedData['certifiedRatingScale'];

        return $this->getConstructionCost($category, $buildingSize, $year, $location, $structure, $certifiedRatingScale);
    }

    public function getConstructionCost(string $category, float $buildingSize, int $year, string $location, string $structure)
    {
        $categoryId = DB::table('categories')->where('category', $category)->value('id');
        if (!$categoryId) {
            throw new \Exception("Category '{$category}' not found");
        }

        $prevProject = DB::table('prev_projects')->where('category_id', $categoryId)->first();

        $prevProjectLocationId = $prevProject ? $prevProject->location_id : null;
        $prevProjectLocation = $prevProjectLocationId ? DB::table('locations')->where('id', $prevProjectLocationId)->value('code') : null;

        $locationIndex = ($prevProjectLocation !== $location) ? LocationIndex::where('structure', $structure)
            ->where('location', $location)
            ->value('multiplier') : 1.0;

        $curProjectTpi = TenderPriceIndex::where('year', '<=', $year)
            ->orderByDesc('year')
            ->value('index');
        $prevProjectTpi = TenderPriceIndex::where('year', '<=', (int)$prevProject->year)
            ->orderByDesc('year')
            ->value('index');

        $tpi = $curProjectTpi / $prevProjectTpi;

        $totalCost = 0;

        // Build nested cost_breakdown from prev_project_costs
        $prevProjectId = $prevProject ? $prevProject->id : null;
        $costs = DB::table('prev_project_costs')
            ->where('prev_project_id', $prevProjectId)
            ->orderBy('level')
            ->get()
            ->toArray();

        // Index by id for fast lookup
        $costsById = [];
        foreach ($costs as $cost) {
            $cost->children = [];
            $costsById[$cost->id] = $cost;
        }

        // Build the tree
        $tree = [];
        foreach ($costs as $cost) {
            if ($cost->parent_id) {
                $costsById[$cost->parent_id]->children[] = $cost;
            } else {
                $tree[$cost->code] = $cost;
            }
        }

        // Format to match config structure
        function formatCostNode($node)
        {
            $arr = [
                'description' => $node->description,
            ];
            if ($node->cost_per_gfa !== null) {
                $arr['cost_per_gfa'] = $node->cost_per_gfa;
            }
            if (!empty($node->children)) {
                $arr['children'] = [];
                foreach ($node->children as $child) {
                    $arr['children'][$child->code] = formatCostNode($child);
                }
            }
            return $arr;
        }

        $cost_breakdown = [];
        foreach ($tree as $code => $node) {
            $cost_breakdown[$code] = formatCostNode($node);
        }

        ksort($cost_breakdown, SORT_NATURAL | SORT_FLAG_CASE);

        // Calculate costs as before
        foreach ($cost_breakdown as $key => &$node) {
            $nodeCost = $this->calculateNodeCost($node, $buildingSize, $locationIndex, $tpi);
            $node['cost'] = round($nodeCost, 2);
            $totalCost += $nodeCost;
        }

        $new_project = [
            'total_cost' => round($totalCost, 2),
            'cost_breakdown' => $cost_breakdown,
        ];

        return $new_project;
    }

    private function calculateNodeCost(&$node, $buildingSize, $locationIndex, $tpi): float
    {
        if (isset($node['cost_per_gfa']) && !isset($node['children'])) {
            $cost = round($node['cost_per_gfa'] * $buildingSize * $tpi * $locationIndex, 2);
            unset($node['cost_per_gfa']);
            return $cost;
        }

        $total = 0;
        if (isset($node['children'])) {
            foreach ($node['children'] as $key => &$child) {
                $childCost = $this->calculateNodeCost($child, $buildingSize, $locationIndex, $tpi);
                $child['cost'] = $childCost;
                unset($child['cost_per_gfa']);
                $total += $childCost;
            }
        }

        $node['cost'] = round($total, 2);
        return $node['cost'];
    }
}
