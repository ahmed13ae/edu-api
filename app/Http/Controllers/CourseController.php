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
    /**
     * Retrieve all courses.
     *
     * Fetches all courses from the database and returns them as JSON.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing course data or an error message.
     */
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
     * Store a new course.
     *
     * Validates and creates a new course record in the database.
     *
     * @param StoreCourseRequest $request The validated request containing course data.
     * @return \Illuminate\Http\JsonResponse JSON response with success message and course data.
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
     * Update an existing course.
     *
     * Finds a course by ID, validates request data, and updates the course.
     *
     * @param Request $request The request containing updated course data.
     * @param int $id The ID of the course to update.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
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
     * Retrieve a specific course.
     *
     * Fetches a single course by its ID.
     *
     * @param int $id The ID of the course to retrieve.
     * @return \Illuminate\Http\JsonResponse JSON response containing course data or an error message.
     */
    public function show($id)
{
    try {
        $course = Course::with(['provider', 'field'])->findOrFail($id);

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
            ]
        ];

        return response()->json([ 'course' => $data], 200);
    } catch (Exception $e) {
        return response()->json([ 'message' => 'Failed to fetch course', 'error' => $e->getMessage()], 500);
    }
}

    /**
     * Delete a course.
     *
     * Finds a course by ID and deletes it from the database.
     *
     * @param int $id The ID of the course to delete.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
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
