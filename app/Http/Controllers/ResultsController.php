<?php

namespace App\Http\Controllers;

use App\Models\Cost;
use App\Models\Project;
use App\Models\UserAnswer;
use App\Services\CostCalculationService;
use App\Services\FormDataMappingService;
use App\Services\GreenElementsDataService;
use App\Services\ThreeDObjectService;
// use App\Services\ThreeDObjectService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultsController extends Controller
{
    protected $costCalculation;
    protected $greenElementsData;
    protected $threeDObject;
    private $mappingService;

    public function __construct(CostCalculationService $costCalculation, GreenElementsDataService $greenElementsData, ThreeDObjectService $threeDObject, FormDataMappingService $mappingService)
    {
        $this->costCalculation = $costCalculation;
        $this->greenElementsData = $greenElementsData;
        $this->threeDObject = $threeDObject;
        $this->mappingService = $mappingService;
    }

    public function getResults(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'projectName' => 'required|string',
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

            $threeDObjects = $this->threeDObject->get3DVisibilityConfig();

            return response()->json([
                'cost' => $cost,
                'green_elements' => $greenElements,
                'three_d_objects' => $threeDObjects,
                'mapped_form_data' => $mappedData,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function submitAssessment(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $rating = $request->input('rating');
            $formData = $request->input('form_data');
            $costsData = $request->input('costs');
            $checkedItems = $request->input('checked_items');

            // Create Project in its own transaction
            $projectId = DB::transaction(function () use ($userId, $rating, $formData, $costsData) {
                try {
                    $project = Project::create([
                        'user_id' => $userId,
                        'name' => $formData['projectName'],
                        'building_type_id' => $formData['buildingType'],
                        'category' => $formData['category'],
                        'size' => $formData['buildingSize'],
                        'year' => $formData['year'],
                        'location' => config('formDataMappings.locations_reverse')[$formData['location']],
                        'structure_id' => (int)$formData['structure'],
                        'cost_preview_way' => $formData['costPreviewWay'],
                        'budget' => $formData['projectBudget'],
                        'adjusted_cost' => $costsData['total_cost'],
                        'rating' => $rating,
                        'target_certification' => $formData['certifiedRatingScale'],
                        'created_at' => now(),
                    ]);
                    Log::info('Created project with ID: ' . $project->id);
                    return $project->id;
                } catch (Exception $e) {
                    Log::error('Failed to create project: ' . $e->getMessage());
                    throw $e;
                }
            });

            // Save costs in its own transaction (only if not Simplified)
            if ($formData['costPreviewWay'] !== 'Simplified') {
                DB::transaction(function () use ($projectId, $costsData) {
                    try {
                        $this->saveCostBreakdown($projectId, $costsData['cost_breakdown']);
                    } catch (Exception $e) {
                        Log::error('Failed to save cost breakdown: ' . $e->getMessage());
                        throw $e;
                    }
                });
            }

            // Save checked items and subitems in its own transaction
            DB::transaction(function () use ($projectId, $userId, $checkedItems) {
                try {
                    $this->saveUserAnswers($projectId, $userId, $checkedItems);
                } catch (Exception $e) {
                    Log::error('Failed to save user answers: ' . $e->getMessage());
                    throw $e;
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Assessment submitted successfully.',
                'project_id' => $projectId
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting assessment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save cost breakdown hierarchically
     */
    private function saveCostBreakdown($projectId, $costBreakdown)
    {
        foreach ($costBreakdown as $code => $data) {
            // Create parent cost entry
            $parentCost = Cost::create([
                'project_id' => $projectId,
                'code' => $code,
                'description' => $data['description'] ?? '',
                'item_cost' => $data['cost'] ?? 0,
                'level' => 0,
            ]);

            // Save children if they exist
            if (isset($data['children']) && is_array($data['children'])) {
                foreach ($data['children'] as $childCode => $childData) {
                    Cost::create([
                        'project_id' => $projectId,
                        'code' => $childCode,
                        'description' => $childData['description'] ?? '',
                        'item_cost' => $childData['cost'] ?? 0,
                        'parent_id' => $parentCost->id,
                        'level' => 1,
                    ]);
                }
            }
        }
    }

    /**
     * Save user answers for checked items and subitems
     */
    private function saveUserAnswers($projectId, $userId, $checkedItems)
    {
        // Save checked items
        if (isset($checkedItems['checkedItems']) && is_array($checkedItems['checkedItems'])) {
            foreach ($checkedItems['checkedItems'] as $itemId) {
                UserAnswer::create([
                    'user_id' => $userId,
                    'item_id' => $itemId,
                    'project_id' => $projectId,
                ]);
            }
        }

        // Save checked subitems
        if (isset($checkedItems['checkedSubitems']) && is_array($checkedItems['checkedSubitems'])) {
            foreach ($checkedItems['checkedSubitems'] as $itemId => $subitems) {
                foreach ($subitems as $subitemId) {
                    UserAnswer::create([
                        'user_id' => $userId,
                        'subitem_id' => $subitemId,
                        'item_id' => $itemId,
                        'project_id' => $projectId,
                    ]);
                }
            }
        }

        // Save custom items
        if (isset($checkedItems['customItems']) && is_array($checkedItems['customItems'])) {
            foreach ($checkedItems['customItems'] as $itemId => $customItems) {
                foreach ($customItems as $custom) {
                    UserAnswer::create([
                        'user_id' => $userId,
                        'item_id' => $itemId,
                        'custom_answer' => $custom['description'] ?? '',
                        'project_id' => $projectId,
                    ]);
                }
            }
        }
    }
}
