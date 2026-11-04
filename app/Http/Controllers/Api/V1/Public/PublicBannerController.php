<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

class PublicBannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = Banner::query()
            ->currentlyVisible()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return BannerResource::collection($banners)->response();
    }
}
