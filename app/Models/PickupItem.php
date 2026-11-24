<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['pickup_request_id', 'waste_category_id', 'weight', 'unit_price', 'subtotal'])]
class PickupItem extends Model
{
    public function pickup()
    {
        return $this->belongsTo(PickupRequest::class, 'pickup_request_id');
    }

    public function category()
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }
}
