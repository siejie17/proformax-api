<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssessmentScoreService
{
    public function breakdown(Project $project): array
    {
        $subcriterionColumn = Schema::hasColumn('items', 'subcriterion_id') ? 'subcriterion_id' : 'subcriteria_id';
        $criterionColumn = Schema::hasColumn('subcriteria', 'criterion_id') ? 'criterion_id' : 'criteria_id';
        $hasDirectCriterion = Schema::hasColumn('items', 'criterion_id');

        $query = DB::table('items')
            ->leftJoin('subcriteria', "items.{$subcriterionColumn}", '=', 'subcriteria.id')
            ->leftJoin('criteria as sub_criteria', "subcriteria.{$criterionColumn}", '=', 'sub_criteria.id');

        if ($hasDirectCriterion) {
            $query->leftJoin('criteria as direct_criteria', 'items.criterion_id', '=', 'direct_criteria.id')
                ->where(function ($inner) use ($project) {
                    $inner->where('direct_criteria.building_type_id', $project->building_type_id)
                        ->orWhere('sub_criteria.building_type_id', $project->building_type_id);
                })
                ->selectRaw('items.id, items.description, items.info, items.esg, items.suggestions, items.marks, items.subitems_exist, COALESCE(direct_criteria.name, sub_criteria.name) as criterion_name, subcriteria.name as subcriterion_name');
        } else {
            $query->where('sub_criteria.building_type_id', $project->building_type_id)
                ->selectRaw('items.id, items.description, items.info, items.esg, items.suggestions, items.marks, items.subitems_exist, sub_criteria.name as criterion_name, subcriteria.name as subcriterion_name');
        }

        $items = $query->orderBy('items.id')->get();
        $itemIds = $items->pluck('id');

        if ($itemIds->isEmpty()) return $this->emptyBreakdown($project);

        $selectionGroupItems = DB::table('selection_groups')->whereIn('item_id', $itemIds)->pluck('item_id', 'id');
        $optionGroupItems = DB::table('option_groups')->whereIn('item_id', $itemIds)->pluck('item_id', 'id');
        $selectionQuery = DB::table('selections')->whereIn('selection_group_id', $selectionGroupItems->keys());
        $optionQuery = DB::table('options')->whereIn('option_group_id', $optionGroupItems->keys());
        if ($project->classification_id) {
            $selectionQuery->where(fn ($query) => $query->whereNull('classification_id')->orWhere('classification_id', $project->classification_id));
            $optionQuery->where(fn ($query) => $query->whereNull('classification_id')->orWhere('classification_id', $project->classification_id));
        }
        $selectionDetails = $selectionQuery->get()->keyBy('id');
        $optionDetails = $optionQuery->get()->keyBy('id');
        $subitemDetails = DB::table('subitems')->whereIn('item_id', $itemIds)->get()->keyBy('id');
        $maxScores = $items->mapWithKeys(fn ($item) => [(int) $item->id => max(0, (int) $item->marks)]);

        $predicted = $this->summarizeAnswers(
            DB::table('user_answers')->where('project_id', $project->id)->get(),
            $selectionGroupItems, $optionGroupItems, $selectionDetails, $optionDetails, $subitemDetails, $maxScores,
        );
        $actual = $this->summarizeAnswers(
            DB::table('actual_user_answers')->where('project_id', $project->id)->get(),
            $selectionGroupItems, $optionGroupItems, $selectionDetails, $optionDetails, $subitemDetails, $maxScores,
        );

        $configuredChoices = [];
        foreach ($subitemDetails as $subitem) {
            $configuredChoices[(int) $subitem->item_id][] = [
                'choice_key' => 'subitem:' . $subitem->id,
                'label' => $subitem->description,
                'score' => 1,
            ];
        }
        foreach ($selectionDetails as $selection) {
            $choiceItemId = (int) ($selectionGroupItems[$selection->selection_group_id] ?? 0);
            if (! $choiceItemId) continue;
            $configuredChoices[$choiceItemId][] = [
                'choice_key' => 'selection:' . $selection->id,
                'label' => $selection->description,
                'score' => (int) $selection->marks,
            ];
        }
        foreach ($optionDetails as $option) {
            $choiceItemId = (int) ($optionGroupItems[$option->option_group_id] ?? 0);
            if (! $choiceItemId) continue;
            $configuredChoices[$choiceItemId][] = [
                'choice_key' => 'option:' . $option->id,
                'label' => $option->description,
                'score' => (int) $option->marks,
            ];
        }

        $reviews = DB::table('assessment_item_reviews')
            ->leftJoin('users', 'users.id', '=', 'assessment_item_reviews.reviewed_by')
            ->where('assessment_item_reviews.project_id', $project->id)
            ->where('assessment_item_reviews.review_basis', 'actual')
            ->whereIn('assessment_item_reviews.item_id', $itemIds)
            ->select([
                'assessment_item_reviews.*',
                'users.first_name as reviewer_first_name',
                'users.last_name as reviewer_last_name',
            ])
            ->get()
            ->keyBy('item_id');

        $evidence = DB::table('attachments')
            ->where('project_id', $project->id)
            ->whereIn('assessment_item_id', $itemIds)
            ->orderBy('uploaded_at')
            ->get(['id', 'assessment_item_id', 'original_name', 'filename', 'kind', 'size', 'uploaded_at'])
            ->groupBy('assessment_item_id');

        $predictedTotal = 0;
        $actualTotal = 0;
        $reviewedItems = 0;
        $rows = [];

        foreach ($items as $item) {
            $itemId = (int) $item->id;
            $maxScore = $maxScores[$itemId];
            $predictedScore = min($predicted['scores'][$itemId] ?? 0, $maxScore);
            $submittedActualScore = min($actual['scores'][$itemId] ?? 0, $maxScore);
            $review = $reviews->get($itemId);
            $predictedAnswers = collect($predicted['answers'][$itemId] ?? []);
            $predictedChoices = collect($configuredChoices[$itemId] ?? []);
            foreach ($predictedAnswers as $answer) {
                if (! $predictedChoices->contains('choice_key', $answer['choice_key'])) {
                    $predictedChoices->push([
                        'choice_key' => $answer['choice_key'],
                        'label' => $answer['label'],
                        'score' => $answer['score'],
                    ]);
                }
            }
            if ($predictedChoices->isEmpty()) {
                $predictedChoices->push([
                    'choice_key' => 'item:' . $itemId,
                    'label' => $item->description,
                    'score' => $maxScore,
                ]);
            }
            $predictedSelectedKeys = $predictedAnswers->pluck('choice_key')->unique();
            $predictedChoices = $predictedChoices->map(fn ($choice) => [
                ...$choice,
                'label' => $choice['choice_key'] === 'item:' . $itemId ? $item->description : $choice['label'],
                'selected' => $predictedSelectedKeys->contains($choice['choice_key']),
            ])->values();

            $actualAnswers = collect($actual['answers'][$itemId] ?? []);
            $actualChoices = collect($configuredChoices[$itemId] ?? []);
            foreach ($actualAnswers as $answer) {
                if (! $actualChoices->contains('choice_key', $answer['choice_key'])) {
                    $actualChoices->push([
                        'choice_key' => $answer['choice_key'],
                        'label' => $answer['label'],
                        'score' => $answer['score'],
                    ]);
                }
            }
            if ($actualChoices->isEmpty()) {
                $actualChoices->push([
                    'choice_key' => 'item:' . $itemId,
                    'label' => $item->description,
                    'score' => $maxScore,
                ]);
            }
            $submittedChoiceKeys = $actualAnswers->pluck('choice_key')->unique()->sort()->values();

            if ($review) {
                if ($review->submitted_actual_choice_keys !== null) {
                    $reviewedSubmissionKeys = collect(json_decode((string) $review->submitted_actual_choice_keys, true) ?: [])->map(fn ($key) => (string) $key)->unique()->sort()->values();
                    if ($reviewedSubmissionKeys->all() !== $submittedChoiceKeys->all()) $review = null;
                } elseif ((int) $review->original_score !== $submittedActualScore) {
                    $review = null;
                }
            }

            if ($review && $review->accepted_actual_choice_keys !== null) {
                $acceptedChoiceKeys = collect(json_decode((string) $review->accepted_actual_choice_keys, true) ?: [])->map(fn ($key) => (string) $key);
            } elseif ($review && $review->accepted_actual_answer_ids !== null) {
                $acceptedAnswerIds = collect(json_decode((string) $review->accepted_actual_answer_ids, true) ?: [])->map(fn ($id) => (int) $id);
                $acceptedChoiceKeys = $actualAnswers->whereIn('answer_id', $acceptedAnswerIds)->pluck('choice_key');
            } elseif ($review) {
                $remainingScore = (int) $review->reviewed_score;
                $acceptedChoiceKeys = $actualChoices->filter(function ($choice) use (&$remainingScore) {
                    if ($choice['score'] > $remainingScore) return false;
                    $remainingScore -= $choice['score'];

                    return true;
                })->pluck('choice_key');
            } else {
                $acceptedChoiceKeys = collect();
            }
            $actualChoices = $actualChoices->map(fn ($choice) => [
                ...$choice,
                'label' => $choice['choice_key'] === 'item:' . $itemId ? $item->description : $choice['label'],
                'submitted' => $submittedChoiceKeys->contains($choice['choice_key']),
                'accepted' => $acceptedChoiceKeys->contains($choice['choice_key']),
            ])->values();
            $actualScore = min($actualChoices->where('accepted', true)->sum('score'), $maxScore);
            $predictedTotal += $predictedScore;
            $actualTotal += $actualScore;
            $reviewedItems += $review ? 1 : 0;

            $rows[] = [
                'item_id' => $itemId,
                'criterion' => $item->criterion_name,
                'subcriterion' => $item->subcriterion_name,
                'description' => $item->description,
                'info' => $item->info,
                'esg' => $item->esg,
                'suggestions' => $item->suggestions,
                'max_score' => $maxScore,
                'predicted_score' => $predictedScore,
                'predicted_selections' => $predicted['selections'][$itemId] ?? [],
                'predicted_choices' => $predictedChoices->all(),
                'submitted_actual_score' => $submittedActualScore,
                'actual_score' => $actualScore,
                'actual_selections' => $actual['selections'][$itemId] ?? [],
                'actual_choices' => $actualChoices->all(),
                'remarks' => $review?->remarks,
                'evidence' => collect($evidence->get($itemId, []))->map(fn ($attachment) => [
                    'id' => (int) $attachment->id,
                    'original_name' => $attachment->original_name,
                    'filename' => $attachment->filename,
                    'kind' => $attachment->kind,
                    'size' => (int) $attachment->size,
                    'uploaded_at' => $attachment->uploaded_at,
                ])->values()->all(),
                'review_status' => $review ? 'reviewed' : 'pending',
                'reviewed_at' => $review?->updated_at,
                'reviewed_by' => $review ? [
                    'id' => $review->reviewed_by,
                    'first_name' => $review->reviewer_first_name,
                    'last_name' => $review->reviewer_last_name,
                ] : null,
            ];
        }

        $allActualReviewed = $reviewedItems === count($rows);

        return [
            'items' => $rows,
            'predicted_total' => $predictedTotal,
            'actual_total' => $actualTotal,
            'reviewed_items' => $reviewedItems,
            'total_items' => count($rows),
            'all_actual_reviewed' => $allActualReviewed,
            'calculated_certification_level' => $allActualReviewed ? $this->certificationLevel($project, $actualTotal) : null,
            'certification_status' => $project->assessment_status === 'certified' ? 'certified' : 'not_certified',
        ];
    }

    public function certificationLevel(Project $project, int $approvedActualTotal): ?string
    {
        $buildingTypeColumn = Schema::hasColumn('certifications', 'building_type_id') ? 'building_type_id' : 'type_id';
        $levels = DB::table('certifications')
            ->where($buildingTypeColumn, $project->building_type_id)
            ->orderByDesc('min_score')
            ->get(['name', 'min_score', 'max_score']);

        if ($levels->isEmpty()) return null;

        $match = $levels->first(fn ($level) => $approvedActualTotal >= (int) $level->min_score && $approvedActualTotal <= (int) $level->max_score);
        return $match?->name ?? 'Not Certified';
    }

    private function summarizeAnswers(
        Collection $answers,
        Collection $selectionGroupItems,
        Collection $optionGroupItems,
        Collection $selectionDetails,
        Collection $optionDetails,
        Collection $subitemDetails,
        Collection $maxScores,
    ): array {
        $scores = [];
        $selections = [];
        $answerRows = [];
        $directItems = [];

        foreach ($answers as $answer) {
            $itemId = $answer->item_id ? (int) $answer->item_id : null;

            if ($answer->custom_answer !== null && trim((string) $answer->custom_answer) !== '') {
                if (! $itemId) continue;
                $scores[$itemId] = ($scores[$itemId] ?? 0) + 1;
                $label = 'Custom: ' . trim((string) $answer->custom_answer);
                $selections[$itemId][] = $label;
                $answerRows[$itemId][] = ['answer_id' => (int) $answer->id, 'choice_key' => 'custom:' . $answer->id, 'label' => $label, 'score' => 1];
            } elseif ($answer->option_id) {
                $itemId = (int) ($optionGroupItems[$answer->option_group_id] ?? 0);
                $option = $optionDetails->get($answer->option_id);
                if (! $itemId || ! $option) continue;
                $score = (int) $option->marks;
                $label = $option->description . ' (' . $score . ' marks)';
                $scores[$itemId] = ($scores[$itemId] ?? 0) + $score;
                $selections[$itemId][] = $label;
                $answerRows[$itemId][] = ['answer_id' => (int) $answer->id, 'choice_key' => 'option:' . $option->id, 'label' => $option->description, 'score' => $score];
            } elseif ($answer->selection_id) {
                $itemId = (int) ($selectionGroupItems[$answer->selection_group_id] ?? 0);
                $selection = $selectionDetails->get($answer->selection_id);
                if (! $itemId || ! $selection) continue;
                $score = (int) $selection->marks;
                $label = $selection->description . ' (' . $score . ' marks)';
                $scores[$itemId] = ($scores[$itemId] ?? 0) + $score;
                $selections[$itemId][] = $label;
                $answerRows[$itemId][] = ['answer_id' => (int) $answer->id, 'choice_key' => 'selection:' . $selection->id, 'label' => $selection->description, 'score' => $score];
            } elseif ($answer->subitem_id) {
                if (! $itemId) continue;
                $scores[$itemId] = ($scores[$itemId] ?? 0) + 1;
                $label = $subitemDetails->get($answer->subitem_id)?->description ?? 'Selected subitem';
                $selections[$itemId][] = $label;
                $answerRows[$itemId][] = ['answer_id' => (int) $answer->id, 'choice_key' => 'subitem:' . $answer->subitem_id, 'label' => $label, 'score' => 1];
            } elseif ($itemId) {
                $directItems[$itemId][] = (int) $answer->id;
            }
        }

        foreach ($directItems as $itemId => $answerIds) {
            $scores[$itemId] = (int) ($maxScores[$itemId] ?? 0);
            $selections[$itemId][] = 'Selected';
            foreach ($answerIds as $index => $answerId) {
                $answerRows[$itemId][] = ['answer_id' => $answerId, 'choice_key' => 'item:' . $itemId, 'label' => 'Selected', 'score' => $index === 0 ? $scores[$itemId] : 0];
            }
        }

        foreach ($selections as $itemId => $values) {
            $selections[$itemId] = array_values(array_unique($values));
        }

        return ['scores' => $scores, 'selections' => $selections, 'answers' => $answerRows];
    }

    private function emptyBreakdown(Project $project): array
    {
        return [
            'items' => [],
            'predicted_total' => 0,
            'actual_total' => 0,
            'reviewed_items' => 0,
            'total_items' => 0,
            'all_actual_reviewed' => false,
            'calculated_certification_level' => null,
            'certification_status' => $project->assessment_status === 'certified' ? 'certified' : 'not_certified',
        ];
    }
}
