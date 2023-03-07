<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantWiseNetSale extends Model
{
    use HasFactory;

    protected $table = 'merchant_wise_published_net_sale';

    protected $fillable =['merchant_id','net_sale_amount'];
}
