<?php

namespace App\Models;

use App\Http\Traits\CategoryFilterTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Category extends BaseModel
{
    use HasFactory,CategoryFilterTrait, SoftDeletes;

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function merchantItems()
    {
        return $this->hasMany(Item::class)->where('user_id', auth()->user()->id);
    }


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
        $orderedIds = implode(',', $this->getTopSaleCategoriesId());

        return $query->whereIn('id', $this->getTopSaleCategoriesId())
            ->orderByRaw(DB::raw("FIELD(id, ".$orderedIds." )"));

    }


}
