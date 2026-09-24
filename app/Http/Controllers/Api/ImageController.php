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

class ImageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ImageService $imageService
    ) {}

    /**
     * list of imges for current user
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $images = $request->user()
            ->images()
            ->with('storedFile')
            ->latest()
            ->paginate(15);

        return ImageResource::collection($images);
    }

    /**
     * upload new image for current user
     */
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
