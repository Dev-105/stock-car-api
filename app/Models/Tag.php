<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    
    protected $table = 'tags';
    
    protected $fillable = ['name'];
    
    // Relationships
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tags')
                    ->withTimestamps();
    }
    
    // Helper method
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }
}