<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;
use React\Socket\Server;

#[OA\Info(
    version: "1.0.0",
    x: [
        "logo" => [
            "url" => "https://via.placeholder.com/190x90.png?text=L5-Swagger"
        ]
    ],
    title: "Image service api",
    description: "Service for image uploads",
    contact: new OA\Contact(
        email: "bdrbt.com@gmail.com"
    ),
)]
#[OA\Server(
    url:"http://localhost:8080"
)]
#[OA\PathItem(
    path:"/api",
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter Sanctum Bearer Token"
)]
abstract class Controller extends \Illuminate\Routing\Controller
{
    //
}
