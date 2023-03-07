<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTransactionCategoryWise extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_transaction_id','category_id','item_quantities','total_price','total_price_without_discount'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
