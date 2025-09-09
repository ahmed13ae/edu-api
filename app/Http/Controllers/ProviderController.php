<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class ProviderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/providers",
     *     summary="Get a list of all course providers",
     *     tags={"Providers"},
     *     @OA\Response(
     *         response=200,
     *         description="List of providers"
     *     )
     * )
     */
    public function index()
    {
        try {
            $providers = Provider::with(['city:id,city', 'reviews.user'])
                ->paginate(10);

            $providers->getCollection()->transform(function ($provider) {
    
                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'image' => $provider->image,
                    'phone' => $provider->phone,
                    'address' => $provider->address,
                    'page' => $provider->page,
                    'city' => $provider->city,
                    'average_rating' => number_format($provider->average_rating, 1),
                    'review_count' => $provider->reviews_count,
                    'reviews' => $provider->formatted_reviews
                    
                ];
            });

            return response()->json(['providers' => $providers], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching providers: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/providers",
     *     summary="Add a new course provider , (admin only)",
     *     tags={"Providers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "city_id"},
     *                 @OA\Property(property="name", type="string", example="Udemy"),
     *                 @OA\Property(property="image", type="file"),
     *                 @OA\Property(property="phone", type="string", example="123456789"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="page", type="string", example="https://udemy.com"),
     *                 @OA\Property(property="city_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Provider created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        try {
            $userId = Auth::id();
            $validated = $request->validate([
                'name' => 'required|string|min:2|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
                'phone' => 'nullable|string|min:3|max:15',
                'address' => 'nullable|string|min:3|max:255',
                'page' => 'nullable|string|min:3|max:255',
                'city_id' => 'required|exists:cities,id'
            ]);

            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('images', 'public');
            }

            $provider = Provider::create([
                'name' => $validated['name'],
                'image' => $validated['image'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'page' => $validated['page'] ?? null,
                'city_id' => $validated['city_id'],
                'user_id' => $userId
            ]);

            return response()->json([
                'message' => 'Course provider added successfully!',
                'provider' => $provider
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('Error creating provider: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/providers/{id}",
     *     summary="Get a single course provider by ID",
     *     tags={"Providers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Provider ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Provider found"),
     *     @OA\Response(response=404, description="Provider not found")
     * )
     */
    public function show($id)
    {
        try {
            $provider = Provider::with(['city:id,city', 'reviews.user'])
                ->findOrFail($id);

            return response()->json([
                'provider' => $provider,
                'average_rating' => number_format($provider->average_rating, 1),
                'review_count' => $provider->reviews_count,
                'reviews' => $provider->formatted_reviews
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Provider not found!'], 404);

        } catch (\Exception $e) {
            Log::error('Error fetching provider: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/providers/{id}",
     *     summary="Update an existing course provider, (admin only)",
     *     tags={"Providers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Provider ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="New Name"),
     *                 @OA\Property(property="image", type="file"),
     *                 @OA\Property(property="phone", type="string", example="987654321"),
     *                 @OA\Property(property="address", type="string", example="Updated address"),
     *                 @OA\Property(property="page", type="string", example="https://newpage.com"),
     *                 @OA\Property(property="city_id", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Provider updated"),
     *     @OA\Response(response=404, description="Provider not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $userId = Auth::id();
            $provider = Provider::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $validated = $request->validate([
                'name' => 'string|min:2|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
                'phone' => 'nullable|string|min:3|max:15',
                'address' => 'nullable|string|min:3|max:255',
                'page' => 'nullable|string|min:3|max:255',
                'city_id' => 'exists:cities,id'
            ]);

            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('images', 'public');
            }

            $provider->update($validated);
            return response()->json([
                'message' => 'Course provider updated successfully!',
                'provider' => $provider
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Provider not found!'], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('Error updating provider: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/providers/{id}",
     *     summary="Delete a course provider, (admin only)",
     *     tags={"Providers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Provider ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Provider deleted"),
     *     @OA\Response(response=404, description="Provider not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $userId = Auth::id();
            $provider = Provider::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $provider->delete();
            return response()->json(['message' => 'Course provider deleted successfully!'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Provider not found!'], 404);

        } catch (\Exception $e) {
            Log::error('Error deleting provider: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}
