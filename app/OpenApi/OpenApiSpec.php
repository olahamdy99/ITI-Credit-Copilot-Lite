<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Credit Copilot Lite API",
 *     version="1.0.0",
 *     description="REST API for Credit Copilot Lite"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8001",
 *     description="Local development server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token"
 * )
 */
class OpenApiSpec {}
