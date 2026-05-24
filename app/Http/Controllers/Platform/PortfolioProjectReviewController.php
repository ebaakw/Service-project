<?php

namespace App\Http\Controllers\Platform;

use App\Enums\PortfolioProjectReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PortfolioProject;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioProjectReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewReviews', PortfolioProject::class);

        $search = $request->string('search')->trim()->value();
        $reviewStatus = $request->string('review_status')->trim()->value();
        $reviewStatus = $reviewStatus !== '' ? strtolower($reviewStatus) : PortfolioProjectReviewStatus::Pending->value;
        $companyId = $request->integer('company_id') ?: null;
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = PortfolioProject::query()
            ->with([
                'company:id,name,name_ar,name_en,slug',
                'serviceCategory:id,name_ar,name_en',
                'technicians.user:id,name',
                'reviewedBy:id,name,email',
                'portfolioImages:id,model_type,model_id,disk,file_path,file_name,mime_type,size,sort_order',
            ])
            ->orderByDesc('id');

        if ($reviewStatus !== 'all') {
            $query->where('review_status', $reviewStatus);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('title_ar', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhereHas('company', function ($cq) use ($like): void {
                        $cq->where('name', 'like', $like)
                            ->orWhere('name_ar', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    });
            });
        }

        $projects = $query->paginate($perPage)->withQueryString();

        $projects->through(function (PortfolioProject $project) use ($request): array {
            $images = $project->portfolioImages
                ->map(fn ($media) => MediaUploadService::publicPayload($media))
                ->values();
            $technicians = $project->technicians
                ->map(fn ($technician): array => [
                    'id' => $technician->id,
                    'user' => [
                        'name' => $technician->user?->name,
                    ],
                ])
                ->values();

            return [
                'id' => $project->id,
                'title_ar' => $project->title_ar,
                'title_en' => $project->title_en,
                'description_ar' => $project->description_ar,
                'description_en' => $project->description_en,
                'location' => $project->location,
                'location_ar' => $project->location_ar,
                'location_en' => $project->location_en,
                'completed_at' => $project->completed_at?->format('Y-m-d'),
                'is_active' => $project->is_active,
                'is_featured' => $project->is_featured,
                'review_status' => $project->review_status?->value,
                'reviewed_at' => $project->reviewed_at?->toISOString(),
                'rejection_reason' => $project->rejection_reason,
                'created_at' => $project->created_at?->toISOString(),
                'thumbnail_url' => $images->first()['url'] ?? null,
                'images_count' => $images->count(),
                'images' => $images,
                'company' => $project->company ? [
                    'id' => $project->company->id,
                    'name' => $project->company->display_name,
                    'slug' => $project->company->slug,
                ] : null,
                'service_category' => $project->serviceCategory ? [
                    'id' => $project->serviceCategory->id,
                    'name_ar' => $project->serviceCategory->name_ar,
                    'name_en' => $project->serviceCategory->name_en,
                ] : null,
                'technicians' => $technicians,
                'technician' => $technicians->first(),
                'reviewed_by' => $project->reviewedBy?->only(['id', 'name', 'email']),
                'can' => [
                    'approve' => $request->user()?->can('approveReview', $project) ?? false,
                    'reject' => $request->user()?->can('rejectReview', $project) ?? false,
                ],
            ];
        });

        $companies = Company::query()
            ->orderBy('id')
            ->get(['id', 'name', 'name_ar', 'name_en', 'slug'])
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->display_name,
            ])
            ->values()
            ->all();

        return Inertia::render('Platform/PortfolioReviews/Index', [
            'projects' => $projects,
            'filters' => [
                'search' => $search,
                'review_status' => $reviewStatus,
                'company_id' => $companyId,
                'per_page' => $perPage,
            ],
            'companies' => $companies,
        ]);
    }

    public function approve(Request $request, PortfolioProject $portfolioProject): RedirectResponse
    {
        $this->authorize('approveReview', $portfolioProject);

        if (! in_array($portfolioProject->review_status, [PortfolioProjectReviewStatus::Pending, PortfolioProjectReviewStatus::Rejected], true)) {
            abort(422);
        }

        $portfolioProject->forceFill([
            'review_status' => PortfolioProjectReviewStatus::Approved,
            'reviewed_by_user_id' => $request->user()?->getKey(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
            'is_active' => true,
        ])->save();

        return redirect()->back()
            ->with('type', 'success')
            ->with('message', __('dashboard.portfolio_reviews.approved_successfully'));
    }

    public function reject(Request $request, PortfolioProject $portfolioProject): RedirectResponse
    {
        $this->authorize('rejectReview', $portfolioProject);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $portfolioProject->forceFill([
            'review_status' => PortfolioProjectReviewStatus::Rejected,
            'reviewed_by_user_id' => $request->user()?->getKey(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
            'is_active' => false,
        ])->save();

        return redirect()->back()
            ->with('type', 'success')
            ->with('message', __('dashboard.portfolio_reviews.rejected_successfully'));
    }
}
