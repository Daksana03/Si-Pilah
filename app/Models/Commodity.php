<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'price', 'stock', 'unit'])]
class Commodity extends Model
{
    public function vouchers()
    {
        return $this->hasMany(CommodityVoucher::class);
    }
}
