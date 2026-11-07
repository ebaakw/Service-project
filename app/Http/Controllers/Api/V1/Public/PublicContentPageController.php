<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContentPageResource;
use App\Models\ContentPage;
use App\Support\SafeEloquent;
use Illuminate\Http\JsonResponse;

class PublicContentPageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        abort_unless(SafeEloquent::tableExists('content_pages'), 404);

        $page = ContentPage::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return (new ContentPageResource($page))->response();
    }
}
