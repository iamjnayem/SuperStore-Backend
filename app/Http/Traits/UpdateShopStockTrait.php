<?php

namespace App\Http\Traits;

use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait UpdateShopStockTrait
{
    use RequestTrait;

    public function updateStock($id)
    {

        $orders =Order::with('order_item_details')
            ->where('id',$id)
            ->first();

        if ($orders){

            foreach($orders['order_item_details'] as $item){

                $stockItem = Item::where('id',$item['item_id'])->first();

                $stockItem->stock_quantity = $stockItem['stock_quantity'] + $item['quantity'];
                $stockItem->save();

            }
            return true;
        }
        return false;
    }

}
