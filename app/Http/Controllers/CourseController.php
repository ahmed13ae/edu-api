<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    // public function index()
    // {
    //     try {
    //         $courses = Course::with(['provider.city', 'field'])->get();
    //         $data = $courses->map(function ($course) {
    //             return [
    //                 'id' => $course->id,
    //                 'name' => $course->name,
    //                 'price' => $course->price,
    //                 'image' => $course->image,
    //                 'description' => $course->description,
    //                 'content' => $course->content,
    //                 'provider' => $course->provider->name,
    //                 'city' => $course->provider->city->city,
    //                 'field' => $course->field->name
    //             ];
    //         });
    //         return response()->json(['success' => true, 'courses' => $data], 200);
    //     } catch (Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'Failed to fetch courses', 'error' => $e->getMessage()], 500);
    //     }
    // }

        /**
     * @OA\Get(
     *     path="/api/courses",
     *     summary="Get all courses with optional filters",
     *     description="Returns a paginated list of courses, optionally filtered by city and/or field",
     *     operationId="getCourses",
     *     tags={"Courses"},
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="City name to filter courses",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="field",
     *         in="query",
     *         description="Field name to filter courses",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of courses",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total_pages", type="integer"),
     *             @OA\Property(property="total_courses", type="integer"),
     *             @OA\Property(property="courses_per_page", type="integer"),
     *             @OA\Property(
     *                 property="courses",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="price", type="integer"),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="provider", type="string"),
     *                     @OA\Property(property="city", type="string"),
     *                     @OA\Property(property="field", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Failed to fetch courses")
     * )
     */
    public function index(Request $request)
{
    try{
    $city = $request->query('city');
    $field = $request->query('field');
    $page = $request->query('page', 1); 
    $perPage = 2; 
    $offset = ($page - 1) * $perPage; 

    // Base query
    $query = "
        SELECT SQL_CALC_FOUND_ROWS courses.id, courses.name, courses.price, courses.image, 
               courses.description, courses.content, providers.id AS provider_id, 
               providers.name AS provider, cities.city AS city, 
               fields.name AS field
        FROM courses
        JOIN providers ON courses.provider_id = providers.id
        JOIN cities ON providers.city_id = cities.id
        JOIN fields ON courses.field_id = fields.id
        WHERE 1=1
    ";

    // Parameters for binding (to prevent SQL injection)
    $bindings = [];
    
    if ($city) {
        $query .= " AND cities.city = ?";
        $bindings[] = $city;
    }

    if ($field) {
        $query .= " AND fields.name = ?";
        $bindings[] = $field;
    }

    // Add pagination (LIMIT and OFFSET)
    $query .= " LIMIT ? OFFSET ?";
    $bindings[] = $perPage;
    $bindings[] = $offset;

    // Execute the query
    $courses = DB::select($query, $bindings);

    // Get total count of results without pagination (for frontend navigation)
    $total = DB::select("SELECT FOUND_ROWS() as total")[0]->total;
    $totalPages = ceil($total / $perPage);

    return response()->json([
        'success' => true,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_courses' => $total,
        'courses_per_page' => $perPage,
        'courses' => $courses
    ], 200);}
    catch (Exception $e) {
        return response()->json(['success' => false, 'message' => 'Failed to fetch courses', 'error' => $e->getMessage()], 500);
    }
}

        /**
     * @OA\Post(
     *     path="/api/courses",
     *     summary="Store a new course",
     *     description="Creates a new course with validated data and stores it in the database.",
     *     operationId="storeCourse",
     *     tags={"Courses"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "price", "provider_id", "field_id"},
     *                 @OA\Property(property="name", type="string", example="Laravel Mastery"),
     *                 @OA\Property(property="price", type="integer", example=200),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="field_id", type="integer", example=3),
     *                 @OA\Property(property="description", type="string", example="A complete Laravel 11 course."),
     *                 @OA\Property(property="image", type="file", format="binary"),
     *                 @OA\Property(property="content", type="file", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Course created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course created successfully"),
     *             @OA\Property(property="course", type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="name", type="string", example="Laravel Mastery"),
     *                 @OA\Property(property="price", type="integer", example=200),
     *                 @OA\Property(property="image", type="string", example="images/laravel.png"),
     *                 @OA\Property(property="content", type="string", example="content/laravel.pdf"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - user not authenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create course"
     *     )
     * )
     */
    public function store(StoreCourseRequest $request)
    {
        $user_id=Auth::user()->id;
        $validated=$request->validated();
        $validated['user_id']=$user_id;
        if ($request->hasFile('image')) {
            $path=$request->file('image')->store('images','public');
            $validated['image']=$path;
        }
        if ($request->hasFile('content')) {
            $path=$request->file('content')->store('content','public');
            $validated['content']=$path;
        }
        try {
            $data = Course::create($validated);
            return response()->json(['success' => true, 'course' => $data, 'message' => 'Course created successfully'], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create course', 'error' => $e->getMessage()], 500);
        }
    }

        /**
     * @OA\Put(
     *     path="/api/courses/{id}",
     *     summary="Update an existing course",
     *     description="Updates a course owned by the authenticated user",
     *     operationId="updateCourse",
     *     tags={"Courses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the course to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Updated Course"),
     *                 @OA\Property(property="price", type="integer", example=300),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="image", type="file", format="binary"),
     *                 @OA\Property(property="content", type="file", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Provider not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Failed to update course")
     * )
     */
    public function update(Request $request,$id){
        $user_id=Auth::user()->id;
        $course=Course::where('id',$id)->where('user_id',$user_id)->first();
        if(!$course){
            return response()->json(["message" => "Provider not found!"], 404);
        }
        
        $validated = $request->validate([
            'name' => 'string|min:2|max:255',
            "price"=>"integer",
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'description' => 'nullable|string|max:1000',
            'content'=>'nullable|file|mimes:pdf,doc,docx|max:30720',
        ]);
        if ($request->hasFile('image')) {
            $path=$request->file('image')->store('images','public');
            $validated['image']=$path;
        }
        if ($request->hasFile('content')) {
            $path=$request->file('content')->store('content','public');
            $validated['content']=$path;
        }
        
        $course->update($validated);
        return response()->json(["message" => "Course updated successfully!", "provider" => $course], 200);
    }

        /**
     * @OA\Get(
     *     path="/api/courses/{id}",
     *     summary="Get a specific course by ID",
     *     description="Returns details of a specific course including provider, field, and reviews",
     *     operationId="getCourseById",
     *     tags={"Courses"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the course",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course details",
     *         @OA\JsonContent(
     *             @OA\Property(property="course", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Course not found"),
     *     @OA\Response(response=500, description="Failed to fetch course")
     * )
     */
    public function show($id)
{
    try {
        $course = Course::with(['provider', 'field','reviews.user'])->findOrFail($id);

        $data = [
            'id' => $course->id,
            'name' => $course->name,
            'price' => $course->price,
            'image' => $course->image,
            'description' => $course->description,
            'content' => $course->content,
            'provider' => [
                'id' => $course->provider->id,
                'name' => $course->provider->name,
                'city' => $course->provider->city->city 
            ],
            'field' => [
                'id' => $course->field->id,
                'name' => $course->field->name
            ],
            'average_rating' => number_format($course->average_rating, 1),
            'review_count' => $course->reviews_count,
            'reviews' => $course->formatted_reviews
        ];

        return response()->json([ 'course' => $data], 200);
    } catch (Exception $e) {
        return response()->json([ 'message' => 'Failed to fetch course', 'error' => $e->getMessage()], 500);
    }
}

        /**
     * @OA\Delete(
     *     path="/api/courses/{id}",
     *     summary="Delete a course",
     *     description="Deletes a course by ID",
     *     operationId="deleteCourse",
     *     tags={"Courses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the course to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Course deleted successfully"),
     *     @OA\Response(response=404, description="Course not found"),
     *     @OA\Response(response=500, description="Failed to delete course")
     * )
     */
    public function destroy($id)
    {
        try {
            $course = Course::findOrFail($id);
            $course->delete();
            return response()->json([ 'message' => 'Course deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to delete course', 'error' => $e->getMessage()], 500);
        }
    }
}