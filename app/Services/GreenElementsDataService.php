<?php

namespace App\Services;

use App\Models\Criterion;

class GreenElementsDataService
{
    private function transformCriteria($criteriaCollection)
    {
        return $criteriaCollection->map(function ($crit) {
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

                "subcriteria" => $crit->subcriteria->map(function ($sub) {
                    return [
                        "id"   => $sub->id,
                        "name" => $sub->name,
                        "items" => $sub->items->map(function ($item) {
                            return $this->transformItem($item);
                        })
                    ];
                }),

                "items" => $crit->items->map(function ($item) {
                    return $this->transformItem($item);
                })
            ];
        });
    }

    private function transformItem($item)
    {
        return [
            "id"             => $item->id,
            "description"    => $item->description,
            "info"           => $item->info,
            "marks"          => $item->marks,
            "suggestions"    => $item->suggestions,
            "esg"            => $item->esg,
            "subitems_exist" => $item->subitems_exist,
            "subitems"       => $item->subitems->map(function ($subitem) {
                return [
                    "id"          => $subitem->id,
                    "description" => $subitem->description
                ];
            })
        ];
    }

    public function getGreenElementsData($buildingType)
    {
        $criteria = Criterion::with([
            'subcriteria.items.subitems',
            'items.subitems',
        ])
            ->where('building_type_id', $buildingType)
            ->get();

        return $this->transformCriteria($criteria);
    }
}
