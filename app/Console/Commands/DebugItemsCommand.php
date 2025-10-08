<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Criterion;
use App\Models\Item;

class DebugItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug items with null subcriterion_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== Checking Items table structure ===");
        $items = Item::all();
        if ($items->count() > 0) {
            $firstItem = $items->first();
            $this->info('Sample item attributes: ' . json_encode($firstItem->getAttributes(), JSON_PRETTY_PRINT));
        }

        $this->info("\n=== Testing direct relationship ===");
        $criterion6 = Criterion::find(6);
        if ($criterion6) {
            $this->info("Criterion 6 name: " . $criterion6->name);
            
            // Test raw query to see items with criterion_id = 6 and subcriterion_id = null
            $directItems = Item::where('criterion_id', 6)->whereNull('subcriterion_id')->get();
            $this->info("Direct items found (raw query): " . $directItems->count());
            
            // Test the relationship
            $relationshipItems = $criterion6->items;
            $this->info("Items via relationship: " . $relationshipItems->count());
            
            if ($directItems->count() > 0) {
                foreach ($directItems as $item) {
                    $this->info("  Item ID: {$item->id}, Description: {$item->description}, criterion_id: {$item->criterion_id}, subcriterion_id: " . ($item->subcriterion_id ?: 'NULL'));
                }
            }
        } else {
            $this->error("Criterion with ID 6 not found!");
        }

        $this->info("\n=== Checking all criteria and their items ===");
        $allCriteria = Criterion::with('items')->get();
        foreach ($allCriteria as $crit) {
            if ($crit->items->count() > 0) {
                $this->info("Criterion {$crit->id} ({$crit->name}): {$crit->items->count()} direct items");
            }
        }

        $this->info("\n=== Checking items with NULL subcriterion_id ===");
        $nullSubcriterionItems = Item::whereNull('subcriterion_id')->get();
        $this->info("Total items with NULL subcriterion_id: " . $nullSubcriterionItems->count());
        
        foreach ($nullSubcriterionItems as $item) {
            $this->info("  Item ID: {$item->id}, criterion_id: " . ($item->criterion_id ?: 'NULL') . ", subcriterion_id: " . ($item->subcriterion_id ?: 'NULL'));
        }

        $this->info("\n=== Checking Item model fillable and relationships ===");
        $itemModel = new Item();
        $this->info("Item fillable fields: " . json_encode($itemModel->getFillable()));

        $this->info("\n=== Checking all criteria named 'Innovation' ===");
        $innovationCriteria = Criterion::where('name', 'Innovation')->get();
        foreach ($innovationCriteria as $crit) {
            $this->info("Criterion ID: {$crit->id}, Name: {$crit->name}, Building Type ID: {$crit->building_type_id}");
            $itemCount = $crit->items()->count();
            $this->info("  - Direct items count: {$itemCount}");
        }
        
        return 0;
    }
}
