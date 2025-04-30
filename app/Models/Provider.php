<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $guarded=[];
    public function courses(){
        return $this->hasMany(Course::class);
    }
    public function city(){
        return $this->belongsTo(City::class);
    }
    public function reviews()
    {
    return $this->morphMany(Review::class, 'reviewable');
    }
}
