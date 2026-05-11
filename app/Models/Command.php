<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    use HasFactory;
    
    protected $table = 'commands';
    
    protected $fillable = [
        'user_id',
        'total',
        'status',
        'promo_code_id'
    ];
    
    protected $casts = [
        'total' => 'decimal:2',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function lignes()
    {
        return $this->hasMany(LigneCommande::class, 'command_id');
    }
    
    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }
    
    // Accessors
    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2) . ' MAD';
    }
    
    public function getStatusLabelAttribute()
    {
        return [
            'pending' => 'En attente',
            'processing' => 'En traitement',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée'
        ][$this->status] ?? $this->status;
    }
    
    // Helper methods
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
    
    public function cancel()
    {
        if ($this->status === 'pending') {
            $this->status = 'cancelled';
            $this->save();
            
            // Restore stock
            foreach ($this->lignes as $ligne) {
                $ligne->product->increaseStock($ligne->quantity);
            }
            return true;
        }
        return false;
    }
}