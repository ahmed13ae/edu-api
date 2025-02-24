<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    //
    protected $fillable = [
        'name',
        'price'
    ];
    public function provider(){
        return $this->belongsTo(Provider::class);
    }
}
