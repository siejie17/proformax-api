<?php

namespace App\Services;

use App\Models\BuildingClassification;
use App\Models\BuildingType;
use App\Models\Location;
use App\Models\Structure;
use App\Models\Certification;
use Exception;

class FormDataMappingService
{
    /**
     * Map raw form data to backend format
     */
    public function mapFormData(array $formData): array
    {
        $mapped = [];

        $mapped['buildingType'] = BuildingType::where('name', $formData['buildingType'])->value('id');

        if (isset($formData['buildingClassification'])) {
            $mapped['classificationId'] = BuildingClassification::where('name', $formData['buildingClassification'])->value('id');
            $mapped['has_management'] = $formData['hasManagement'] ?? false;
        }

        $mapped['location'] = $this->mapLocation($formData['state'], $formData['region'] ?? null);
        $mapped['location_name'] = $this->formatLocationName($formData['state'], $formData['region'] ?? null);

        // Map structure
        $mapped['structure'] = $this->mapStructure($formData['structure']);

        // Map rating scale
        $mapped['certifiedRatingScale'] = $this->mapRatingScale($formData['certifiedRatingScale'], $mapped['buildingType']);

        // Keep other fields as-is
        $mapped['projectName'] = $formData['projectName'];
        $mapped['category'] = $formData['category'];
        $mapped['year'] = $formData['year'];
        $mapped['buildingSize'] = $formData['buildingSize'];
        $mapped['projectBudget'] = $formData['projectBudget'];
        $mapped['costPreviewWay'] = $formData['costPreviewWay'];

        return $mapped;
    }

    /**
     * Format location name for display
     */
    private function formatLocationName(string $state, ?string $region): string
    {
        $stateChild = Location::where('location_name', $state)->first();

        if ($stateChild && $stateChild->childLocations()->exists()) {
            return $region;
        }

        return $state;
    }

    /**
     * Map location based on state and region
     */
    private function mapLocation(string $state, ?string $region): string
    {
        $stateRecord = Location::whereNull('parent_location_id')
            ->where('location_name', $state)
            ->first();

        if (!$stateRecord) {
            throw new Exception("Invalid state: {$state}");
        }

        $hasRegions = $stateRecord->childLocations()->exists();

        if ($hasRegions) {
            if (empty($region)) {
                throw new Exception("Region is required for state: {$state}");
            }

            $regionCode = Location::where('parent_location_id', $stateRecord->id)
                ->where('location_name', $region)
                ->value('code');

            if (!$regionCode) {
                throw new Exception("Invalid region '{$region}' for state '{$state}'");
            }

            return $regionCode;
        }

        if (!$stateRecord->code) {
            throw new Exception("Location code not found for state: {$state}");
        }

        return $stateRecord->code;
    }

    /**
     * Map structure name to code
     */
    private function mapStructure(string $structure): string
    {
        $matchedStructure = Structure::where('name', $structure)->first();

        if (!$matchedStructure) {
            throw new Exception("Invalid structure: {$structure}");
        }

        return $matchedStructure->id;
    }

    /**
     * Map rating scale description to value
     */
    private function mapRatingScale(string $ratingScale, string $buildingTypeId): string
    {
        $matchedCertification = Certification::where(['building_type_id' => $buildingTypeId, 'display_name' => $ratingScale])
            ->first();

        if (!$matchedCertification) {
            throw new Exception("Invalid rating scale: {$ratingScale} for building type ID: {$buildingTypeId}");
        }

        return $matchedCertification->name;
    }

    /**
     * Validate form data against mapping rules
     */
    public function validateFormData(array $formData): array
    {
        $errors = [];

        // Validate state
        $state = $formData['state'];
        $stateRecord = Location::whereNull('parent_location_id')
            ->where('location_name', $state)
            ->first();

        if (!$stateRecord) {
            $errors['state'] = 'Invalid state selected';
            $hasRegions = false;
        } else {
            $hasRegions = $stateRecord->childLocations()->exists();
        }

        // Validate region if required
        if ($hasRegions) {
            $region = $formData['region'] ?? null;
            if (empty($region)) {
                $errors['region'] = 'Region is required for this state';
            } elseif (!Location::where('parent_location_id', $stateRecord->id)->where('location_name', $region)->exists()) {
                $errors['region'] = 'Invalid region for selected state';
            }
        }

        // Validate structure exists in mappings
        if (!isset($formData['structure'])) {
            $errors['structure'] = 'Invalid structure selected';
        }

        // Validate rating scale
        if (!isset($formData['certifiedRatingScale'])) {
            $errors['certifiedRatingScale'] = 'Invalid rating scale selected';
        }

        return $errors;
    }

    /**
     * Get certified scale range based on rating scale
     */
    public function getCertifications($buildingTypeId)
    {
        $certifiedScaleRange = Certification::where('building_type_id', $buildingTypeId)->get()->mapWithKeys(function ($cert) {
            return [
                $cert->name => [
                    (int) $cert->min_score,
                    (int) $cert->max_score
                ]
            ];
        })->toArray();

        $certificationMultipliers = Certification::where('building_type_id', $buildingTypeId)->get()->mapWithKeys(function ($cert) {
            return [
                $cert->name => $cert->multiplier
            ];
        });

        return [
            'certifiedScaleRange' => $certifiedScaleRange,
            'certificationMultipliers' => $certificationMultipliers
        ];
    }
}
