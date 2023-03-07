<?php

namespace App\Http\Traits;

use App\Models\Discount;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait DiscountTrait
{

    /*
     * Calculate discount for a specific Item
     *
     * */
    public function getDiscountAmount($item,$merchantId): float|int
    {
        $discountAmount = 0 ;
        $getDiscountInfo = null;

        if (array_key_exists('discount_id',$item)){
            $getDiscountInfo = Discount::where('id',$item['discount_id'])
                ->where('merchant_id',$merchantId)
                ->where('end_date','>',Carbon::now()->format('Y-m-d H:i:s'))
                ->first();
        }


        $itemPrice = $item['unit_price'];

        /*if ($getDiscountInfo && ($getDiscountInfo->max_use - $getDiscountInfo->use_count) > 0 ){

            if ($getDiscountInfo->is_percentage){
                $discountAmount = ($itemPrice*$getDiscountInfo->amount)/100;
            }

            else{
                $discountAmount = $getDiscountInfo->amount ;
            }

        }*/

        if ($getDiscountInfo){

            if ($getDiscountInfo->is_percentage){
                $discountAmount = ($itemPrice*$getDiscountInfo->amount)/100;
            }

            else{
                $discountAmount = $getDiscountInfo->amount ;
            }

        }

        return $discountAmount;
    }


    /*
     * get item  discount Amount
     *
     * */
    public function getItemDiscountAmount($id,$merchantId): float|int
    {
        $item = Item::with('discount')
            ->where('id',$id)
            ->where('user_id',$merchantId)
            ->first()
            ->toArray();

        if (!$item['discount']){

            return 0;
        }

        return $this->getDiscountAmount($item,$merchantId);

    }

}
