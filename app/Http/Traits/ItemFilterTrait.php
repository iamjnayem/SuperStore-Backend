<?php
namespace App\Http\Traits;

use App\Models\Item;
use Illuminate\Support\Facades\DB;

trait ItemFilterTrait
{
    public function sortItemsById($data,$ids)
    {
        return $data->sortBy(function($item) use($ids) {
            return array_search($item->id, $ids);
        })->flatten();
    }

    public function getTopSellerItemId()
    {
        return DB::table('invoice_items')
            ->select('item_id',DB::raw('count(*) as sale'))
            ->groupBy('item_id')
            ->having('item_id', '>=', 1)
            ->orderBy('sale','desc')
            ->limit(10)
            ->pluck('item_id')
            ->unique()->toArray();
    }

    public function getTopSellerItem()
    {
        //
    }

}
