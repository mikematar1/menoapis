<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Business extends Model
{
    use HasFactory;
    protected $primaryKey = 'user_id';
    protected $fillable = ['user_id','name','location','closing_hours','opening_hours','description','logo_url','category_id','employee_id','ig_link','fb_link','tiktok_link','menu_link','wallet'];

}
