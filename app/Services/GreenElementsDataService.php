<?php

namespace App\Services;

use App\Models\Criterion;

class GreenElementsDataService
{
    private const UNCLASSIFIED_GROUP = 'Unclassified';

    private function transformCriteria($criteriaCollection, $classificationId, $hasManagement, $isAdmin = false)
    {
        return $criteriaCollection->map(function ($crit) use ($classificationId, $hasManagement, $isAdmin) {
            // sum from subcriteria if exists
            if ($crit->subcriteria->isNotEmpty()) {
                $totalMarks = $crit->subcriteria->sum(function ($sub) {
                    return $sub->items->sum('marks');
                });
            } else {
                // fallback: sum from direct items
                $totalMarks = $crit->items->sum('marks');
            }

            return [
                "id"          => $crit->id,
                "name"        => $crit->name,
                "total_marks" => $totalMarks,
                "min_marks"   => ceil($totalMarks * $crit->min_percent / 100),

                "subcriteria" => $crit->subcriteria->map(function ($sub) use ($classificationId, $hasManagement, $isAdmin) {
                    return [
                        "id"   => $sub->id,
                        "name" => $sub->name,
                        "items" => $sub->items->map(function ($item) use ($classificationId, $hasManagement, $isAdmin) {
                            return $this->transformItem($item, $classificationId, $hasManagement, $isAdmin);
                        })
                    ];
                }),

                "items" => $crit->items->map(function ($item) use ($classificationId, $hasManagement, $isAdmin) {
                    return $this->transformItem($item, $classificationId, $hasManagement, $isAdmin);
                })
            ];
        });
    }

    private function transformItem($item, $classificationId = null, $hasManagement = null, $isAdmin)
    {
        $classificationUse = $item->uses_classification;

        $optionGroups = $item->optionGroups;
        $selectionGroups = $item->selectionGroups;

        if ($classificationUse) {
            if ($classificationId && !$isAdmin) {
                $selectionGroups = $selectionGroups
                    ->map(function ($group) use ($classificationId) {
                        $group->setRelation(
                            'selections',
                            $group->selections->filter(function ($selection) use ($classificationId) {
                                return $selection->classification_id == $classificationId;
                            })->values()
                        );

                        return $group;
                    })
                    ->filter(function ($group) {
                        return $group->selections->isNotEmpty();
                    })
                    ->map(function ($group) {
                        return [
                            "id" => $group->id,
                            "label" => $group->label,
                            "exclusive" => $group->exclusive ? true : false,
                            "selections" => $group->selections->map(function ($sel) {
                                return [
                                    "id" => $sel->id,
                                    "description" => $sel->description,
                                    "marks" => $sel->marks
                                ];
                            })->values()
                        ];
                    })
                    ->values();
            } else {
                $selectionGroups = $item->selections
                    ->groupBy(fn($selection) => optional($selection->classification)->name ?? self::UNCLASSIFIED_GROUP)
                    ->map(fn($selections) => $selections->values());
            }
        }

        $optionGroups = $optionGroups
            ->map(function ($group) use ($classificationUse, $classificationId, $hasManagement, $isAdmin) {

                $options = $group->options;

                // 1. Management filtering
                if (!$isAdmin && $group->uses_management) {
                    $options = $options->filter(function ($option) use ($hasManagement) {
                        return $hasManagement
                            ? $option->with_management
                            : !$option->with_management;
                    });
                }

                // 2. Classification filtering
                if ($classificationUse && !$isAdmin && $classificationId) {
                    $options = $options->filter(
                        fn($option) => $option->classification_id == $classificationId
                    );
                }

                // 3. Admin logic
                if ($isAdmin && $group->uses_management) {
                    $options = $options
                        ->filter(fn($option) => !is_null($option->with_management))
                        ->values();

                    if ($options->isEmpty()) {
                        return null;
                    }

                    $group->label = $options->first()->with_management
                        ? 'Building with Common Management'
                        : 'Building without Common Management';
                }

                // 4. Transform options (non-admin)
                if (!$isAdmin) {
                    $options = $options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'description' => $option->description,
                            'sub_description' => $option->sub_description,
                            'marks' => $option->marks,
                        ];
                    });
                }

                // keep as object here
                $group->setRelation('options', $options->values());

                return $group;
            })
            ->filter(function ($group) {
                return $group && $group->options->isNotEmpty(); // ✅ still object
            })
            ->map(function ($group) {
                return [
                    "id" => $group->id,
                    "label" => $group->label,
                    "options" => $group->options
                ];
            })
            ->values();

        return [
            "id"             => $item->id,
            "description"    => $item->description,
            "info"           => $item->info,
            "marks"          => $item->marks,
            "is_compulsory"  => $item->is_compulsory,
            "suggestions"    => $item->suggestions,
            "esg"            => $item->esg,
            "subitems_exist" => $item->subitems_exist,
            "selection_groups" => $selectionGroups,
            "option_groups"    => $optionGroups,
            "subitems"       => $item->subitems->map(function ($subitem) {
                return [
                    "id"          => $subitem->id,
                    "description" => $subitem->description
                ];
            })
        ];
    }

    public function getGreenElementsData($buildingType, $classificationId = null, $hasManagement = null, $isAdmin)
    {
        $criteria = Criterion::with([
            'subcriteria.items.selectionGroups.selections.classification',
            'subcriteria.items.optionGroups.options.classification',
            'subcriteria.items.subitems',
            'items.selectionGroups.selections.classification',
            'items.optionGroups.options.classification',
            'items.subitems',
        ])
            ->where('building_type_id', $buildingType)
            ->get();

        return $this->transformCriteria($criteria, $classificationId, $hasManagement, $isAdmin);
    }
}
