<?php

namespace App\Models;

use App\Http\Traits\ItemFilterTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class Item extends BaseModel
{
    use HasFactory,ItemFilterTrait, SoftDeletes;

    protected $guarded = [];

    const ITEM_PUBLISHED = 2;
    const ITEM_UNPUBLISHED = 0;
    const ITEM_REQUESTED = 1;

    public function scopeSearch($query)
    {

        return $query->where('name', 'like', '%' . request()->query('search') . '%');
    }

    public function scopeFilterByRecentAdded($query)
    {
        return $query->orderBy('id', 'desc');
    }

    public function scopeFilterBytopSeller($query)
    {
        $orderedIds = implode(',', $this->getTopSellerItemId());

        return $query->whereIn('id', $this->getTopSellerItemId())
            ->orderByRaw(DB::raw("FIELD(id, ".$orderedIds." )"));

    }

    public function scopeFilterByPublished($query)
    {
        return $query->where('is_publish',self::ITEM_PUBLISHED);
    }

    public function scopeFilterByUnpublished($query)
    {
        return $query->whereIn('is_publish', [self::ITEM_UNPUBLISHED,self::ITEM_REQUESTED]);
    }

    public function scopeFilterByShopItem($query)
    {
        return $query->whereIn('is_publish', [self::ITEM_PUBLISHED,self::ITEM_REQUESTED]);
    }

    public function scopeFilterByRequested($query)
    {
        return $query->where('is_publish', self::ITEM_REQUESTED);
    }


    public function scopeFilterByCategoryId($query)
    {
        return $query->where('category_id', request()->query('category'));
    }

    public function scopeSortByAlphabetic($query)
    {
        return $query->orderBy('name');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class,'category_id','id')->select(['id','name']);
    }

    public function sales_report(): BelongsTo
    {
        return $this->belongsTo(ItemWiseTotalReport::class,'id','item_id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class,'discount_id','id')
            ->where('end_date','>',Carbon::now()->format('Y-m-d H:i:s'));
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function storeInfo()
    {
        return $this->belongsTo(StoreSettings::class,'user_id','user_id');
    }


}
