<?php

namespace App\Http\Controllers\Platform;

use App\Enums\PortfolioProjectReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdatePlatformPortfolioProjectRequest;
use App\Models\Company;
use App\Models\PortfolioProject;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformPortfolioProjectController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PortfolioProject::class);

        $query = PortfolioProject::query()
            ->with([
                'company:id,name,name_ar,name_en,slug',
                'serviceCategory:id,name_ar,name_en',
                'technicians.user:id,name',
                'reviewedBy:id,name,email',
                'portfolioImages:id,model_type,model_id,disk,file_path,file_name,mime_type,size,sort_order',
            ])
            ->orderByDesc('id');

        $search = $request->string('search')->trim()->value();
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title_ar', 'like', '%'.$search.'%')
                    ->orWhere('title_en', 'like', '%'.$search.'%');
            });
        }

        $companyId = $request->input('company_id');
        if ($companyId !== null && $companyId !== '') {
            $query->where('company_id', $companyId);
        }

        $projects = $query->paginate(15)->withQueryString()->through(function (PortfolioProject $project): array {
            $images = $project->portfolioImages
                ->map(fn ($media) => MediaUploadService::publicPayload($media))
                ->values();
            $technicians = $project->technicians
                ->map(fn (Technician $technician): array => [
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
                'is_featured' => $project->is_featured,
                'is_active' => $project->is_active,
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
            ];
        });

        return Inertia::render('Platform/Portfolio/Index', [
            'projects' => $projects,
            'filters' => [
                'search' => $search,
                'company_id' => $companyId,
            ],
            'companies' => Company::query()
                ->orderBy('name')
                ->get(['id', 'name', 'name_ar', 'name_en'])
                ->map(fn (Company $company): array => [
                    'id' => $company->id,
                    'name' => $company->display_name,
                ])
                ->values(),
        ]);
    }

    public function edit(PortfolioProject $portfolioProject): Response
    {
        $this->authorize('update', $portfolioProject);

        $portfolioProject->load([
            'portfolioImages',
            'company:id,name',
            'serviceCategory:id,name_ar,name_en',
            'technicians.user:id,name',
        ]);

        $images = $portfolioProject->portfolioImages->map(fn ($m) => [
            'id' => $m->id,
            'url' => MediaUploadService::publicUrl($m),
            'sort_order' => $m->sort_order,
        ]);

        return Inertia::render('Platform/Portfolio/Edit', [
            'project' => [
                ...$portfolioProject->only([
                    'id', 'company_id', 'title_ar', 'title_en', 'description_ar', 'description_en',
                    'location', 'completed_at', 'is_featured', 'is_active',
                    'service_category_id', 'technician_id', 'review_status', 'reviewed_at', 'rejection_reason',
                ]),
                'completed_at' => $portfolioProject->completed_at?->format('Y-m-d'),
                'review_status' => $portfolioProject->review_status?->value,
                'reviewed_at' => $portfolioProject->reviewed_at?->toISOString(),
                'technician_ids' => $portfolioProject->technicians->pluck('id')->values(),
                'images' => $images,
            ],
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'categories' => ServiceCategory::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name_ar', 'name_en']),
            'technicians' => $this->technicianOptions(),
        ]);
    }

    public function update(UpdatePlatformPortfolioProjectRequest $request, PortfolioProject $portfolioProject): RedirectResponse
    {
        $this->authorize('update', $portfolioProject);

        $data = $request->validated();
        $technicianIds = $data['technician_ids'] ?? [];
        unset($data['technician_ids'], $data['images']);

        DB::transaction(function () use ($request, $portfolioProject, $data, $technicianIds): void {
            $data['technician_id'] = $technicianIds[0] ?? null;
            $data['review_status'] = PortfolioProjectReviewStatus::Approved;
            $data['reviewed_by_user_id'] = $request->user()?->getKey();
            $data['reviewed_at'] = now();
            $data['rejection_reason'] = null;

            $portfolioProject->update($data);
            $portfolioProject->technicians()->sync($technicianIds);
        });

        return redirect()
            ->route('platform.portfolio-projects.edit', $portfolioProject)
            ->with('message', 'Portfolio project updated.');
    }

    public function destroy(PortfolioProject $portfolioProject): RedirectResponse
    {
        $this->authorize('delete', $portfolioProject);

        foreach ($portfolioProject->allMedia()->get() as $m) {
            $this->mediaUploadService->deleteMedia($m);
        }

        $portfolioProject->delete();

        return redirect()
            ->route('platform.portfolio-projects.index')
            ->with('message', 'Deleted.');
    }

    private function technicianOptions()
    {
        return Technician::query()
            ->with('user:id,name')
            ->orderBy('company_id')
            ->orderBy('id')
            ->get(['id', 'company_id', 'user_id'])
            ->map(fn (Technician $technician): array => [
                'id' => $technician->id,
                'company_id' => $technician->company_id,
                'user' => [
                    'name' => $technician->user?->name,
                ],
            ])
            ->values();
    }
}
