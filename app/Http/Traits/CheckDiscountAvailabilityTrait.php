<?php

namespace App\Http\Traits;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

trait CheckDiscountAvailabilityTrait
{
    /*
     *
     * Check Categories or Items is already active with another Discount or Not
     * */
    public function isRunningDiscountAvailable($discount,$id=null): array
    {

        $duplicateCategory = array_intersect($discount['categories'], $this->getActiveDiscountCategories($id));
        if (sizeof($duplicateCategory) > 0 ){
            return [
                'categories' => $duplicateCategory
            ];
        }

        $duplicateItem = array_intersect($discount['items'], $this->getActiveDiscountItems($id));
        if (sizeof($duplicateItem) > 0 ){
            return [
                'items' => $duplicateItem
            ];
        }

        return [];

    }

    /*
     * get all categories for auth merchant which is already into active Discount
     *
     * */
    public function getActiveDiscountCategories($id): array
    {
        $data =  Discount::where('end_date','>',Carbon::now()->format('Y-m-d H:i:s'))
            ->where('merchant_id',auth()->user()->id)
            ->where('id','!=',$id)
            ->pluck('categories')->flatten()->unique()->toArray();

        $categories = [];
        foreach ($data as $i => $cat){
            $categories[$i] = json_decode($cat);
        }
        return array_merge(...$categories);
    }

    /*
     *
     * get all Items for auth merchant which is already into active Discount
     * */
    public function getActiveDiscountItems($id): array
    {
        $data = Discount::where('end_date','>',Carbon::now()->format('Y-m-d H:i:s'))
            ->where('merchant_id',auth()->user()->id)
            ->where('id','!=',$id)
            ->pluck('items')->flatten()->unique()->toArray();

        $items = [];
        foreach ($data as $i => $item){
            $items[$i] = json_decode($item);
        }
        return array_merge(...$items);
    }

    /*
     * get specific discount categories
     *
     * */
    public function getDiscountCategories($discount)
    {
        if (sizeof($discount) > 0 ){
            foreach ($discount as $i => $cat){
                $discount[$i]['categories_details'] = Category::whereIn('id',json_decode($cat['categories']))->get();
            }
        }
        return $discount;
    }

    /*
     * get specific discount items
     *
     * */
    public function getDiscountItems($discount)
    {
        if (sizeof($discount) > 0 ){
            foreach ($discount as $i => $item){
                $discount[$i]['items_details'] = Item::whereIn('id',json_decode($item['items']))->get();
            }
        }
        return $discount;
    }


    /*
     * get Items with discount Id
     *
     * */
    public function getDiscountIntoItem($data): array
    {
        $items = $data['items'];
        $itemCategories = [];
        if (sizeof($data['categories']) > 0 ){
            $itemCategories = Item::whereIn('category_id',$data['categories'])->pluck('id')->toArray();
        }
        $items = array_unique(array_merge($items,$itemCategories));
        return $items;
    }

    /*
     * set discount to requested item's
     *
     * */
    public function setItemDiscount($items,$id)
    {
        Item::whereIn('id',$items)
            ->update([
                'discount_id' => $id
            ]);

    }

    /*
     * Remove discount form item's
     *
     * */
    public function removeItemDiscount($id)
    {
        Item::where('discount_id',$id)
            ->update([
                'discount_id' => null
            ]);
    }


}
