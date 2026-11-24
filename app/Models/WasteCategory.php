<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'unit', 'buy_price', 'sell_price', 'description'])]
class WasteCategory extends Model {}