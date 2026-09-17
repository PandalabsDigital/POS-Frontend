<?php

namespace App\Services;

use App\Models\InventoryRecipe;

class RecipeCosting
{
    public function __construct(private UnitConverter $units) {}

    /**
     * @return array{ingredient_cost: float, waste_cost: float, recipe_cost: float, portion_cost: float, yield: float}
     */
    public function summarize(InventoryRecipe $recipe): array
    {
        $recipe->loadMissing('ingredients.item.stockUnit', 'ingredients.unit');
        $ingredientCost = 0.0;
        $withWaste = 0.0;

        foreach ($recipe->ingredients as $line) {
            $base = $this->units->toStockQuantity($line->item, (float) $line->quantity, $line->unit);
            $effective = $this->units->toStockQuantity($line->item, $line->effectiveQuantity(), $line->unit);
            $cost = (float) $line->item->average_cost;
            $ingredientCost += $base * $cost;
            $withWaste += $effective * $cost;
        }

        $recipeWaste = 1 + ((float) $recipe->waste_percent / 100);
        $recipeCost = $withWaste * $recipeWaste;
        $yield = max((float) $recipe->yield_quantity, 0.0001);

        return [
            'ingredient_cost' => round($ingredientCost, 2),
            'waste_cost' => round($recipeCost - $ingredientCost, 2),
            'recipe_cost' => round($recipeCost, 2),
            'portion_cost' => round($recipeCost / $yield, 2),
            'yield' => (float) $recipe->yield_quantity,
        ];
    }
}
