<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'google_id',
    'role',
    'phone',
    'address',
    'balance',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public function pickupRequests()
{
    return $this->hasMany(PickupRequest::class)->latest();
}

public function ledgerMutations()
{
    return $this->hasMany(LedgerMutation::class)->latest();
}

public function vouchers()
{
    return $this->hasMany(CommodityVoucher::class)->latest();
}
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }
}