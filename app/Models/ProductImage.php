<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;
    
    protected $table = 'product_images';
    
    protected $fillable = [
        'product_id',
        'image_url'
    ];
    
    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    // Accessor for full URL
    public function getImageUrlAttribute($value)
    {
        if (str_starts_with($value, 'http')) {
            return $value;
        }
        return asset('storage/' . $value);
    }
}