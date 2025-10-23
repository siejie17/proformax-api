<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\UserAnswer;
use App\Services\GreenElementsDataService;
use Exception;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected $greenElementsData;

    public function __construct(GreenElementsDataService $greenElementsData)
    {
        $this->greenElementsData = $greenElementsData;
    }

    /**
     * Get all projects related to a specific user ID
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserProjects($userId)
    {
        try {
            $projects = Project::where('user_id', $userId)
                ->with([
                    'buildingType:id,code',
                    'structure:id,name'
                ])
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'building_type' => $project->buildingType?->code,
                        'structure' => $project->structure?->name,
                        'category' => $project->category,
                        'size' => $project->size,
                        'year' => $project->year,
                        'location' => $project->location,
                        'budget' => $project->budget,
                        'adjusted_cost' => $project->adjusted_cost,
                        'rating' => $project->rating,
                        'target_certification' => $project->target_certification,
                        'created_at' => $project->created_at
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
                'apiData' => $projects
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving projects: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get project details by project ID
     *
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showSelectedProject($projectId)
    {
        try {
            $project = Project::with([
                'buildingType:id,code,name',
                'structure:id,name',
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

            // Split user answers into three categories
            $splitAnswers = $this->splitUserAnswers($userAnswers);

            // Format costs in hierarchical structure
            $costBreakdown = $this->formatCostBreakdown($project->costs);

            // Format project data with selected fields from related models
            $projectData = $project->toArray();
            unset($projectData['costs']); // Remove unformatted costs
            unset($projectData['user_id']); // Remove user info
            unset($projectData['structure_id']);

            $greenElements = $this->greenElementsData->getGreenElementsData($projectData['building_type_id']);

            unset($projectData['building_type_id']); // Remove building_type_id after use

            $projectData['building_type'] = $project->buildingType?->code;
            $projectData['building_type_name'] = $project->buildingType?->name;
            $projectData['structure'] = $project->structure?->name;

            return response()->json([
                'success' => true,
                'data' => array_merge($projectData, [
                    'checked_items' => $splitAnswers['checkedItems'],
                    'checked_subitems' => $splitAnswers['checkedSubitems'],
                    'custom_inputs' => $splitAnswers['customInputs'],
                    'cost_breakdown' => $costBreakdown,
                ]),
                'green_elements' => $greenElements
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving project: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Split user answers into three categories: checkedItems, checkedSubitems, and customInputs
     *
     * @param \Illuminate\Database\Eloquent\Collection $userAnswers
     * @return array
     */
    private function splitUserAnswers($userAnswers)
    {
        $checkedItems = [];
        $checkedSubitems = [];
        $customInputs = [];

        foreach ($userAnswers as $answer) {
            // If it has a custom answer, add to customInputs
            if (!empty($answer->custom_answer)) {
                if (!isset($customInputs[$answer->item_id])) {
                    $customInputs[$answer->item_id] = [];
                }
                $customInputs[$answer->item_id][] = $answer->custom_answer;
            }
            // If it has a subitem, add to checkedSubitems
            elseif ($answer->subitem_id) {
                if (!isset($checkedSubitems[$answer->item_id])) {
                    $checkedSubitems[$answer->item_id] = [];
                }
                $checkedSubitems[$answer->item_id][] = $answer->subitem_id;
            }
            // Otherwise, add to checkedItems (only item_id)
            else {
                if (!in_array($answer->item_id, $checkedItems)) {
                    $checkedItems[] = $answer->item_id;
                }
            }
        }

        return [
            'checkedItems' => $checkedItems,
            'checkedSubitems' => $checkedSubitems,
            'customInputs' => $customInputs,
        ];
    }

    /**
     * Format costs into hierarchical structure like prevProjects.php
     *
     * @param \Illuminate\Database\Eloquent\Collection $costs
     * @return array
     */
    private function formatCostBreakdown($costs)
    {
        $costBreakdown = [];

        // Get only parent level costs (level = 0)
        $parentCosts = $costs->where('level', 0);

        foreach ($parentCosts as $parentCost) {
            $costData = [
                'description' => $parentCost->description,
                'cost' => 0.0
            ];

            // Check if parent has children
            $children = $costs->where('parent_id', $parentCost->id);

            if ($children->count() > 0) {
                $costData['children'] = [];
                foreach ($children as $child) {
                    $costData['children'][$child->code] = [
                        'description' => $child->description,
                        'cost' => (float) $child->item_cost,
                    ];
                    // Accumulate parent cost
                    $costData['cost'] += (float) $child->item_cost;
                }
            } else {
                // If no children, add cost directly
                $costData['cost'] = (float) $parentCost->item_cost;
            }

            $costBreakdown[$parentCost->code] = $costData;
        }

        return $costBreakdown;
    }

    public function getGreenElementsData(int $buildingTypeId)
    {
        try {
            return $this->greenElementsData->getGreenElementsData($buildingTypeId);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving green elements data: ' . $e->getMessage()
            ], 500);
        }
    }
}
