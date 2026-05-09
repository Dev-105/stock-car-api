<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;
    
    protected $table = 'promo_codes';
    
    protected $fillable = [
        'code',
        'discount_percentage',
        'max_usage',
        'used_count',
        'expires_at'
    ];
    
    protected $casts = [
        'discount_percentage' => 'integer',
        'max_usage' => 'integer',
        'used_count' => 'integer',
        'expires_at' => 'datetime',
    ];
    
    // Relationships
    public function commands()
    {
        return $this->hasMany(Command::class);
    }
    
    // Accessors
    public function getIsValidAttribute()
    {
        return !$this->isExpired() && !$this->isMaxedOut();
    }
    
    // Helper methods
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
    
    public function isMaxedOut()
    {
        return $this->used_count >= $this->max_usage;
    }
    
    public function use()
    {
        if ($this->isValid) {
            $this->used_count++;
            $this->save();
            return true;
        }
        return false;
    }
    
    public function calculateDiscount($amount)
    {
        return $amount * ($this->discount_percentage / 100);
    }
}