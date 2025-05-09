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
