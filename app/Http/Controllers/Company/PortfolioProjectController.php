<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StorePortfolioProjectRequest;
use App\Http\Requests\Company\UpdatePortfolioProjectRequest;
use App\Models\PortfolioProject;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['serviceCategory:id,name_ar,name_en', 'technician.user:id,name'])
            ->orderByDesc('is_featured')
            ->orderByDesc('id');

        $search = $request->string('search')->trim()->value();
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title_ar', 'like', '%'.$search.'%')
                    ->orWhere('title_en', 'like', '%'.$search.'%');
            });
        }

        return Inertia::render('Company/Portfolio/Index', [
            'projects' => $query->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PortfolioProject::class);

        $company = current_company();
        abort_if($company === null, 403);

        return Inertia::render('Company/Portfolio/Create', [
            'categories' => ServiceCategory::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name_ar', 'name_en']),
            'technicians' => Technician::query()
                ->where('company_id', $company->getKey())
                ->where('is_active', true)
                ->with('user:id,name')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(StorePortfolioProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', PortfolioProject::class);

        $company = current_company();
        abort_if($company === null, 403);

        $data = $request->validated();
        $data['company_id'] = $company->getKey();

        $project = PortfolioProject::query()->create($data);

        return redirect()
            ->route('company.portfolio-projects.edit', $project)
            ->with('message', 'Portfolio project created.');
    }

    public function edit(PortfolioProject $portfolioProject): Response
    {
        $this->authorize('update', $portfolioProject);

        $company = current_company();
        abort_if($company === null, 403);

        $portfolioProject->load([
            'portfolioImages',
            'serviceCategory:id,name_ar,name_en',
            'technician.user:id,name',
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
                ]),
                'completed_at' => $portfolioProject->completed_at?->format('Y-m-d'),
                'images' => $images,
            ],
            'categories' => ServiceCategory::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name_ar', 'name_en']),
            'technicians' => Technician::query()
                ->where('company_id', $company->getKey())
                ->where('is_active', true)
                ->with('user:id,name')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function update(UpdatePortfolioProjectRequest $request, PortfolioProject $portfolioProject): RedirectResponse
    {
        $this->authorize('update', $portfolioProject);

        $data = $request->validated();
        $portfolioProject->update($data);

        return redirect()
            ->route('company.portfolio-projects.edit', $portfolioProject)
            ->with('message', 'Portfolio project updated.');
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
            ->with('message', 'Portfolio project deleted.');
    }
}
