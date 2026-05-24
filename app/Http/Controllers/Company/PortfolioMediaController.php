<?php

namespace App\Http\Controllers\Company;

use App\Enums\PortfolioProjectReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StorePortfolioImageRequest;
use App\Models\Media;
use App\Models\PortfolioProject;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;

class PortfolioMediaController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService
    ) {}

    public function store(
        StorePortfolioImageRequest $request,
        PortfolioProject $portfolioProject
    ): RedirectResponse {
        $this->authorize('uploadImages', $portfolioProject);

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

        $portfolioProject->forceFill([
            'is_active' => false,
            'review_status' => PortfolioProjectReviewStatus::Pending,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ])->save();

        return redirect()
            ->route('company.portfolio-projects.edit', $portfolioProject)
            ->with('type', 'success')
            ->with('message', __('tenant.portfolio_projects.image_uploaded_for_review'));
    }

    public function destroy(PortfolioProject $portfolioProject, Media $portfolio_media): RedirectResponse
    {
        $this->authorize('uploadImages', $portfolioProject);

        abort_unless(
            $portfolio_media->model_type === PortfolioProject::class
            && (int) $portfolio_media->model_id === (int) $portfolioProject->getKey()
            && $portfolio_media->collection_name === 'portfolio',
            404
        );

        $this->mediaUploadService->deleteMedia($portfolio_media);

        $portfolioProject->forceFill([
            'is_active' => false,
            'review_status' => PortfolioProjectReviewStatus::Pending,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ])->save();

        return redirect()
            ->route('company.portfolio-projects.edit', $portfolioProject)
            ->with('type', 'success')
            ->with('message', __('tenant.portfolio_projects.image_removed_for_review'));
    }
}
