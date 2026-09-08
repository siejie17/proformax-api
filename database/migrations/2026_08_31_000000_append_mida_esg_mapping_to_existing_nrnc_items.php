<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MIDA_HEADING = '## MIDA ESG';

    public function up(): void
    {
        $criteria = [
            'energy' => ['Environment', 'Energy consumption and efficiency', 'Energy Efficiency credits and BEI performance', 'Annual energy consumption, reduction target and reporting boundary'],
            'renewable' => ['Environment', 'Renewable energy', 'GBI renewable-energy credit', 'Renewable-energy percentage, annual generation and avoided emissions'],
            'pollution' => ['Environment', 'Pollution prevention', 'Refrigerant, IAQ, erosion and construction controls', 'Environmental incidents, emissions and regulatory-compliance records'],
            'water' => ['Environment', 'Water management', 'Water Efficiency credits', 'Annual consumption, discharge, water-risk assessment and reduction targets'],
            'waste' => ['Environment', 'Waste and circularity', 'Materials and Resources credits', 'Operational waste data, recycling rates and hazardous-waste procedures'],
            'materials' => ['Environment', 'Sustainable materials', 'Responsible sourcing and low-impact material credits', 'Supplier ESG screening and embodied-carbon information'],
            'worker_health' => ['Workers', 'Employee health and well-being', 'Indoor Environmental Quality', 'Occupational safety statistics, benefits, training and labour practices'],
            'community' => ['Community', 'Community impact', 'Site connectivity and community-related innovation', 'Consultation, local procurement, complaints and socioeconomic outcomes'],
            'customer_health' => ['Customers', 'Customer health and safety', 'IAQ, thermal comfort, lighting and acoustic performance', 'Customer complaints, product responsibility and data-protection measures'],
            'governance' => ['Governance', 'Governance and ethics', 'Building manuals, commissioning and performance verification', 'Board oversight, anti-corruption, whistleblowing, risk and procurement policies'],
        ];

        $itemCriteria = [
            44 => ['energy'], 45 => ['energy'], 46 => ['energy'], 47 => ['renewable'], 48 => ['energy'],
            49 => ['governance'], 50 => ['governance'], 51 => ['energy', 'governance'], 52 => ['governance'],
            53 => ['worker_health', 'customer_health'], 54 => ['pollution', 'worker_health', 'customer_health'],
            55 => ['worker_health', 'customer_health'], 56 => ['pollution', 'worker_health', 'customer_health'],
            57 => ['worker_health', 'customer_health'], 58 => ['worker_health', 'customer_health'],
            59 => ['worker_health', 'customer_health'], 60 => ['worker_health', 'customer_health'],
            61 => ['worker_health', 'customer_health'], 62 => ['worker_health', 'customer_health'],
            63 => ['worker_health', 'customer_health'], 64 => ['worker_health'],
            65 => ['worker_health', 'customer_health'], 66 => ['pollution', 'worker_health', 'customer_health'],
            67 => ['worker_health', 'customer_health'], 70 => ['community'], 72 => ['pollution'],
            75 => ['community'], 78 => ['pollution'], 80 => ['governance'],
            81 => ['waste', 'materials'], 82 => ['waste', 'materials'], 83 => ['materials'], 84 => ['materials'],
            85 => ['waste'], 86 => ['waste'], 87 => ['pollution'],
            88 => ['water'], 89 => ['water'], 90 => ['water'], 91 => ['water'], 92 => ['water'],
        ];

        foreach ($itemCriteria as $itemId => $criterionKeys) {
            $existing = DB::table('items')->where('id', $itemId)->value('esg');

            if ($existing === null || str_contains($existing, self::MIDA_HEADING)) {
                continue;
            }

            $mappings = collect($criterionKeys)
                ->map(fn (string $key) => $this->mappingMarkdown($criteria[$key]))
                ->implode("\n\n");
            $separator = $existing === '' || str_ends_with($existing, "\n") ? "\n" : "\n\n";

            DB::table('items')->where('id', $itemId)->update([
                'esg' => $existing.$separator.self::MIDA_HEADING."\n\n".$mappings,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('items')
            ->whereIn('id', [
                44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63,
                64, 65, 66, 67, 70, 72, 75, 78, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92,
            ])
            ->where('esg', 'like', '%'.self::MIDA_HEADING.'%')
            ->orderBy('id')
            ->eachById(function ($items): void {
                foreach ($items as $item) {
                    $position = strpos($item->esg, self::MIDA_HEADING);
                    $original = rtrim(substr($item->esg, 0, $position), "\r\n");
                    DB::table('items')->where('id', $item->id)->update(['esg' => $original]);
                }
            });
    }

    private function mappingMarkdown(array $mapping): string
    {
        [$impactArea, $criterion, $contribution, $additional] = $mapping;

        return implode("\n", [
            '### '.$criterion,
            '',
            '- **Impact Area:** '.$impactArea,
            '- **Criterion:** '.$criterion,
            '- **Mapping Status:** Partially mapped',
            '- **GBI Contribution:** '.$contribution,
            '- **Additional Information Required:** '.$additional,
        ]);
    }
};
