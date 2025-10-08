<?php

namespace App\Services;

use Illuminate\Http\Request;

class CostCalculationService
{
    public function calculateCost(Request $request)
    {
        // $category = Category::find($categoryId);
        // if (!$category) {
        //     throw new \Exception("Category not found");
        // }

        // $costPerMeterSquared = $category->cost_per_meter_squared;
        return 100;
    }
}
