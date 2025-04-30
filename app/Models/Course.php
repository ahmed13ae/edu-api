<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    //
    protected $guarded = [
       
    ];
    public function provider(){
        return $this->belongsTo(Provider::class);
    }
    public function field(){
        return $this->belongsTo(Field::class);
    }
    public function reviews()
    {
    return $this->morphMany(Review::class, 'reviewable');
    }

}
