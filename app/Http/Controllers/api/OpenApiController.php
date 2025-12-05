<?php

namespace App\Http\Controllers\api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Himalayan Yoga API",
    description: "API documentation for Himalayan Yoga application"
)]
#[OA\Server(
    url: "/api",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class OpenApiController
{
    // This class is used only for OpenAPI documentation metadata
}

