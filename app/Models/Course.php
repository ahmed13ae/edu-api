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

    // Accessor for average rating
     public function getAverageRatingAttribute()
     {
         return $this->reviews()->avg('rating');
     }
     
     // Accessor for reviews count
     public function getReviewsCountAttribute()
     {
         return $this->reviews()->count();
     }
 
     // Accessor for formatted reviews
     public function getFormattedReviewsAttribute()
     {
         return $this->reviews()->with('user:id,name')->get()->map(function ($review) {
             return [
                 'id' => $review->id,
                 'user' => $review->user->name,
                 'rating' => $review->rating,
                 'comment' => $review->comment,
                 'created_at' => $review->created_at->toDateTimeString(),
             ];
         });
     }

}
