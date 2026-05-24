<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StorePortfolioImageRequest;
use App\Models\Media;
use App\Models\PortfolioProject;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;

class PlatformPortfolioMediaController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService
    ) {}

    public function store(
        StorePortfolioImageRequest $request,
        PortfolioProject $portfolioProject
    ): RedirectResponse {
        $this->authorize('update', $portfolioProject);

        $sortOrder = $request->input('sort_order') !== null ? (int) $request->input('sort_order') : null;
        $images = $request->file('images', []);
        $images = is_array($images) ? $images : [$images];
        if ($request->hasFile('image')) {
            $images[] = $request->file('image');
        }
        $images = array_values(array_filter($images));

        foreach ($images as $image) {
            $this->mediaUploadService->storePortfolioImage($portfolioProject, $image, $sortOrder);
            $sortOrder = null;
        }

        return redirect()
            ->route('platform.portfolio-projects.edit', $portfolioProject)
            ->with('message', 'Image uploaded.');
    }

    public function destroy(PortfolioProject $portfolioProject, Media $portfolio_media): RedirectResponse
    {
        $this->authorize('update', $portfolioProject);

        abort_unless(
            $portfolio_media->model_type === PortfolioProject::class
            && (int) $portfolio_media->model_id === (int) $portfolioProject->getKey()
            && $portfolio_media->collection_name === 'portfolio',
            404
        );

        $this->mediaUploadService->deleteMedia($portfolio_media);

        return redirect()
            ->route('platform.portfolio-projects.edit', $portfolioProject)
            ->with('message', 'Image removed.');
    }
}
