<?php

namespace App\Http\Controllers;

use App\Models\ActualUserAnswer;
use App\Models\Cost;
use App\Models\Project;
use App\Models\UserAnswer;
use App\Models\Location;
use App\Services\GreenElementsDataService;
use App\Services\FormDataMappingService;
use Exception;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected $greenElementsData;
    protected $formDataMappingService;

    public function __construct(
        GreenElementsDataService $greenElementsData,
        FormDataMappingService $formDataMappingService
    ) {
        $this->greenElementsData = $greenElementsData;
        $this->formDataMappingService = $formDataMappingService;
    }

    /**
     * Retrieve all projects for a specific user.
     * Returns a paginated list with basic project information and available certifications.
     */
    public function getUserProjects($userId)
    {
        try {
            $projects = Project::where('user_id', $userId)
                ->where('cost_preview_way', 'Detailed')
                ->with([
                    'buildingType:id,code',
                    'structure:id,name',
                    'classification:id,name',
                    'location:id,location_name',
                ])
                ->latest()
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'building_type' => $project->buildingType?->code,
                        'classification' => $project->classification?->name,
                        'structure' => $project->structure?->name,
                        'category' => $project->category?->category,
                        'size' => $project->size,
                        'year' => $project->year,
                        'location' => $project->location?->location_name,
                        'budget' => $project->budget,
                        'adjusted_cost' => $project->adjusted_cost,
                        'rating' => $project->rating,
                        'target_certification' => $project->target_certification,
                        'created_at' => $project->created_at,
                        'certifications' => $this->formDataMappingService
                            ->getCertifications($project->building_type_id),
                    ];
                });

            if ($projects->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No projects found for this user.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'projectsData' => $projects,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving projects: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieve detailed information for a specific project.
     * Includes project metadata, user answers, actual answers, cost breakdown, and available certifications.
     */
    public function showSelectedProject($projectId)
    {
        try {
            $project = Project::with([
                'buildingType:id,code,name',
                'structure:id,name',
                'classification:id,name',
                'category:id,category',
                'location:id,location_name',
                'costs'
            ])->find($projectId);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found.'
                ], 404);
            }

            // Get user answers with related item and subitem details
            $userAnswers = UserAnswer::where('project_id', $projectId)
                ->with(['item', 'subitem'])
                ->get();

            $actualUserAnswers = ActualUserAnswer::where('project_id', $projectId)
                ->with(['item', 'subitem'])
                ->get();

            // Split user answers into three categories
            $splitAnswers = $this->splitUserAnswers($userAnswers);

            $splitActualAnswers = $this->splitUserAnswers($actualUserAnswers);

            // Format costs in hierarchical structure
            $costBreakdown = $this->formatCostBreakdown($project->costs);

            // Format project data with selected fields from related models
            $projectData = $project->toArray();
            unset($projectData['costs']); // Remove unformatted costs
            unset($projectData['user_id']); // Remove user info
            unset($projectData['structure_id']);

            $greenElements = $this->greenElementsData->getGreenElementsData($projectData['building_type_id'], $projectData['classification_id'] ?? null, $projectData['has_management'] ?? null, false);

            $buildingTypeId = $projectData['building_type_id'];
            unset($projectData['building_type_id']); // Remove building_type_id after use

            $projectData['building_type'] = $project->buildingType?->code;
            $projectData['building_type_name'] = $project->buildingType?->name;
            $projectData['classification'] = $project->classification?->name;
            $projectData['structure'] = $project->structure?->name;
            $projectData['category'] = $project->category?->category;

            $parentLocationId = Location::where('id', $project->location?->id)->first()?->parent_location_id;

            if ($parentLocationId != null) {
                $parentLocation = Location::where('id', $parentLocationId)->first()?->location_name;
                $projectData['location'] = $project->location?->location_name . ', ' . $parentLocation;
            } else {
                $projectData['location'] = $project->location?->location_name;
            }

            return response()->json([
                'success' => true,
                'projectData' => array_merge($projectData, [
                    'checked_items' => $splitAnswers['checkedItems'],
                    'checked_subitems' => $splitAnswers['checkedSubitems'],
                    'checked_options' => $splitAnswers['checkedOptions'],
                    'selected_items' => $splitAnswers['selectedItems'],
                    'custom_inputs' => $splitAnswers['customInputs'],
                    'cost_breakdown' => $costBreakdown,
                    'actual_checked_items' => $splitActualAnswers['checkedItems'],
                    'actual_checked_subitems' => $splitActualAnswers['checkedSubitems'],
                    'actual_checked_options' => $splitActualAnswers['checkedOptions'],
                    'actual_selected_items' => $splitActualAnswers['selectedItems'],
                    'actual_custom_inputs' => $splitActualAnswers['customInputs'],
                    'actual_answers_id' => $this->categoriseActualAnswerIds($actualUserAnswers)
                ]),
                'green_elements' => $greenElements,
                'certifications' => $this->formDataMappingService->getCertifications($buildingTypeId)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving project: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Split user answers into categories: checked items, subitems, options, selections, and custom inputs.
     * Organizes answers for easier processing and frontend display.
     */
    private function splitUserAnswers($userAnswers)
    {
        $checkedItems = [];
        $checkedOptions = [];
        $selectedItems = [];
        $checkedSubitems = [];
        $customInputs = [];

        foreach ($userAnswers as $answer) {
            if (!empty($answer->custom_answer)) {
                if (!isset($customInputs[$answer->item_id])) {
                    $customInputs[$answer->item_id] = [];
                }
                $customInputs[$answer->item_id][] = $answer->custom_answer;
            } elseif ($answer->option_id) {
                if (!isset($checkedOptions[$answer->option_group_id])) {
                    $checkedOptions[$answer->option_group_id] = [];
                }
                $checkedOptions[$answer->option_group_id][] = $answer->option_id;
            } elseif ($answer->selection_id) {
                if (!isset($selectedItems[$answer->selection_group_id])) {
                    $selectedItems[$answer->selection_group_id] = $answer->selection_id;
                }
            } elseif ($answer->subitem_id) {
                if (!isset($checkedSubitems[$answer->item_id])) {
                    $checkedSubitems[$answer->item_id] = [];
                }
                $checkedSubitems[$answer->item_id][] = $answer->subitem_id;
            } else {
                if (!in_array($answer->item_id, $checkedItems)) {
                    $checkedItems[] = $answer->item_id;
                }
            }
        }

        return [
            'checkedItems' => $checkedItems,
            'checkedOptions' => $checkedOptions,
            'selectedItems' => $selectedItems,
            'checkedSubitems' => $checkedSubitems,
            'customInputs' => $customInputs,
        ];
    }

    /**
     * Categorize actual answer IDs by type and organize them with their record IDs.
     * Creates a structured mapping of items, options, selections, subitems, and custom entries.
     */
    private function categoriseActualAnswerIds($userAnswers)
    {
        $itemIds = [];
        $optionIds = [];
        $selectedItemIds = [];
        $checkedSubitemIds = [];
        $customInputIds = [];

        foreach ($userAnswers as $answer) {
            if (!empty($answer->custom_answer)) {
                if (!isset($customInputIds[$answer->item_id])) {
                    $customInputIds[$answer->item_id] = [];
                }
                $customInputIds[$answer->item_id][$answer->custom_answer] = $answer->id;
            } elseif ($answer->option_id) {
                if (!isset($optionIds[$answer->option_group_id])) {
                    $optionIds[$answer->option_group_id] = [];
                }
                $optionIds[$answer->option_group_id][$answer->option_id] = $answer->id;
            } elseif ($answer->selection_id) {
                if (!isset($selectedItemIds[$answer->selection_group_id])) {
                    $selectedItemIds[$answer->selection_group_id] = $answer->id;
                }
            } elseif ($answer->subitem_id) {
                if (!isset($checkedSubitemIds[$answer->item_id])) {
                    $checkedSubitemIds[$answer->item_id] = [];
                }
                $checkedSubitemIds[$answer->item_id][$answer->subitem_id] = $answer->id;
            } else {
                if (!in_array($answer->item_id, $itemIds)) {
                    $itemIds[$answer->item_id] = [];
                }
                $itemIds[$answer->item_id] = $answer->id;
            }
        }

        return [
            'items' => $itemIds,
            'options' => $optionIds,
            'selections' => $selectedItemIds,
            'subitems' => $checkedSubitemIds,
            'customEntries' => $customInputIds,
        ];
    }

    /**
     * Format project costs into a hierarchical structure with parent and child cost nodes.
     * Calculates total costs by recursively summing child costs and filters parent-level costs.
     */
    private function formatCostBreakdown($costs)
    {
        $costBreakdown = [];

        // Get only parent level costs (level = 0)
        $parentCosts = $costs->where('level', 0);

        foreach ($parentCosts as $parentCost) {
            $costData = [
                'id' => $parentCost->id,
                'description' => $parentCost->description,
                'cost' => 0.0
            ];

            // Recursively get children and their costs
            $childrenData = $this->getNestedChildren($costs, $parentCost->id);

            if (!empty($childrenData)) {
                $costData['children'] = $childrenData['children'];
                $costData['cost'] = $childrenData['totalCost'];
            } else {
                // If no children, add cost directly
                $costData['cost'] = (float) $parentCost->item_cost;
                $costData['actual_cost'] = (float) $parentCost->actual_cost;
            }

            $costData['is_certification'] = $parentCost->is_certification;

            $costBreakdown[$parentCost->code] = $costData;
        }

        ksort($costBreakdown);

        return $costBreakdown;
    }

    /**
     * Recursively retrieve nested children of a cost node and accumulate their costs.
     * Traverses the cost hierarchy and sums up costs for all descendants.
     */
    private function getNestedChildren($costs, $parentId)
    {
        $children = $costs->where('parent_id', $parentId);

        if ($children->count() === 0) {
            return null;
        }

        $childrenData = [];
        $totalCost = 0.0;

        foreach ($children as $child) {
            $data = [
                'id' => $child->id,
                'description' => $child->description,
                'cost' => 0.0,
            ];

            // Check if this child has its own children
            $nestedChildren = $this->getNestedChildren($costs, $child->id);
            if ($nestedChildren) {
                // Node has children: cost is sum of children only
                $data['children'] = $nestedChildren['children'];
                $data['cost'] = $nestedChildren['totalCost'];
            } else {
                // Leaf node: use its actual item_cost
                $data['cost'] = (float) $child->item_cost;
                $data['actual_cost'] = (float) $child->actual_cost;
            }

            $data['is_certification'] = $child->is_certification;

            $childrenData[$child->code] = $data;
            $totalCost += $data['cost'];
        }

        return [
            'children' => $childrenData,
            'totalCost' => $totalCost
        ];
    }

    /**
     * Update the actual costs for multiple cost nodes in a project.
     * Accepts an array of cost node IDs with their updated actual cost values.
     */
    public function updateActualCost(Request $request)
    {
        $request->validate([
            'changedNodes' => 'required|array'
        ]);

        try {
            $changedNodes = $request->input('changedNodes');

            foreach ($changedNodes as $id => $actualCost) {
                Cost::where('id', $id)->update([
                    'actual_cost' => $actualCost
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Actual costs updated successfully.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating actual cost: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save actual assessment data and certification changes for a project.
     * Stores actual user answers and returns the formatted assessment data.
     */
    public function saveProjectActualChanges($projectId, Request $request)
    {
        $actualChanges = $request->input('actualChanges');

        try {
            // Categorize changes for optimal processing
            $categorized = $this->categorizeActualChanges($actualChanges);

            // Process each category
            $this->processItemChanges($projectId, $categorized['item']);
            $this->processOptionChanges($projectId, $categorized['option']);
            $this->processSelectionChanges($projectId, $categorized['selection']);
            $this->processSubitemChanges($projectId, $categorized['subitem']);
            $this->processCustomChanges($projectId, $categorized['custom']);

            $actualAnswers = ActualUserAnswer::where('project_id', $projectId)
                ->with(['item', 'subitem'])
                ->get();

            $actualAnswers = $this->splitUserAnswers($actualAnswers);

            return response()->json([
                'success' => true,
                'message' => 'Actual assessment saved successfully.',
                'updatedData' => $actualAnswers
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving actual assessment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Categorize actual changes by answer type and action.
     * Returns organized structure: [type => [action => [changes]]]
     */
    private function categorizeActualChanges(array $actualChanges)
    {
        $categorized = [
            'item' => ['add' => [], 'delete' => []],
            'option' => ['add' => [], 'delete' => []],
            'selection' => ['add' => [], 'delete' => [], 'edit' => []],
            'subitem' => ['add' => [], 'delete' => []],
            'custom' => ['add' => [], 'delete' => []]
        ];

        foreach ($actualChanges as $change) {
            $type = $change['answerType'] ?? null;
            $action = $change['action'] ?? null;

            if (!isset($categorized[$type]) || !isset($categorized[$type][$action])) {
                continue;
            }

            $categorized[$type][$action][] = $change;
        }

        return $categorized;
    }

    /**
     * Process item changes: add or delete
     * - Add: requires itemId
     * - Delete: requires userAnswerId
     */
    private function processItemChanges($projectId, array $itemChanges)
    {
        // Handle ADD: Create new UserAnswer
        foreach ($itemChanges['add'] as $change) {
            if (!isset($change['itemId'])) continue;

            ActualUserAnswer::create([
                'project_id' => $projectId,
                'item_id' => $change['itemId']
            ]);
        }

        // Handle DELETE: Remove existing UserAnswer
        foreach ($itemChanges['delete'] as $change) {
            if (!isset($change['userAnswerId'])) continue;

            ActualUserAnswer::where('id', $change['userAnswerId'])->delete();
        }
    }

    /**
     * Process option changes: add or delete
     * - Add: requires optionGroupId, optionId
     * - Delete: requires userAnswerId
     */
    private function processOptionChanges($projectId, array $optionChanges)
    {
        // Handle ADD: Create new UserAnswer with option
        foreach ($optionChanges['add'] as $change) {
            if (!isset($change['optionGroupId'], $change['optionId'])) continue;

            ActualUserAnswer::create([
                'project_id' => $projectId,
                'option_group_id' => $change['optionGroupId'],
                'option_id' => $change['optionId'],
            ]);
        }

        // Handle DELETE: Remove existing UserAnswer
        foreach ($optionChanges['delete'] as $change) {
            if (!isset($change['userAnswerId'])) continue;

            ActualUserAnswer::where('id', $change['userAnswerId'])->delete();
        }
    }

    /**
     * Process selection changes: add, edit, or delete
     * - Add: requires selectionGroupId, selectionId
     * - Edit: requires userAnswerId, previousSelectionId, newSelectionId
     * - Delete: requires userAnswerId
     */
    private function processSelectionChanges($projectId, array $selectionChanges)
    {
        // Handle ADD: Create new UserAnswer with selection
        foreach ($selectionChanges['add'] as $change) {
            if (!isset($change['selectionGroupId'], $change['selectionId'])) continue;

            ActualUserAnswer::create([
                'project_id' => $projectId,
                'selection_group_id' => $change['selectionGroupId'],
                'selection_id' => $change['selectionId'],
            ]);
        }

        // Handle EDIT: Update existing UserAnswer with new selection
        foreach ($selectionChanges['edit'] as $change) {
            if (!isset($change['userAnswerId'], $change['newSelectionId'])) continue;

            ActualUserAnswer::where('id', $change['userAnswerId'])->update([
                'selection_id' => $change['newSelectionId'],
            ]);
        }

        // Handle DELETE: Remove existing UserAnswer
        foreach ($selectionChanges['delete'] as $change) {
            if (!isset($change['userAnswerId'])) continue;

            ActualUserAnswer::where('id', $change['userAnswerId'])->delete();
        }
    }

    /**
     * Process subitem changes: add or delete
     * - Add: requires itemId, subitemId
     * - Delete: requires userAnswerId
     */
    private function processSubitemChanges($projectId, array $subitemChanges)
    {
        // Handle ADD: Create new UserAnswer with subitem
        foreach ($subitemChanges['add'] as $change) {
            if (!isset($change['itemId'], $change['subitemId'])) continue;

            ActualUserAnswer::create([
                'project_id' => $projectId,
                'item_id' => $change['itemId'],
                'subitem_id' => $change['subitemId'],
            ]);
        }

        // Handle DELETE: Remove existing UserAnswer
        foreach ($subitemChanges['delete'] as $change) {
            if (!isset($change['userAnswerId'])) continue;

            ActualUserAnswer::where('id', $change['userAnswerId'])->delete();
        }
    }

    /**
     * Process custom changes: add or delete
     * - Add: requires itemId, customValue
     * - Delete: requires userAnswerId
     */
    private function processCustomChanges($projectId, array $customChanges)
    {
        // Handle ADD: Create new UserAnswer with custom input
        foreach ($customChanges['add'] as $change) {
            if (!isset($change['itemId'], $change['customValue'])) continue;

            ActualUserAnswer::create([
                'project_id' => $projectId,
                'item_id' => $change['itemId'],
                'custom_answer' => $change['customValue'],
            ]);
        }

        // Handle DELETE: Remove existing UserAnswer
        foreach ($customChanges['delete'] as $change) {
            if (!isset($change['userAnswerId'])) continue;

            ActualUserAnswer::where('id', $change['userAnswerId'])->delete();
        }
    }
}
