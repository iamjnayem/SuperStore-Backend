<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTransaction extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','invoice_id','transaction_id','deshi_transaction_id','type','type_human_readable','amount','discount'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeFilterByDate($query)
    {
        $from = Carbon::createFromFormat('Y-m-d', request()->query('date_from'))->format('Y-m-d 00:00:00');
        $to = Carbon::createFromFormat('Y-m-d', request()->query('date_to'))->format('Y-m-d 23:59:59');

        return $query->whereBetween('created_at',[$from,$to]);
    }
}
