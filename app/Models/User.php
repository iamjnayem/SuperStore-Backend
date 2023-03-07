<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeSearch($query)
    {
        return $query->where('name', 'like', '%' . request()->query('search') . '%')
            ->orWhere('mobile_no', 'like', '%' . request()->query('search') . '%');
    }

    public function scopeFilterByUserType($query)
    {
        return $query->where('user_type',request()->query('filter'));
    }

    public function scopeSortByAlphabetical($query)
    {
        return $query->orderBy('name');
    }


    public function shopItems(): HasMany
    {
        return $this->hasMany(Item::class, 'user_id', 'id')
            ->whereNot('is_publish', 0)
            ->orderByDesc('updated_at');
    }

    public function storeSettings(): HasMany
    {
        return $this->hasMany(StoreSettings::class);

    }

}
