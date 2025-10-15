<?php

namespace App\Http\Controllers;

use App\Services\CostCalculationService;
use App\Services\FormDataMappingService;
use App\Services\GreenElementsDataService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResultsController extends Controller
{
    protected $costCalculation;
    protected $greenElementsData;

    private $mappingService;

    public function __construct(CostCalculationService $costCalculation, GreenElementsDataService $greenElementsData, FormDataMappingService $mappingService)
    {
        $this->costCalculation = $costCalculation;
        $this->greenElementsData = $greenElementsData;
        $this->mappingService = $mappingService;
    }

    public function getResults(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'buildingType' => 'required|string',
            'category' => 'required|string',
            'year' => 'required|integer',
            'buildingSize' => 'required|numeric',
            'projectBudget' => 'required|numeric',
            'state' => 'required|string',
            'region' => 'nullable|string',
            'structure' => 'required|string',
            'certifiedRatingScale' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ]);
        }

        $formData = $request->all();

        $mappingErrors = $this->mappingService->validateFormData($formData);
        if (!empty($mappingErrors)) {
            return response()->json([
                'success' => false,
                'errors' => $mappingErrors,
                'msg' => 'Validation errors in form data.'
            ]);
        }

        try {
            $mappedData = $this->mappingService->mapFormData($formData);

            $greenElements = $this->greenElementsData->getGreenElementsData($mappedData['buildingType']);

            $cost = $this->costCalculation->calculateCost($mappedData);

            return response()->json([
                'cost' => $cost,
                'green_elements' => $greenElements,
                'mapped_form_data' => $mappedData,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
