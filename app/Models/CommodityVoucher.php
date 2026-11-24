<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['voucher_code', 'user_id', 'commodity_id', 'quantity', 'total_cost', 'status'])]
class CommodityVoucher extends Model
{
    public function user() { return $this->belongsTo(User::class); }
    public function commodity() { return $this->belongsTo(Commodity::class); }
}