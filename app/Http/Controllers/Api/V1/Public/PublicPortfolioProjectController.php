<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\CompanyStatus;
use App\Enums\PortfolioProjectReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PortfolioProjectResource;
use App\Models\PortfolioProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicPortfolioProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $categoryId = isset($validated['service_category_id'])
            ? (int) $validated['service_category_id']
            : (isset($validated['category_id']) ? (int) $validated['category_id'] : null);

        $query = $this->publicProjectsQuery()
            ->with($this->defaultWith());

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('title_ar', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhereHas('company', function ($company) use ($like): void {
                        $company->where('name_ar', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    });
            });
        }

        if ($categoryId !== null) {
            $query->where('service_category_id', $categoryId);
        }

        $projects = $query
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(20);

        return PortfolioProjectResource::collection($projects)->response();
    }

    public function show(PortfolioProject $portfolioProject): JsonResponse
    {
        abort_unless($this->isPubliclyVisible($portfolioProject), 404);

        $portfolioProject->load($this->defaultWith());

        $similar = $this->similarWorks($portfolioProject);
        $portfolioProject->setRelation('similarWorks', $similar);

        return (new PortfolioProjectResource($portfolioProject))->response();
    }

    /**
     * @return list<string|\Closure>
     */
    private function defaultWith(): array
    {
        return [
            'portfolioImages:id,model_type,model_id,disk,file_path,file_name,mime_type,size,sort_order,collection_name',
            'company' => function ($query): void {
                $query->select([
                    'id',
                    'ulid',
                    'name',
                    'name_ar',
                    'name_en',
                    'slug',
                    'short_description_ar',
                    'short_description_en',
                    'description_ar',
                    'description_en',
                    'warranty_note_ar',
                    'warranty_note_en',
                    'status',
                    'subscription_status',
                    'is_featured',
                    'logo_path',
                    'rating_average',
                    'rating_count',
                    'default_inspection_fee',
                ])->with(['discounts' => function ($q): void {
                    $now = now();
                    $q->where('is_active', true)
                        ->where(function ($inner) use ($now): void {
                            $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                        })
                        ->where(function ($inner) use ($now): void {
                            $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                        })
                        ->orderByDesc('discount_value')
                        ->limit(1);
                }]);
            },
            'technician.user:id,name,name_ar,name_en,avatar_path',
            'technician.company:id,name,name_ar,name_en,logo_path,status,is_featured,rating_average,rating_count',
            'technicians.user:id,name,name_ar,name_en,avatar_path',
            'technicians.company:id,name,name_ar,name_en,logo_path,status,is_featured,rating_average,rating_count',
        ];
    }

    private function publicProjectsQuery()
    {
        return PortfolioProject::query()
            ->where('is_active', true)
            ->where('review_status', PortfolioProjectReviewStatus::Approved)
            ->whereHas('company', fn ($q) => $q->where('status', CompanyStatus::Active));
    }

    private function isPubliclyVisible(PortfolioProject $project): bool
    {
        if (! $project->is_active) {
            return false;
        }

        if ($project->review_status !== PortfolioProjectReviewStatus::Approved) {
            return false;
        }

        $company = $project->company()->first();

        return $company !== null && $company->status === CompanyStatus::Active;
    }

    /**
     * @return Collection<int, PortfolioProject>
     */
    private function similarWorks(PortfolioProject $project)
    {
        $with = [
            'portfolioImages:id,model_type,model_id,disk,file_path,file_name,mime_type,size,sort_order,collection_name',
            'company:id,name,name_ar,name_en,slug,logo_path,status,is_featured,rating_average,rating_count',
            'technician.user:id,name,name_ar,name_en,avatar_path',
        ];

        if ($project->service_category_id !== null) {
            $sameCategory = $this->publicProjectsQuery()
                ->whereKeyNot($project->getKey())
                ->where('service_category_id', $project->service_category_id)
                ->with($with)
                ->orderByDesc('is_featured')
                ->orderByDesc('completed_at')
                ->orderByDesc('id')
                ->limit(12)
                ->get();

            if ($sameCategory->isNotEmpty()) {
                return $sameCategory;
            }
        }

        return $this->publicProjectsQuery()
            ->whereKeyNot($project->getKey())
            ->where('company_id', $project->company_id)
            ->with($with)
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }
}
