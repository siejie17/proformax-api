<?php

namespace App\Services;

use Exception;

class FormDataMappingService
{
    private $mappings;
    
    public function __construct()
    {
        $this->mappings = config('formDataMappings');
    }
    
    /**
     * Map raw form data to backend format
     */
    public function mapFormData(array $formData): array
    {
        $mapped = [];
        
        // Map location (state/region)
        $mapped['location'] = $this->mapLocation($formData['state'], $formData['region'] ?? null);
        
        // Map structure
        $mapped['structure'] = $this->mapStructure($formData['structure']);
        
        // Map rating scale
        $mapped['certifiedRatingScale'] = $this->mapRatingScale($formData['certifiedRatingScale']);
        
        // Keep other fields as-is
        $mapped['buildingType'] = $formData['buildingType'] === 'Non-Residential New Construction (NRNC)' ? 1 : 2;
        $mapped['category'] = $formData['category'];
        $mapped['year'] = $formData['year'];
        $mapped['buildingSize'] = $formData['buildingSize'];
        $mapped['projectBudget'] = $formData['projectBudget'];
        $mapped['costPreviewWay'] = $formData['costPreviewWay'];
        
        return $mapped;
    }
    
    /**
     * Map location based on state and region
     */
    private function mapLocation(string $state, ?string $region): string
    {
        // Check if it's a special state with regions
        if (isset($this->mappings['regions'][$state])) {
            if (empty($region)) {
                throw new Exception("Region is required for state: {$state}");
            }
            
            if (!isset($this->mappings['regions'][$state][$region])) {
                throw new Exception("Invalid region '{$region}' for state '{$state}'");
            }
            
            return $this->mappings['regions'][$state][$region];
        }
        
        // Regular state mapping
        if (!isset($this->mappings['locations'][$state])) {
            throw new Exception("Invalid state: {$state}");
        }
        
        return $this->mappings['locations'][$state];
    }
    
    /**
     * Map structure name to code
     */
    private function mapStructure(string $structure): string
    {
        if (!isset($this->mappings['structures'][$structure])) {
            throw new Exception("Invalid structure: {$structure}");
        }
        
        return $this->mappings['structures'][$structure];
    }
    
    /**
     * Map rating scale description to value
     */
    private function mapRatingScale(string $ratingScale): string
    {
        if (!isset($this->mappings['rating_scales'][$ratingScale])) {
            throw new Exception("Invalid rating scale: {$ratingScale}");
        }
        
        return $this->mappings['rating_scales'][$ratingScale];
    }
    
    /**
     * Validate form data against mapping rules
     */
    public function validateFormData(array $formData): array
    {
        $errors = [];
        
        // Validate state
        $state = $formData['state'];
        $hasRegions = isset($this->mappings['regions'][$state]);
        $hasDirectMapping = isset($this->mappings['locations'][$state]);
        
        if (!$hasRegions && !$hasDirectMapping) {
            $errors['state'] = 'Invalid state selected';
        }
        
        // Validate region if required
        if ($hasRegions) {
            $region = $formData['region'] ?? null;
            if (empty($region)) {
                $errors['region'] = 'Region is required for this state';
            } elseif (!isset($this->mappings['regions'][$state][$region])) {
                $errors['region'] = 'Invalid region for selected state';
            }
        }
        
        // Validate structure availability
        if (isset($formData['structure']) && isset($formData['buildingType'])) {
            $isValidStructure = $this->validateStructureForBuildingTypeAndState(
                $formData['structure'],
                $formData['buildingType'],
                $state
            );
            
            if (!$isValidStructure) {
                $errors['structure'] = 'Invalid structure for selected building type and state';
            }
        }
        
        // Validate structure exists in mappings
        if (isset($formData['structure']) && !isset($this->mappings['structures'][$formData['structure']])) {
            $errors['structure'] = 'Invalid structure selected';
        }
        
        // Validate rating scale
        if (isset($formData['certifiedRatingScale']) && !isset($this->mappings['rating_scales'][$formData['certifiedRatingScale']])) {
            $errors['certifiedRatingScale'] = 'Invalid rating scale selected';
        }
        
        return $errors;
    }
    
    /**
     * Validate if structure is available for building type and state
     */
    private function validateStructureForBuildingTypeAndState(string $structure, string $buildingType, string $state): bool
    {
        $buildingTypeCode = $buildingType === 'Residential New Construction (RNC)' ? 'RNC' : 'NRNC';
        $rules = $this->mappings['structure_availability'][$buildingTypeCode] ?? [];
        
        if ($buildingTypeCode === 'RNC') {
            return in_array($structure, $rules['all_states'] ?? []);
        }
        
        if ($buildingTypeCode === 'NRNC') {
            // Check if state has special structures
            if (isset($rules['special_states'][$state])) {
                return in_array($structure, $rules['special_states'][$state]);
            }
            
            // Use base structures for other states
            return in_array($structure, $rules['base_structures'] ?? []);
        }
        
        return false;
    }
    
    /**
     * Get available structures for building type and state
     */
    public function getAvailableStructures(string $buildingType, string $state): array
    {
        $buildingTypeCode = $buildingType === 'Residential New Construction (RNC)' ? 'RNC' : 'NRNC';
        $rules = $this->mappings['structure_rules'][$buildingTypeCode] ?? [];
        
        if ($buildingTypeCode === 'RNC') {
            return $rules['all_states'] ?? [];
        }
        
        if ($buildingTypeCode === 'NRNC') {
            // Check if state has special structures
            if (isset($rules['special_states'][$state])) {
                return $rules['special_states'][$state];
            }
            
            // Use base structures for other states
            return $rules['base_structures'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Get all available options for dropdowns
     */
    public function getMappingOptions(): array
    {
        return [
            'states' => array_merge(
                array_keys($this->mappings['locations']),
                array_keys($this->mappings['regions'])
            ),
            'regions' => $this->mappings['regions'],
            'rating_scales' => array_keys($this->mappings['rating_scales']),
        ];
    }
}