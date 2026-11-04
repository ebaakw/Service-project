<?php

namespace App\Http\Controllers\Platform;

use App\Enums\BannerLinkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreBannerRequest;
use App\Http\Requests\Platform\UpdateBannerRequest;
use App\Models\Banner;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Banner::class);

        $actor = $request->user();

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $query = Banner::query()->orderBy('sort_order')->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title_ar', 'like', '%'.$search.'%')
                    ->orWhere('title_en', 'like', '%'.$search.'%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $banners = $query->paginate($perPage)->withQueryString();

        $banners->through(function (Banner $banner) use ($actor): array {
            return [
                'id' => $banner->id,
                'title_ar' => $banner->title_ar,
                'title_en' => $banner->title_en,
                'link_type' => $banner->link_type?->value,
                'link_value' => $banner->link_value,
                'sort_order' => $banner->sort_order,
                'is_active' => (bool) $banner->is_active,
                'image_url' => $banner->imageUrl(),
                'starts_at' => $banner->starts_at?->toIso8601String(),
                'ends_at' => $banner->ends_at?->toIso8601String(),
                'can_update' => $actor !== null && $actor->can('update', $banner),
                'can_delete' => $actor !== null && $actor->can('delete', $banner),
            ];
        });

        return Inertia::render('Platform/Banners/Index', [
            'banners' => $banners,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $actor !== null && $actor->can('create', Banner::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Banner::class);

        return Inertia::render('Platform/Banners/Create', [
            'linkTypes' => BannerLinkType::values(),
        ]);
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $this->authorize('create', Banner::class);

        $data = $request->safe()->except(['image']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;
        $data['link_value'] = ($data['link_value'] ?? '') !== '' ? $data['link_value'] : null;

        $banner = Banner::query()->create($data);

        if ($request->hasFile('image')) {
            $this->mediaUploadService->storeBannerImage($banner, $request->file('image'));
        }

        return redirect()
            ->route('platform.banners.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function edit(Banner $banner): Response
    {
        $this->authorize('update', $banner);

        return Inertia::render('Platform/Banners/Edit', [
            'banner' => [
                'id' => $banner->id,
                'title_ar' => $banner->title_ar,
                'title_en' => $banner->title_en,
                'subtitle_ar' => $banner->subtitle_ar,
                'subtitle_en' => $banner->subtitle_en,
                'cta_label_ar' => $banner->cta_label_ar,
                'cta_label_en' => $banner->cta_label_en,
                'link_type' => $banner->link_type?->value ?? BannerLinkType::None->value,
                'link_value' => $banner->link_value,
                'sort_order' => $banner->sort_order,
                'is_active' => (bool) $banner->is_active,
                'image_url' => $banner->imageUrl(),
                'starts_at' => $banner->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $banner->ends_at?->format('Y-m-d\TH:i'),
            ],
            'linkTypes' => BannerLinkType::values(),
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $this->authorize('update', $banner);

        $data = $request->safe()->except(['image']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;
        $data['link_value'] = ($data['link_value'] ?? '') !== '' ? $data['link_value'] : null;

        $banner->update($data);

        if ($request->hasFile('image')) {
            $this->mediaUploadService->storeBannerImage($banner, $request->file('image'));
        }

        return redirect()
            ->route('platform.banners.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->authorize('delete', $banner);

        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return redirect()
            ->route('platform.banners.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }
}
