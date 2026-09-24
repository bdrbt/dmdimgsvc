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
    title: "L5 OpenApi",
    description: "L5 Swagger OpenApi description",
    contact: new OA\Contact(
        email: "darius@matulionis.lt"
    ),
    license: new OA\License(
        name: "Apache 2.0",
        url: "https://www.apache.org/licenses/LICENSE-2.0.html"
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
