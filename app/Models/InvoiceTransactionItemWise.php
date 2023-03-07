<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTransactionItemWise extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_transaction_id','item_id','quantity','unit','unit_price','unit_price_without_discount','total_price','total_price_without_discount'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function item()
    {
        return $this->belongsTo(Item::Class);
    }
}
