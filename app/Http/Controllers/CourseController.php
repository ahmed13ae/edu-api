<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class CourseController extends Controller
{
    /**
     * Retrieve all courses.
     *
     * Fetches all courses from the database and returns them as JSON.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing course data or an error message.
     */
    public function index()
    {
        try {
            $data = Course::all();
            return response()->json(['success' => true, 'courses' => $data], 200);
        } catch (Exception $e) {
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
        try {
            $data = Course::create($request->validated());
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
    public function update(Request $request, $id)
    {
        try {
            $course = Course::findOrFail($id);
            $validated = $request->validate([
                'name' => 'string',
                'price' => 'integer'
            ]);
            $course->update($validated);

            return response()->json(['success' => true, 'message' => 'Course updated successfully', 'course' => $course], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Course not found'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update course', 'error' => $e->getMessage()], 500);
        }
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
            $data = Course::findOrFail($id);
            return response()->json(['success' => true, 'course' => $data], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Course not found'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch course', 'error' => $e->getMessage()], 500);
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
            return response()->json(['success' => true, 'message' => 'Course deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Course not found'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete course', 'error' => $e->getMessage()], 500);
        }
    }
}
