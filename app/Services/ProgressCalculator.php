<?php
namespace App\Services;

class ProgressCalculator {
    
    /**
     * Calculates individual BOQ item progress capped at 100.
     */
    public function calculateItemProgress($acceptedInstalledQty, $approvedBoqQty, $manualPercentage=0) {
        if (!is_numeric($approvedBoqQty) || (float)$approvedBoqQty <= 0) {
            $manual=max(0,min(100,(float)$manualPercentage));
            return $manual>=100?100.00:min(99.99,round($manual,2));
        }

        $progress = max(0,($acceptedInstalledQty / $approvedBoqQty) * 100);
        
        // Cap at 100 percent per workflow specification
        if($progress>=100)return 100.00;
        return min(99.99,round($progress,2));
    }

    /**
     * Calculates the overall project progress based on weighted items.
     */
    public function calculateProjectProgress(array $boqItems) {
        $totalWeight = 0;
        $weightedProgressSum = 0;

        foreach ($boqItems as $item) {
            // BR-001: Only evaluate eligible "Measurable Item" records
            if (($item['item_type']??'') !== 'Measurable Item' || (($item['status']??'') !== 'Baseline_Approved' && ($item['status']??'') !== 'Complete')) {
                continue;
            }

            $itemWeight = max(0,(float)($item['item_weight']??$item['activity_weight']??0));
            $itemProgress = $this->calculateItemProgress(
                $item['accepted_installed_quantity'] ?? 0, 
                $item['boq_quantity'] ?? $item['material_quantity'] ?? 0,
                $item['percentage_complete'] ?? 0
            );

            $totalWeight += $itemWeight;
            $weightedProgressSum += ($itemWeight * $itemProgress);
        }

        if ($totalWeight <= 0) {
            return 0.00; // Deterministic Not Started result; never divide by zero.
        }

        // Return the final progress rounded to 2 decimal places
        $progress=max(0,min(100,$weightedProgressSum/$totalWeight));
        return $progress>=100?100.00:min(99.99,round($progress,2));
    }
}
