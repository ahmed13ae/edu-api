<?php
namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Provider;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get the list of reviews with optional filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = \App\Models\Review::with('reviewable', 'user');
    
            // Filter by reviewable type
            if ($request->has('type')) {
                $type = ucfirst($request->type); // course or provider
                $model = $type === 'Course' ? \App\Models\Course::class : \App\Models\Provider::class;
    
                $query->where('reviewable_type', $model);
            }
    
            // Get paginated results
            $reviews = $query->latest()->paginate(10);

            // Check if no reviews found
            if ($reviews->total() === 0) {
                return response()->json([
                    'current_page' => $reviews->currentPage(),
                    'total_pages' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total_items' => $reviews->total(),
                    'average_rating' => 0.0,
                    'data' => [],
                    'links' => [
                        'previous' => $reviews->previousPageUrl(),
                        'next' => $reviews->nextPageUrl(),
                        'first' => $reviews->url(1),
                        'last' => $reviews->url($reviews->lastPage())
                    ]
                ], 200);
            }
    
            // Modify the reviewable_type to be just 'Course' or 'Provider'
            $reviews->getCollection()->transform(function ($review) {
                $review->reviewable_type = class_basename($review->reviewable_type);
                return $review;
            });

            // Get the average rating for the reviewable item (course or provider)
            $averageRating = $reviews->getCollection()->first()->reviewable->reviews()
            ->avg('rating');
            $averageRatingFormatted = number_format($averageRating, 1);

            // Custom pagination response structure
            $data = [
                'current_page' => $reviews->currentPage(),
                'total_pages' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total_items' => $reviews->total(),
                'average_rating' => $averageRatingFormatted,
                'data' => $reviews->items(), // This is the collection of reviews for the current page
                'links' => [
                    'previous' => $reviews->previousPageUrl(),
                    'next' => $reviews->nextPageUrl(),
                    'first' => $reviews->url(1),
                    'last' => $reviews->url($reviews->lastPage())
                ]
            ];
    
            return response()->json($data, 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch reviews'], 500);
        }
    }
    


    /**
     * Store a review for a course.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCourseReview(Request $request, $id)
    {
        return $this->storeReview($request, Course::class, $id);
    }

    /**
     * Store a review for a provider.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeProviderReview(Request $request, $id)
    {
        return $this->storeReview($request, Provider::class, $id);
    }

    /**
     * Store a review for a given model (course or provider).
     *
     * @param Request $request
     * @param string $modelClass
     * @param int $reviewableId
     * @return \Illuminate\Http\JsonResponse
     */
    private function storeReview(Request $request, string $modelClass, int $reviewableId)
    {
        try {
            // Validate review data
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|max:1000',
            ]);

            // Find the reviewable entity (Course or Provider)
            $reviewable = $modelClass::findOrFail($reviewableId);

            // Create the review
            $review = $reviewable->reviews()->create([
                'user_id' => auth()->id(),
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            // Return the review with human-readable reviewable type
            return response()->json([
                'id' => $review->id,
                'user_id' => $review->user_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'reviewable_id' => $review->reviewable_id,
                'reviewable_type' => class_basename($modelClass), // 'Course' or 'Provider'
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create review'], 500);
        }
    }

    /**
     * Update a review.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $review = Review::findOrFail($id);

            // Check if the review belongs to the authenticated user or if the user is an admin
            if ($review->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Validate updated review data
            $request->validate([
                'rating' => 'sometimes|integer|min:1|max:5',
                'comment' => 'sometimes|nullable|string|max:1000',
            ]);

            // Update the review
            $review->update($request->only(['rating', 'comment']));

            return response()->json($review, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update review'], 500);
        }
    }

    /**
     * Delete a review.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $review = Review::findOrFail($id);

            // Check if the review belongs to the authenticated user or if the user is an admin
            if ($review->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Delete the review
            $review->delete();
            return response()->json(['message' => 'Review deleted'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete review'], 500);
        }
    }
}
