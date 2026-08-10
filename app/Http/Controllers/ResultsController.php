<?php

namespace App\Http\Controllers;

use App\Models\ActualUserAnswer;
use App\Models\Category;
use App\Models\Cost;
use App\Models\Location;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\UserAnswer;
use App\Services\CostCalculationService;
use App\Services\FormDataMappingService;
use App\Services\GreenElementsDataService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultsController extends Controller
{
    protected $costCalculation;
    protected $greenElementsData;
    private $mappingService;

    public function __construct(
        CostCalculationService $costCalculation,
        GreenElementsDataService $greenElementsData,
        FormDataMappingService $mappingService
    ) {
        $this->costCalculation = $costCalculation;
        $this->greenElementsData = $greenElementsData;
        $this->mappingService = $mappingService;
    }

    public function getResults(Request $request)
    {
        $formData = $request->input('formData');

        if (!is_array($formData)) {
            $formData = $request->all();
        }

        if (!is_array($formData)) {
            $formData = [];
        }

        $validator = Validator::make($formData, [
            'projectName' => 'required|string',
            'buildingType' => 'required|string',
            'category' => 'required|string',
            'year' => 'required|integer',
            'buildingSize' => 'required|numeric',
            'projectBudget' => 'nullable',
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

        $mappingErrors = $this->mappingService->validateFormData($formData);
        if (!empty($mappingErrors)) {
            return response()->json([
                'success' => false,
                'errors' => $mappingErrors,
                'msg' => 'Validation errors in form data.'
            ]);
        }

        $admin = $request->boolean('admin');

        try {
            $mappedData = $this->mappingService->mapFormData($formData);
            $greenElements = $this->greenElementsData->getGreenElementsData(
                $mappedData['buildingType'],
                $mappedData['classificationId'] ?? null,
                $mappedData['has_management'] ?? null,
                $admin
            );
            $cost = $this->costCalculation->calculateCost($mappedData);
            $certifications = $this->mappingService->getCertifications($mappedData['buildingType']);

            return response()->json([
                'cost' => $cost,
                'green_elements' => $greenElements,
                'mapped_form_data' => $mappedData,
                'certifications' => $certifications,
                'original_form_data' => $formData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getRealTimePrediction(Request $request)
    {
        $predictionData = $request->input('predictionData');

        if (!is_array($predictionData)) {
            return response()->json([
                'success' => false,
                'message' => 'predictionData must be an object.'
            ], 400);
        }

        $validator = Validator::make($predictionData, [
            'type' => 'required|string',
            'category' => 'required|string',
            'year' => 'required|integer',
            'size' => 'required|numeric',
            'state' => 'required|string',
            'region' => 'nullable|string',
            'structure' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Map frontend data to calculation format
            $data = $this->mappingService->mapPredictionData($predictionData);

            // Ensure mapping returned the required fields
            $requiredFields = [
                'category',
                'year',
                'buildingSize',
                'location',
                'structure'
            ];

            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $data)) {
                    throw new Exception("Mapped data is missing required field '{$field}'.");
                }
            }

            // Calculate cost
            $cost = $this->costCalculation->calculateCost($data);

            return response()->json([
                'success' => true,
                'data' => [
                    'totalCost' => $cost['total_cost'],
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Real-time prediction failed.', [
                'request' => $predictionData,
                'mappedData' => $data ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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
                        'classification_id' => $formData['classificationId'] ?? null,
                        'has_management' => $formData['has_management'] ?? null,
                        'category_id' => Category::where('category', $formData['category'])->first()?->id,
                        'size' => $formData['buildingSize'],
                        'year' => $formData['year'],
                        'location_id' => Location::where('location_name', $formData['location_name'])->first()?->id,
                        'structure_id' => (int)$formData['structure'],
                        'cost_preview_way' => $formData['costPreviewWay'],
                        'budget' => $formData['projectBudget'] == null ? null : $formData['projectBudget'],
                        'adjusted_cost' => $costsData['total_cost'],
                        'rating' => $rating,
                        'target_certification' => $formData['certifiedRatingScale'],
                        'created_at' => now(),
                    ]);

                    return $project->id;
                } catch (Exception $e) {
                    Log::error('Failed to create project: ' . $e->getMessage());
                    throw $e;
                }
            });

            // Save costs in its own transaction
            DB::transaction(function () use ($projectId, $costsData) {
                try {
                    $this->saveCostBreakdown($projectId, $costsData['cost_breakdown']);
                } catch (Exception $e) {
                    Log::error('Failed to save cost breakdown: ' . $e->getMessage());
                    throw $e;
                }
            });

            // Save checked items and subitems in its own transaction
            DB::transaction(function () use ($projectId, $checkedItems) {
                try {
                    $this->saveUserAnswers($projectId, $checkedItems);
                } catch (Exception $e) {
                    Log::error('Failed to save user answers: ' . $e->getMessage());
                    throw $e;
                }
            });

            // Add the current user to the project chat as the first member
            DB::transaction(function () use ($projectId, $userId) {
                try {
                    $ownerRoleId = Role::where('name', 'gbi_facilitator')->value('id');

                    ProjectMember::firstOrCreate([
                        'project_id' => $projectId,
                        'user_id'    => $userId,
                    ], [
                        'added_by' => $userId,
                        'role_id'  => $ownerRoleId,
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to add project owner as chat member: ' . $e->getMessage());
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
     * Save cost breakdown hierarchically (supports up to level 2)
     */
    private function saveCostBreakdown($projectId, array $costBreakdown)
    {
        $hasCertificationAtLevelZero = false;
        $lastTopLevelCode = null;

        foreach ($costBreakdown as $code => $data) {
            $lastTopLevelCode = $code;
            $description = $data['description'] ?? '';

            if (strcasecmp(trim($description), 'Certification') === 0) {
                $hasCertificationAtLevelZero = true;
            }

            $parentCost = Cost::create([
                'project_id' => $projectId,
                'code' => $code,
                'description' => $description,
                'item_cost' => $data['cost'] ?? 0,
                'level' => 0,
                'is_certification' => $data['isMultiplier'] ?? false,
            ]);

            // Recursively save children up to max level 2
            if (isset($data['children'])) {
                $this->saveCostChildren($projectId, $data['children'], $parentCost->id, 1);
            }
        }

        if (!$hasCertificationAtLevelZero) {
            Cost::create([
                'project_id' => $projectId,
                'code' => $this->getNextAlphabeticalCode($lastTopLevelCode),
                'description' => 'Certification',
                'item_cost' => 0,
                'level' => 0,
            ]);
        }
    }

    private function getNextAlphabeticalCode(string $code)
    {
        if (empty($code)) {
            return 'A';
        }

        $nextCode = strtoupper($code);
        $nextCode++;

        return $nextCode;
    }

    /**
     * Recursively save cost children (max level = 2)
     */
    private function saveCostChildren(int $projectId, array $children, int $parentId, int $currentLevel)
    {
        if ($currentLevel > 2) {
            return; // Stop recursion at max level 2
        }

        foreach ($children as $code => $data) {
            $cost = Cost::create([
                'project_id' => $projectId,
                'code' => $code,
                'description' => $data['description'] ?? '',
                'item_cost' => $data['cost'] ?? 0,
                'parent_id' => $parentId,
                'level' => $currentLevel,
                'is_certification' => $data['isMultiplier'] ?? false,
            ]);

            // Continue recursion if children exist
            if (isset($data['children'])) {
                $this->saveCostChildren($projectId, $data['children'], $cost->id, $currentLevel + 1);
            }
        }
    }

    /**
     * Save user answers for checked items and subitems
     */
    private function saveUserAnswers(int $projectId, $checkedItems)
    {
        // Save checked items
        if (isset($checkedItems['checkedItems']) && is_array($checkedItems['checkedItems'])) {
            foreach ($checkedItems['checkedItems'] as $itemId) {
                UserAnswer::create([
                    'item_id' => $itemId,
                    'project_id' => $projectId,
                ]);
            }
        }

        if (isset($checkedItems['checkedOptions']) && is_array($checkedItems['checkedOptions'])) {
            foreach ($checkedItems['checkedOptions'] as $optionGroupId => $options) {
                foreach ($options as $optionId) {
                    UserAnswer::create([
                        'option_group_id' => $optionGroupId,
                        'option_id' => $optionId,
                        'project_id' => $projectId,
                    ]);
                }
            }
        }

        // Save checked subitems
        if (isset($checkedItems['checkedSubitems']) && is_array($checkedItems['checkedSubitems'])) {
            foreach ($checkedItems['checkedSubitems'] as $itemId => $subitems) {
                foreach ($subitems as $subitemId) {
                    UserAnswer::create([
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
                        'item_id' => $itemId,
                        'custom_answer' => $custom['description'] ?? '',
                        'project_id' => $projectId,
                    ]);
                }
            }
        }

        if (!empty($checkedItems['selections']) && is_array($checkedItems['selections'])) {
            foreach ($checkedItems['selections'] as $selectionGroupId => $selectedId) {
                if ($selectedId) {
                    UserAnswer::create([
                        'selection_group_id' => $selectionGroupId,
                        'selection_id' => $selectedId,
                        'project_id' => $projectId,
                    ]);
                }
            }
        }

        if (!empty($checkedItems['compulsoryItems']) && is_array($checkedItems['compulsoryItems'])) {
            foreach ($checkedItems['compulsoryItems'] as $itemId) {
                ActualUserAnswer::create([
                    'item_id' => $itemId,
                    'project_id' => $projectId,
                ]);
            }
        }

        if (!empty($checkedItems['compulsory_items']) && is_array($checkedItems['compulsory_items'])) {
            $comp = $checkedItems['compulsory_items'];

            // Items
            if (!empty($comp['items'])) {
                foreach ($comp['items'] as $itemId) {
                    ActualUserAnswer::create([
                        'item_id' => $itemId,
                        'project_id' => $projectId,
                    ]);
                }
            }

            // Subitems (keyed by parent item ID)
            if (!empty($comp['subitems'])) {
                foreach ($comp['subitems'] as $itemId => $subitemIds) {
                    foreach ($subitemIds as $subitemId) {
                        ActualUserAnswer::create([
                            'subitem_id' => $subitemId,
                            'item_id' => $itemId,
                            'project_id' => $projectId,
                        ]);
                    }
                }
            }

            // Options (keyed by optionGroupId)
            if (!empty($comp['options'])) {
                foreach ($comp['options'] as $optionGroupId => $optionIds) {
                    foreach ($optionIds as $optionId) {
                        ActualUserAnswer::create([
                            'option_group_id' => $optionGroupId,
                            'option_id' => $optionId,
                            'project_id' => $projectId,
                        ]);
                    }
                }
            }

            // Selections (keyed by selectionGroupId)
            if (!empty($comp['selections'])) {
                foreach ($comp['selections'] as $selectionGroupId => $selectionIds) {
                    foreach ($selectionIds as $selectionId) {
                        ActualUserAnswer::create([
                            'selection_group_id' => $selectionGroupId,
                            'selection_id' => $selectionId,
                            'project_id' => $projectId,
                        ]);
                    }
                }
            }
        }
    }
}
