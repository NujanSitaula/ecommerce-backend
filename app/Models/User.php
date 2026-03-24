<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'pending_email',
        'pending_email_verified_at',
        'password',
        'phone',
        'country',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'pending_email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function searchHistories()
    {
        return $this->hasMany(SearchHistory::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function adminTodos()
    {
        return $this->hasMany(AdminTodo::class);
    }

    public function adminNote()
    {
        return $this->hasOne(AdminNote::class);
    }
}
