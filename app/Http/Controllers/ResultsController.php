<?php

namespace App\Http\Controllers;

use App\Services\CostCalculationService;
use App\Services\GreenElementsDataService;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    protected $costCalculation;
    protected $greenElementsData;

    public function __construct(CostCalculationService $costCalculation, GreenElementsDataService $greenElementsData)
    {
        $this->costCalculation = $costCalculation;
        $this->greenElementsData = $greenElementsData;
    }

    public function getResults(Request $request)
    {
        $greenElements = $this->greenElementsData->getGreenElementsData($request->input('buildingType'));

        $cost = $this->costCalculation->calculateCost($request);

        return response()->json([
            'cost' => $cost,
            'green_elements' => $greenElements,
        ]);
    }
}
