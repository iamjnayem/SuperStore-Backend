<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemOrder extends Model
{
    use HasFactory;

    protected $table = 'item_order';
    protected $guarded = [];

    /*public function items()
    {
        return $this->belongsTo(Item::class,'item_id')->withTrashed();
    }*/
}
