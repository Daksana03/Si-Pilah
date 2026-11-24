<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'petugas_id', 'pickup_date', 'time_slot', 'pickup_address', 'estimated_notes', 'status', 'total_weight', 'total_earned'])]
class PickupRequest extends Model
{
    public function user() { return $this->belongsTo(User::class); }
    public function petugas() { return $this->belongsTo(User::class, 'petugas_id'); }
}