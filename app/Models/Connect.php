<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Connect extends BaseModel
{
    use HasFactory, SoftDeletes;

    public function connector()
    {
        return $this->belongsTo(User::class,'connector_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function user_settings()
    {
        return $this->belongsTo(PrivacySetting::class,'user_id','user_id');
    }

    public function scopeFilterByFavorite($query)
    {
        $favorite = request()->query('favorite') == "true" ? 1 : 0;
        return $query->where('is_favourite',$favorite);
    }

    public function scopeSortByRecently($query)
    {
        return $query->orderBy('id','desc');
    }

    public function scopeSortByTopseller($query)
    {

        $orderedIds = implode(',', $this->getTopConnects());
        return $query->whereIn('id', $this->getTopConnects())
            ->orderByRaw(DB::raw("FIELD(id, ".$orderedIds." )"));
    }

    public function getTopConnects()
    {
        return DB::table('invoices')
            ->select('connect_id',DB::raw('count(*) as sale'))
            ->groupBy('connect_id')
            ->orderBy('sale','desc')
            ->pluck('connect_id')
            ->toArray();
    }
}
