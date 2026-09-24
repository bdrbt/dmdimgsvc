<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

class ImageController
{
    use AuthorizesRequests;

    public function __construct(
        protected ImageService $imageService
    ) {}

    /**
     * list of imges for current user
     */
    #[OA\Get(
        path: "/api/images",
        summary: "Retrieve paginated users images",
        security: [["bearerAuth" => []]],
        tags: ["Images"],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Page number",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Images per page (max 100)",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "search by original file name",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Sorting field",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["created_at", "original_name", "size"],
                    default: "created_at"
                )
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                description: "Направление сортировки",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["asc", "desc"],
                    default: "desc"
                )
            )
        ]
    )]
    #[OA\Response(
    response: 200,
    description: "Paginated list of images",
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: "data",
                type: "array",
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "original_name", type: "string", example: "photo.jpg"),
                        new OA\Property(property: "file_hash", type: "string", example: "e3b0c44298fc1c149..."),
                        new OA\Property(property: "created_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Property(
                property: "links",
                type: "object",
                properties: [
                    new OA\Property(property: "first", type: "string", nullable: true),
                    new OA\Property(property: "last", type: "string", nullable: true),
                    new OA\Property(property: "prev", type: "string", nullable: true),
                    new OA\Property(property: "next", type: "string", nullable: true)
                ]
            ),
            new OA\Property(
                property: "meta",
                type: "object",
                properties: [
                    new OA\Property(property: "current_page", type: "integer", example: 1),
                    new OA\Property(property: "from", type: "integer", example: 1),
                    new OA\Property(property: "last_page", type: "integer", example: 5),
                    new OA\Property(property: "per_page", type: "integer", example: 15),
                    new OA\Property(property: "to", type: "integer", example: 15),
                    new OA\Property(property: "total", type: "integer", example: 72)
                ]
            )
        ]
    )
    )]
    #[OA\Response(response: 401, description: "Unauthenticated")]
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $images = $request->user()
                          ->images()
                          ->with('storedFile')
                          ->latest('id')
                          ->paginate($perPage);

        return ImageResource::collection($images);
    }

    #[OA\Post(
        path: "/api/images",
        summary: "Upload an image with streaming hash deduplication",
        security: [["bearerAuth" => []]],
        tags: ["Images"]
    )]
    #[OA\RequestBody(
    required: true,
    content: new OA\MediaType(
        mediaType: "multipart/form-data",
        schema: new OA\Schema(
            required: ["image"],
            properties: [
                new OA\Property(
                    property: "image",
                    description: "JPEG/PNG image file",
                    type: "string",
                    format: "binary"
                )
            ]
        )
    )
    )]
    #[OA\Response(
    response: 201,
    description: "Image processed and saved",
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: "data",
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "original_name", type: "string", example: "photo.jpg"),
                    new OA\Property(property: "file_hash", type: "string", example: "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"),
                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                ]
            )
        ]
    )
    )]
    #[OA\Response(response: 401, description: "Unauthenticated")]
    #[OA\Response(response: 422, description: "Daily limit reached or invalid image")]
    public function store(StoreImageRequest $request): JsonResponse
    {
        $file = $request->file('image');
        $image = $this->imageService->storeImage($file, $request->user());

        $image->load('storedFile');

        return response()->json([
            'message' => 'Image uploaded successfully',
            'data'    => new ImageResource($image),
        ], 201);
    }

    /**
     * remove users image
     */
    public function destroy(Image $image): JsonResponse
    {
        $this->authorize('delete', $image);

        $this->imageService->deleteImage($image);

        return response()->json([
            'message' => 'Image deleted successfully',
        ], 200);
    }
}
