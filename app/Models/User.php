<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $fillable = [
        'username',
        'email', 
        'phone',
        'password',
        'city',
        'address',
        'profile_image',
        'role',
        'fidelity_points'
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'fidelity_points' => 'integer',
    ];
    
    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function commands()
    {
        return $this->hasMany(Command::class);
    }
    
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
    
    public function promoCodeUsages()
    {
        return $this->belongsToMany(PromoCode::class, 'promo_code_usages')
                    ->withTimestamps();
    }
    
    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    public function addFidelityPoints($points)
    {
        $this->fidelity_points += $points;
        $this->save();
    }
    
    public function redeemFidelityPoints($points)
    {
        if ($this->fidelity_points >= $points) {
            $this->fidelity_points -= $points;
            $this->save();
            return true;
        }
        return false;
    }
}