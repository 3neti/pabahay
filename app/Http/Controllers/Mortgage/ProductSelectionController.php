<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mortgage\SelectProductRequest;
use LBHurtado\Mortgage\Models\Product;
use LBHurtado\Mortgage\Services\ProductSelectorService;

class ProductSelectionController extends Controller
{
    public function __construct(
        protected ProductSelectorService $productSelector
    ) {}

    /**
     * Select the best product for a buyer.
     *
     * POST /api/v1/mortgage/product/select
     */
    public function select(SelectProductRequest $request)
    {
        $age = $request->validated('age');
        $income = $request->validated('monthly_gross_income');
        $topN = $request->validated('return_top_n', 3);

        \Log::info('ProductSelectionController: Received selection request', [
            'age' => $age,
            'monthly_gross_income' => $income,
            'top_n' => $topN,
        ]);

        // Get all products from database
        $products = Product::all()->map(function ($product) {
            return (object) [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'brand' => $product->brand,
                'category' => $product->category,
                'description' => $product->description,
                'lending_institution' => $product->lending_institution,
                'price' => $product->price ? $product->price->inclusive()->getAmount()->toFloat() : 0,
                'base_priority' => $product->base_priority,
                'commission_rate' => $product->commission_rate,
                'is_featured' => $product->is_featured,
                'boost_multiplier' => $product->boost_multiplier,
            ];
        });

        \Log::info('ProductSelectionController: Loaded products', [
            'products_count' => $products->count(),
        ]);

        // Get selected product
        $selected = $this->productSelector->selectBestProduct($age, $income, $products);

        \Log::info('ProductSelectionController: Selected product', [
            'selected' => $selected,
        ]);

        // Get alternatives
        $alternatives = $this->productSelector->getTopProducts($age, $income, $products, $topN + 1)
            ->skip(1) // Skip the selected one
            ->take($topN)
            ->values();

        if (! $selected) {
            \Log::warning('ProductSelectionController: No affordable products found');

            return response()->json([
                'success' => false,
                'message' => 'No affordable products found for your income level',
                'selected_product' => null,
                'alternatives' => [],
            ]);
        }

        \Log::info('ProductSelectionController: Returning successful response', [
            'selected_product_id' => $selected['product_id'],
            'alternatives_count' => $alternatives->count(),
        ]);

        return response()->json([
            'success' => true,
            'selected_product' => $selected,
            'alternatives' => $alternatives,
            'message' => 'Product selected successfully',
        ]);
    }
}
