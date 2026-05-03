<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformPortfolioProjectRequest;
use App\Http\Requests\Platform\UpdatePlatformPortfolioProjectRequest;
use App\Models\Company;
use App\Models\PortfolioProject;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['company:id,name', 'serviceCategory:id,name_ar', 'technician.user:id,name'])
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

        return Inertia::render('Platform/Portfolio/Index', [
            'projects' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
                'company_id' => $companyId,
            ],
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PortfolioProject::class);

        return Inertia::render('Platform/Portfolio/Create', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'categories' => ServiceCategory::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function store(StorePlatformPortfolioProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', PortfolioProject::class);

        $data = $request->validated();
        $project = PortfolioProject::query()->create($data);

        return redirect()
            ->route('platform.portfolio-projects.edit', $project)
            ->with('message', 'Portfolio project created.');
    }

    public function edit(PortfolioProject $portfolioProject): Response
    {
        $this->authorize('update', $portfolioProject);

        $portfolioProject->load([
            'portfolioImages',
            'company:id,name',
            'serviceCategory:id,name_ar,name_en',
            'technician.user:id,name',
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
                    'service_category_id', 'technician_id',
                ]),
                'completed_at' => $portfolioProject->completed_at?->format('Y-m-d'),
                'images' => $images,
            ],
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'categories' => ServiceCategory::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name_ar', 'name_en']),
            'technicians' => Technician::query()
                ->where('company_id', $portfolioProject->company_id)
                ->where('is_active', true)
                ->with('user:id,name')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function update(UpdatePlatformPortfolioProjectRequest $request, PortfolioProject $portfolioProject): RedirectResponse
    {
        $this->authorize('update', $portfolioProject);

        $portfolioProject->update($request->validated());

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
}
