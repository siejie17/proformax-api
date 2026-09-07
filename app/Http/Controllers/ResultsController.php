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

            // Resolve lookup ids once, before the transaction, so they are
            // reused for the whole request instead of querying inside a loop.
            $categoryId = Category::where('category', $formData['category'])->value('id');
            $locationId = Location::where('location_name', $formData['location_name'])->value('id');
            $ownerRoleId = Role::where('name', 'gbi_facilitator')->value('id');

            // Single transaction wrapping every write so the whole submission
            // either commits or rolls back atomically (fewer round-trips too).
            $projectId = DB::transaction(function () use (
                $userId,
                $rating,
                $formData,
                $costsData,
                $checkedItems,
                $categoryId,
                $locationId,
                $ownerRoleId
            ) {
                $project = Project::create([
                    'user_id' => $userId,
                    'name' => $formData['projectName'],
                    'building_type_id' => $formData['buildingType'],
                    'classification_id' => $formData['classificationId'] ?? null,
                    'has_management' => $formData['has_management'] ?? null,
                    'category_id' => $categoryId,
                    'size' => $formData['buildingSize'],
                    'year' => $formData['year'],
                    'location_id' => $locationId,
                    'structure_id' => (int)$formData['structure'],
                    'cost_preview_way' => $formData['costPreviewWay'],
                    'budget' => $formData['projectBudget'] == null ? null : $formData['projectBudget'],
                    'adjusted_cost' => $costsData['total_cost'],
                    'rating' => $rating,
                    'target_certification' => $formData['certifiedRatingScale'],
                    'changed_cert' => $formData['changedCert'] ?? false,
                    'created_at' => now(),
                ]);

                $projectId = $project->id;

                // Save costs (bulk inserts).
                $this->saveCostBreakdown($projectId, $costsData['cost_breakdown']);

                // Save checked items and subitems (bulk inserts).
                if (
                    $formData['certifiedRatingScale'] !== "Not Certified"
                    || $formData['changedCert'] === true
                ) {
                    $this->saveUserAnswers($projectId, $checkedItems);
                }

                // Add the current user to the project chat as the first member.
                ProjectMember::firstOrCreate([
                    'project_id' => $projectId,
                    'user_id'    => $userId,
                ], [
                    'added_by' => $userId,
                    'role_id'  => $ownerRoleId,
                ]);

                return $projectId;
            });

            return response()->json([
                'success' => true,
                'message' => 'Assessment submitted successfully.',
                'project_id' => $projectId
            ], 201);
        } catch (Exception $e) {
            Log::error('Error submitting assessment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error submitting assessment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save cost breakdown hierarchically (supports up to level 2),
     * using bulk inserts per level so a large tree is written with only a
     * handful of INSERT statements instead of one query per node.
     */
    private function saveCostBreakdown($projectId, array $costBreakdown)
    {
        $hasCertificationAtLevelZero = false;
        $lastTopLevelCode = null;

        // Level 0 rows, inserted in one statement.
        $level0 = [];
        foreach ($costBreakdown as $code => $data) {
            $lastTopLevelCode = $code;
            $description = $data['description'] ?? '';

            if (strcasecmp(trim($description), 'Certification') === 0) {
                $hasCertificationAtLevelZero = true;
            }

            $level0[] = [
                'project_id' => $projectId,
                'code' => $code,
                'description' => $description,
                'item_cost' => $data['cost'] ?? 0,
                'actual_cost' => $data['cost'] ?? 0,
                'level' => 0,
                'is_certification' => $data['isMultiplier'] ?? false,
            ];
        }

        // Each element gets its own row; collect ids per stable code.
        Cost::insert($level0);
        $parentRows = Cost::where('project_id', $projectId)
            ->where('level', 0)
            ->get()
            ->keyBy('code');

        foreach ($costBreakdown as $code => $data) {
            $parent = $parentRows->get($code);
            if ($parent && isset($data['children'])) {
                $this->saveCostChildren($projectId, $data['children'], $parent->id, 1);
            }
        }

        if (!$hasCertificationAtLevelZero) {
            Cost::insert([
                'project_id' => $projectId,
                'code' => $this->getNextAlphabeticalCode($lastTopLevelCode),
                'description' => 'Certification',
                'item_cost' => 0,
                'level' => 0,
                'is_certification' => true,
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
     * Recursively bulk-save cost children (max level = 2).
     *
     * Rows for a given parent are inserted together, then the freshly
     * inserted child rows are read back (scoped to this project + level) so
     * their real ids can be used as parent ids for the next level — still
     * just one INSERT + one SELECT per level rather than one query per node.
     */
    private function saveCostChildren(int $projectId, array $children, int $parentId, int $currentLevel)
    {
        if ($currentLevel > 2) {
            return;
        }

        $insertRows = [];
        foreach ($children as $code => $data) {
            $insertRows[] = [
                'project_id' => $projectId,
                'code' => $code,
                'description' => $data['description'] ?? '',
                'item_cost' => $data['cost'] ?? 0,
                'actual_cost' => $data['cost'] ?? 0,
                'parent_id' => $parentId,
                'level' => $currentLevel,
                'is_certification' => $data['isMultiplier'] ?? false,
            ];
        }

        if (empty($insertRows)) {
            return;
        }

        Cost::insert($insertRows);

        // Read back the children we just inserted so we get their ids.
        $inserted = Cost::where('project_id', $projectId)
            ->where('parent_id', $parentId)
            ->get()
            ->keyBy('code');

        // Recurse one more level if any child has grandchildren.
        foreach ($children as $code => $data) {
            if (isset($data['children'], $inserted[$code])) {
                $this->saveCostChildren(
                    $projectId,
                    $data['children'],
                    $inserted[$code]->id,
                    $currentLevel + 1
                );
            }
        }
    }

    /**
     * Save user answers for checked items, subitems, options, selections,
     * custom inputs and compulsory items — all via bulk inserts.
     */
    private function saveUserAnswers(int $projectId, $checkedItems)
    {
        $itemAnswers = [];
        $optionAnswers = [];
        $subitemAnswers = [];
        $customAnswers = [];
        $selectionAnswers = [];

        if (!empty($checkedItems['checkedItems']) && is_array($checkedItems['checkedItems'])) {
            foreach ($checkedItems['checkedItems'] as $itemId) {
                $itemAnswers[] = ['item_id' => $itemId, 'project_id' => $projectId];
            }
        }

        if (!empty($checkedItems['checkedOptions']) && is_array($checkedItems['checkedOptions'])) {
            foreach ($checkedItems['checkedOptions'] as $optionGroupId => $options) {
                foreach ($options as $optionId) {
                    $optionAnswers[] = [
                        'option_group_id' => $optionGroupId,
                        'option_id' => $optionId,
                        'project_id' => $projectId,
                    ];
                }
            }
        }

        if (!empty($checkedItems['checkedSubitems']) && is_array($checkedItems['checkedSubitems'])) {
            foreach ($checkedItems['checkedSubitems'] as $itemId => $subitems) {
                foreach ($subitems as $subitemId) {
                    $subitemAnswers[] = [
                        'subitem_id' => $subitemId,
                        'item_id' => $itemId,
                        'project_id' => $projectId,
                    ];
                }
            }
        }

        if (!empty($checkedItems['customItems']) && is_array($checkedItems['customItems'])) {
            foreach ($checkedItems['customItems'] as $itemId => $customItems) {
                foreach ($customItems as $custom) {
                    $customAnswers[] = [
                        'item_id' => $itemId,
                        'custom_answer' => $custom['description'] ?? '',
                        'project_id' => $projectId,
                    ];
                }
            }
        }

        if (!empty($checkedItems['selections']) && is_array($checkedItems['selections'])) {
            foreach ($checkedItems['selections'] as $selectionGroupId => $selectedId) {
                if ($selectedId) {
                    $selectionAnswers[] = [
                        'selection_group_id' => $selectionGroupId,
                        'selection_id' => $selectedId,
                        'project_id' => $projectId,
                    ];
                }
            }
        }

        foreach ([$itemAnswers, $optionAnswers, $subitemAnswers, $customAnswers, $selectionAnswers] as $answers) {
            if (!empty($answers)) {
                UserAnswer::insert($answers);
            }
        }
    }
}
