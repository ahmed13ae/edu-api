<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProviderController extends Controller
{
    public function index(){
        $providers = Provider::with('city:id,city')->paginate(10);
        return response()->json(['providers'=>$providers],200);
    }
    public function store(Request $request){
        $user_id=Auth::user()->id;
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'phone' => 'nullable|string|min:3|max:15',
            'address' => 'nullable|string|min:3|max:255',
            'page' => 'nullable|string|min:3|max:255',
            'city_id'=>'required|exists:cities,id'
        ]);
        if ($request->hasFile('image')) {
            $path=$request->file('image')->store('images','public');
            $validated['image']=$path;
        }

        $provider = Provider::create([
            'name' => $validated['name'],
            'image' => $validated['image']??null,
            'phone' => $validated['phone']??null,
            'address' => $validated['address']??null,
            'page' => $validated['page']??null,
            'city_id' => $validated['city_id'],
            'user_id' => $user_id
        ]);

        return response()->json(["message" => "Course provider added successfully!", "provider" => $provider], 201);
    }
    public function show($id){
        $provider = Provider::with('city:id,city')->find($id);
        if(!$provider){
            return response()->json(["message" => "Provider not found!"], 404);
        }
        return response()->json(['provider'=>$provider],200);
    }
    public function update(Request $request,$id){
        $user_id=Auth::user()->id;
        $provider=Provider::where('id',$id)->where('user_id',$user_id)->first();
        if(!$provider){
            return response()->json(["message" => "Provider not found!"], 404);
        }
        
        $validated = $request->validate([
            'name' => 'string|min:2|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'phone' => 'nullable|string|min:3|max:15',
            'address' => 'nullable|string|min:3|max:255',
            'page' => 'nullable|string|min:3|max:255',
            'city_id'=>'exists:cities,id'
        ]);
        if ($request->hasFile('image')) {
            $path=$request->file('image')->store('images','public');
            $validated['image']=$path;
        }
        
        $provider->update($validated);
        return response()->json(["message" => "Course provider updated successfully!", "provider" => $provider], 200);
    }
    public function destroy(Request $request,$id){
        $user_id=Auth::user()->id;
        $provider=Provider::where('id',$id)->where('user_id',$user_id)->first();
        if(!$provider){
            return response()->json(["message" => "Provider not found!"], 404);
        }
        $provider->delete();
        return response()->json(["message" => "Course provider deleted successfully!"], 200);
    }
}
