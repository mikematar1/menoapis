<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointsItem extends Model
{
    use HasFactory;
    protected $fillable =["business_id","item_name","item_description","item_imageurl","item_points"];
}
