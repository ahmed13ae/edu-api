<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Edu app API",
 *     version="1.0.0",
 *     description="API documentation for Edu app.",
 *     @OA\Contact(
 *         email="eamhel98@gmail.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Base URL"
 * )
 */
class SwaggerInfo {}
