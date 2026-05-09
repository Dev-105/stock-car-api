<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;
    
    protected $table = 'cart_items';
    
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity'
    ];
    
    protected $casts = [
        'quantity' => 'integer',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    // Accessors
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->product->final_price;
    }
    
    public function getFormattedSubtotalAttribute()
    {
        return number_format($this->subtotal, 2) . ' €';
    }
}