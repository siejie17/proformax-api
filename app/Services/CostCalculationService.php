<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Request;

class CostCalculationService
{
    public function calculateCost(array $mappedData): array|float
    {
        // Extract form data
        $buildingType = $mappedData['buildingType'];
        $category = $mappedData['category'];
        $year = $mappedData['year'];
        $buildingSize = $mappedData['buildingSize'];
        $projectBudget = $mappedData['projectBudget'];
        $structure = $mappedData['structure'];
        $location = $mappedData['location'];
        $certifiedRatingScale = $mappedData['certifiedRatingScale'];
        $costPreviewWay = $mappedData['costPreviewWay'];

        if ($costPreviewWay === 'Brief') {
            return $this->getBriefConstructionCost($category, $buildingSize, $year, $location, $structure);
        }
           
        return $this->getDetailedConstructionCost($category, $buildingSize, $year, $location, $structure, $certifiedRatingScale);
    }

    /**
     * Get category data matched by category column
     *
     * @param string $category
     * @return Category|null
     */
    public function getCategoryByName(string $category): ?Category
    {
        return Category::where('category', $category)->first();
    }

    /**
     * Get the latest available TPI value for the given year
     * If the exact year is not available, returns the latest year that is <= requested year
     *
     * @param array $tpiData
     * @param int $year
     * @return float
     * @throws \Exception
     */
    private function getLatestTpiValue(array $tpiData, int $year): float
    {
        // Filter years <= requested year, then get the maximum
        $availableYears = array_filter(
            array_keys($tpiData), 
            fn($y) => (int)$y <= $year
        );
        
        if (empty($availableYears)) {
            throw new \Exception("No TPI data available for year {$year} or earlier");
        }
        
        $latestYear = max($availableYears);
        return $tpiData[$latestYear];
    }

    public function getBriefConstructionCost(string $category, float $buildingSize, int $year, string $location, string $structure): float
    {
        $categoryData = Category::where('category', $category)->first();

        if (!$categoryData) {
            throw new \Exception("Category '{$category}' not found");
        }

        $tpiData = config('tpiMapping');
        $tpi = $this->getLatestTpiValue($tpiData, $year) / $tpiData["2021"];

        $costBeforeLocationAdjustment = $categoryData->cost_per_meter_squared * $buildingSize * $tpi;

        if (in_array($location, ['G', 'H', 'I', 'J', 'K', 'L'])) {
            $locationMultipliers = config('locationIndex');
            $multiplier = $locationMultipliers[$structure][$location] ?? 1.0;
            return round($costBeforeLocationAdjustment * $multiplier, 2);
        }

        // Calculate brief construction cost based on category data
        return round($costBeforeLocationAdjustment, 2);
    }

    public function getDetailedConstructionCost(string $category, float $buildingSize, int $year, string $location, string $structure, string $certifiedRatingScale): array
    {
        $prevProjects = config('prevProjects');
        $locationMultipliers = config('locationIndex');

        if (!isset($prevProjects[$category])) {
            throw new \Exception("Category '{$category}' not found in previous projects");
        }

        $project = $prevProjects[$category];
        $locationIndex = ($project['location'] !== $location) ? $locationMultipliers[$structure][$location] : 1.0;

        $tpiData = config('tpiMapping');
        $tpi = $this->getLatestTpiValue($tpiData, $year) / $tpiData[$project['year']];

        $totalCost = 0;
        foreach ($project['cost_breakdown'] as $key => &$node) {
            $nodeCost = $this->calculateNodeCost($node, $buildingSize, $locationIndex, $tpi, $node['gfa']);
            $node['cost'] = round($nodeCost, 2);
            $totalCost += $nodeCost;
        }

        $project['total_cost'] = round($totalCost, 2);

        $new_project = [
            'total_cost' => $project['total_cost'],
            'cost_breakdown' => $project['cost_breakdown'],
        ];

        return $new_project;
    }

    private function calculateNodeCost(&$node, $buildingSize, $locationIndex, $tpi, $parentGfa = null): float
    {
        $currentGfa = $node['gfa'] ?? $parentGfa;

        if (isset($node['cost']) && !isset($node['children'])) {
            $costPerMeter = $node['cost'] / $currentGfa;
            return round($costPerMeter * $buildingSize * $tpi * $locationIndex, 2);
        }

        $total = 0;
        if (isset($node['children'])) {
            foreach ($node['children'] as $key => &$child) {
                $childCost = $this->calculateNodeCost($child, $buildingSize, $locationIndex, $tpi, $currentGfa);
                $child['cost'] = $childCost;
                $total += $childCost;
            }
        }

        $node['cost'] = round($total, 2);
        return $node['cost'];
    }
}
