<?php

namespace App\Http\Controllers;

use App\Models\Field;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Fields",
 *     description="Operations related to fields of study"
 * )
*/
class FieldController extends Controller
{
    
    /**
     * @OA\Get(
     *     path="/api/fields",
     *     summary="Get all fields",
     *     tags={"Fields"},
     *     @OA\Response(
     *         response=200,
     *         description="List of fields",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Computer Science")
     *             )
     *         )
     *     )
     * )
     */
    public function index(){
        $data=Field::all();
        return response()->json($data,200);
    }
}
