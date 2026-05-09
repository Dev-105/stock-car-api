<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneCommande extends Model
{
    use HasFactory;
    
    protected $table = 'ligne_commandes';
    
    protected $fillable = [
        'command_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal'
    ];
    
    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
    ];
    
    // Relationships
    public function command()
    {
        return $this->belongsTo(Command::class, 'command_id');
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    // Accessors
    public function getFormattedUnitPriceAttribute()
    {
        return number_format($this->unit_price, 2) . ' €';
    }
    
    public function getFormattedSubtotalAttribute()
    {
        return number_format($this->subtotal, 2) . ' €';
    }
}