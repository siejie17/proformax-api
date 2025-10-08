<?php

namespace App\Http\Controllers;

use App\Models\BuildingType;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Load building types with their categories
        $buildingTypes = BuildingType::with('categories')->get();

        // Transform to desired structure
        return $buildingTypes->map(function ($bt) {
            return [
                'id' => $bt->id,
                'building_type' => $bt->name,
                'categories' => $bt->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'category' => $cat->category,
                        'cost_per_meter_squared' => $cat->cost_per_meter_squared,
                    ];
                }),
            ];
        });
    }
}
