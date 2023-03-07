<?php
namespace App\Http\Traits;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

trait CategoryFilterTrait
{
    public function sortCategoriesById($data,$ids)
    {
        return $data->sortBy(function($category) use($ids) {
            return array_search($category->id, $ids);
        })->flatten();
    }

    public function getTopSaleCategoriesId()
    {
        return DB::table('invoice_items')
            ->join('items', 'invoice_items.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->select('categories.id as category_id',DB::raw('count(*) as sale'))
            ->groupBy('invoice_items.item_id','categories.id')
            ->having('invoice_items.item_id', '>=', 1)
            ->orderBy('sale','desc')
            ->limit(10)
            ->pluck('category_id')
            ->unique()->toArray();
    }

    public function getTopSellersCategories()
    {
        $topCategoryId = $this->getTopSaleCategoriesId();
        return Category::with('items')
            ->where(['user_id' => auth()->user()->id])
            ->whereIn('id', $this->getTopSaleCategoriesId())
            ->withCount('items')
            ->limit(10)
            ->get()
            ->sortBy(function($category) use($topCategoryId) {
                return array_search($category->id, $topCategoryId);
            })->flatten();
    }

}
