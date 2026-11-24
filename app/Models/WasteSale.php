<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['waste_category_id', 'weight', 'price_per_unit', 'total', 'buyer', 'sold_at'])]
class WasteSale extends Model
{
    public function category()
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }

    protected function casts(): array
    {
        return [
            'sold_at' => 'date',
        ];
    }
}
