<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(){
        $data=Course::all();
        return response()->json($data,200);
        
    }
    //using the request class for validation
    public function store(StoreCourseRequest $request){
        $data=Course::create($request->validated());
        return response()->json($data,201);
    }
    public function update(Request $request,$id){
        $course=Course::findOrFail($id);
        $validated=$request->validate([
            'name'=>'string',
            'price'=>'integer'
        ]);
        $data=$course->update($validated);
        return response()->json($data,200);
    }
    public function show($id){
        $data=Course::findOrFail($id);
        return response()->json($data,200);
    }
    public function destroy($id){
        $course=Course::findOrFail($id);
        $course->delete();
        return response()->json(["message"=>"resource deleted"],204);
    }
}
