<?php

namespace App\Models;

use App\Constants\OrderStatus;
use App\Constants\PaymentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class,'merchant_id','id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)->withPivot('quantity','addons', 'discount_details');
    }

    public function order_item_details(): HasMany
    {
        return $this->hasMany(ItemOrder::class);
    }

    public function scopeSearch($query)
    {
        $searchParam = match (request()->query('search')){
            'pending' => OrderStatus::WAITING_FOR_ACCEPT,
            'accepted' => OrderStatus::ACCEPTED,
            'ready' => OrderStatus::READY_FOR_PICK_UP,
            'delivered' => OrderStatus::DELIVERED,
            'canceled' => OrderStatus::CANCEL,
            'refunded' => OrderStatus::REFUND,
            'default' =>''
        };
        return $query->where('status', '=', $searchParam);
    }



    public function PaymentStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => match ($value) {
                PaymentStatus::REFUND => PaymentStatus::ALIAS_REFUND,
                PaymentStatus::PAID, PaymentStatus::PROCESSING => PaymentStatus::ALIAS_PAID,
                PaymentStatus::DECLINED => PaymentStatus::ALIAS_DECLINED,
                default =>PaymentStatus::ALIAS_DUE,
            }
        );
    }

    public function status(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => match ($value) {
                OrderStatus::ACCEPTED => OrderStatus::ALIAS_ACCEPTED,
                OrderStatus::READY_FOR_PICK_UP => OrderStatus::ALIAS_READY_FOR_PICK_UP,
                OrderStatus::DELIVERED => OrderStatus::ALIAS_DELIVERED,
                OrderStatus::CANCEL => OrderStatus::ALIAS_CANCEL,
                OrderStatus::REFUND => OrderStatus::ALIAS_REFUND,
                default =>OrderStatus::ALIAS_WAITING_FOR_ACCEPT,
            }
        );
    }
}
