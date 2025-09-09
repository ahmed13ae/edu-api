<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;


class CityController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/cities",
 *     summary="Get all cities",
 *     description="Returns a list of all available cities.",
 *     operationId="getCities",
 *     tags={"Cities"},
 *     @OA\Response(
 *         response=200,
 *         description="List of cities retrieved successfully",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="city", type="string", example="Cairo")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
    public function index(){
        $data=City::all();
        return response()->json($data,200);
    }
}
