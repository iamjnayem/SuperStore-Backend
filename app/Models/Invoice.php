<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'is_starred' => 'boolean',
    ];

    public function scopeSearch($query)
    {
        return $query->where('invoice_id', 'like', '%' . request()->query('search') . '%')
            ->orWhere('title', 'like', '%' . request()->query('search') . '%');
    }

    public function scopeFilterByStarred($query)
    {
        $starred = request()->query('starred') == "true" ? 1 : 0;
        return $query->where('is_starred',$starred);
    }

    public function scopeFilterByConsumerStatus($query)
    {
        $status =  match (request()->query('filter')) {
            'pending'   =>1,
            'payable'   =>2,
            'rejected'  =>3,
            'overdue'   =>4,
            'scheduled' =>7,
            'paid'      =>9,
            default => '',
        };
        return $query->where('status',$status);
    }

    public function scopeFilterByMerchantStatus($query)
    {
        $status =  match (request()->query('filter')) {
            'draft'         =>0,
            'pending'       =>1,
            'receivable'    =>2,
            'declined'      =>3,
            'expired'       =>4,
            'scheduled'     =>7,
            'received'      =>9,
            default         => '',
        };
        return $query->where('status',$status);
    }

    public function merchant()
    {
        return $this->belongsTo(User::class,'merchant_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice_items()
    {
        return $this->hasMany(InvoiceItem::class,'invoice_id')->with('item');
    }

    public function scopeScheduled($query)
    {
        $todayDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        $nextTime = Carbon::now()->addMinutes(5)->format('H:i:s');
        return $query
            ->whereBetween('schedule_at_time',[$currentTime,$nextTime])
            ->where('schedule_at_date',$todayDate)
            ->where('status',7);
    }

    public function scopeFilterByDate($query)
    {
        $from = Carbon::createFromFormat('Y-m-d', request()->query('date'))->format('Y-m-d 00:00:00');
        $to = Carbon::createFromFormat('Y-m-d', request()->query('date'))->format('Y-m-d 23:59:59');

        return $query->whereBetween('created_at',[$from,$to]);
    }
}
