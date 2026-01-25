<?php

namespace App\Http\Controllers;

use App\Models\BuildingType;
use App\Models\Location;
use App\Models\Structure;

class FormController extends Controller
{
    public function getFormInputs()
    {
        $buildingTypesRaw = BuildingType::with('categories')
            ->select('id', 'name', 'code')
            ->get();

        $buildingTypes = $buildingTypesRaw->pluck('name')->values();
        $categories = $buildingTypesRaw
            ->mapWithKeys(fn($type) => [
                $type->name => $type->categories->pluck('category')->values()
            ]);

        $allStructures = Structure::with('buildingType')->get();
        $structures = [];

        foreach ($allStructures as $structure) {
            $name = $structure->buildingType->name;
            $availabilities = explode(',', $structure->availability);

            foreach ($availabilities as $availableCode) {
                $structures[$name][$availableCode][] = $structure->name;
            }
        }

        $states = Location::whereNull('parent_location_id')
            ->get()
            ->pluck('location_name')
            ->values();

        $regions = Location::whereNotNull('parent_location_id')
            ->with('parentLocation')
            ->get()
            ->groupBy(fn($region) => $region->parentLocation->location_name)
            ->map(fn($group) => $group->pluck('location_name')->values())
            ->toArray();

        return response()->json([
            'buildingTypes' => $buildingTypes,
            'categories' => $categories,
            'structures' => $structures,
            'states' => $states,
            'regions' => $regions,
        ]);
    }
}
