<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    protected $table = 'products';
    
    protected $fillable = [
        'title',
        'description', 
        'price',
        'discount_percentage',
        'stock',
        'theme'
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'stock' => 'integer',
    ];
    
    // Relationships
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
    
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags')
                    ->withTimestamps();
    }
    
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
    
    public function commandLines()
    {
        return $this->hasMany(LigneCommande::class);
    }
    
    // Accessors
    public function getFinalPriceAttribute()
    {
        $discountAmount = $this->price * ($this->discount_percentage / 100);
        return round($this->price - $discountAmount, 2);
    }
    
    public function getDiscountAmountAttribute()
    {
        return round($this->price * ($this->discount_percentage / 100), 2);
    }
    
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
    
    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }
    
    public function getIsInStockAttribute()
    {
        return $this->stock > 0;
    }
    
    // Helper methods
    public function reduceStock($quantity)
    {
        if ($this->stock >= $quantity) {
            $this->stock -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }
    
    public function increaseStock($quantity)
    {
        $this->stock += $quantity;
        $this->save();
    }
}