<?php

namespace App\Http\Controllers\Company;

use App\Enums\PortfolioProjectReviewStatus;
use App\Enums\TechnicianReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StorePortfolioProjectRequest;
use App\Http\Requests\Company\UpdatePortfolioProjectRequest;
use App\Models\PortfolioProject;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioProjectController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PortfolioProject::class);

        $company = current_company();
        abort_if($company === null, 403);

        $query = PortfolioProject::query()
            ->where('company_id', $company->getKey())
            ->with([
                'serviceCategory:id,name_ar,name_en',
                'technicians.user:id,name',
                'portfolioImages:id,model_type,model_id,disk,file_path,sort_order',
            ])
            ->orderByDesc('id');

        $search = $request->string('search')->trim()->value();
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title_ar', 'like', '%'.$search.'%')
                    ->orWhere('title_en', 'like', '%'.$search.'%');
            });
        }

        $reviewStatus = $request->string('review_status')->trim()->value();
        $reviewStatus = $reviewStatus !== '' ? strtolower($reviewStatus) : 'all';
        if ($reviewStatus !== 'all') {
            $query->where('review_status', $reviewStatus);
        }

        $categoryId = $request->input('service_category_id');
        if ($categoryId !== null && $categoryId !== '') {
            $query->where('service_category_id', $categoryId);
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        return Inertia::render('Company/Portfolio/Index', [
            'projects' => $query->paginate($perPage)->withQueryString()->through(function (PortfolioProject $project): array {
                $thumbnail = $project->portfolioImages->first();
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
                    'is_featured' => $project->is_featured,
                    'is_active' => $project->is_active,
                    'review_status' => $project->review_status?->value,
                    'rejection_reason' => $project->rejection_reason,
                    'created_at' => $project->created_at?->toISOString(),
                    'thumbnail_url' => $thumbnail ? MediaUploadService::publicUrl($thumbnail) : null,
                    'images_count' => $project->portfolioImages->count(),
                    'service_category' => $project->serviceCategory ? [
                        'id' => $project->serviceCategory->id,
                        'name_ar' => $project->serviceCategory->name_ar,
                        'name_en' => $project->serviceCategory->name_en,
                    ] : null,
                    'technicians' => $technicians,
                    'technician' => $technicians->first(),
                    'can' => [
                        'update' => request()->user()?->can('update', $project) ?? false,
                        'delete' => request()->user()?->can('delete', $project) ?? false,
                        'upload_images' => request()->user()?->can('uploadImages', $project) ?? false,
                    ],
                ];
            }),
            'filters' => [
                'search' => $search,
                'review_status' => $reviewStatus,
                'service_category_id' => $categoryId,
                'per_page' => $perPage,
            ],
            'categories' => $this->activeCategories(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PortfolioProject::class);

        $company = current_company();
        abort_if($company === null, 403);

        return Inertia::render('Company/Portfolio/Create', [
            'categories' => $this->activeCategories(),
            'technicians' => $this->availableTechnicians((int) $company->getKey()),
        ]);
    }

    public function store(StorePortfolioProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', PortfolioProject::class);

        $company = current_company();
        abort_if($company === null, 403);

        $data = $request->validated();
        $images = $request->file('images', []);
        $images = is_array($images) ? $images : [$images];
        if ($request->hasFile('image')) {
            $images[] = $request->file('image');
        }
        $images = array_values(array_filter($images));
        $technicianIds = $data['technician_ids'] ?? [];

        unset($data['image'], $data['images'], $data['technician_ids'], $data['is_active']);

        $project = DB::transaction(function () use ($company, $data, $images, $technicianIds): PortfolioProject {
            $data['company_id'] = $company->getKey();
            $data['technician_id'] = $technicianIds[0] ?? null;
            $data['is_active'] = false;
            $data['review_status'] = PortfolioProjectReviewStatus::Pending;
            $data['reviewed_by_user_id'] = null;
            $data['reviewed_at'] = null;
            $data['rejection_reason'] = null;

            $project = PortfolioProject::query()->create($data);
            $project->technicians()->sync($technicianIds);

            foreach ($images as $image) {
                $this->mediaUploadService->storePortfolioImage($project, $image);
            }

            return $project;
        });

        return redirect()
            ->route('company.portfolio-projects.index')
            ->with('type', 'success')
            ->with('message', __('tenant.portfolio_projects.submitted_for_review'));
    }

    public function edit(PortfolioProject $portfolioProject): Response
    {
        $this->authorize('view', $portfolioProject);

        $company = current_company();
        abort_if($company === null, 403);

        $portfolioProject->load([
            'portfolioImages',
            'serviceCategory:id,name_ar,name_en',
            'technicians.user:id,name',
        ]);

        $images = $portfolioProject->portfolioImages->map(fn ($m) => [
            'id' => $m->id,
            'url' => MediaUploadService::publicUrl($m),
            'sort_order' => $m->sort_order,
        ]);

        return Inertia::render('Company/Portfolio/Edit', [
            'project' => [
                ...$portfolioProject->only([
                    'id', 'title_ar', 'title_en', 'description_ar', 'description_en',
                    'location', 'completed_at', 'is_featured', 'is_active',
                    'service_category_id', 'technician_id',
                    'review_status', 'reviewed_at', 'rejection_reason',
                ]),
                'completed_at' => $portfolioProject->completed_at?->format('Y-m-d'),
                'review_status' => $portfolioProject->review_status?->value,
                'reviewed_at' => $portfolioProject->reviewed_at?->toISOString(),
                'technician_ids' => $portfolioProject->technicians->pluck('id')->values(),
                'images' => $images,
                'can' => [
                    'update' => request()->user()?->can('update', $portfolioProject) ?? false,
                    'delete' => request()->user()?->can('delete', $portfolioProject) ?? false,
                    'upload_images' => request()->user()?->can('uploadImages', $portfolioProject) ?? false,
                ],
            ],
            'categories' => $this->activeCategories(),
            'technicians' => $this->availableTechnicians(
                (int) $company->getKey(),
                $portfolioProject->technicians->pluck('id')->all()
            ),
        ]);
    }

    public function update(UpdatePortfolioProjectRequest $request, PortfolioProject $portfolioProject): RedirectResponse
    {
        $this->authorize('update', $portfolioProject);

        $data = $request->validated();
        $technicianIds = $data['technician_ids'] ?? [];
        unset($data['technician_ids'], $data['is_active']);

        $data['technician_id'] = $technicianIds[0] ?? null;
        $data['is_active'] = false;
        $data['review_status'] = PortfolioProjectReviewStatus::Pending;
        $data['reviewed_by_user_id'] = null;
        $data['reviewed_at'] = null;
        $data['rejection_reason'] = null;

        DB::transaction(function () use ($portfolioProject, $data, $technicianIds): void {
            $portfolioProject->update($data);
            $portfolioProject->technicians()->sync($technicianIds);
        });

        return redirect()
            ->route('company.portfolio-projects.index')
            ->with('type', 'success')
            ->with('message', __('tenant.portfolio_projects.submitted_for_review'));
    }

    public function destroy(PortfolioProject $portfolioProject): RedirectResponse
    {
        $this->authorize('delete', $portfolioProject);

        foreach ($portfolioProject->allMedia as $m) {
            $this->mediaUploadService->deleteMedia($m);
        }

        $portfolioProject->delete();

        return redirect()
            ->route('company.portfolio-projects.index')
            ->with('type', 'success')
            ->with('message', __('tenant.portfolio_projects.deleted'));
    }

    private function activeCategories()
    {
        return ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name_ar', 'name_en']);
    }

    /**
     * @param  array<int, int>  $includeTechnicianIds
     */
    private function availableTechnicians(int $companyId, array $includeTechnicianIds = [])
    {
        return Technician::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($includeTechnicianIds): void {
                $query->where(function ($active): void {
                    $active->where('is_active', true)
                        ->where('review_status', TechnicianReviewStatus::Approved->value);
                });

                if ($includeTechnicianIds !== []) {
                    $query->orWhereIn('id', $includeTechnicianIds);
                }
            })
            ->with('user:id,name')
            ->orderBy('id')
            ->get();
    }
}
