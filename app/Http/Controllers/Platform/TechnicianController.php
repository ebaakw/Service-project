<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateTechnicianRequest;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Enums\TechnicianReviewStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Technician::class);

        $search = $request->string('search')->trim()->value();
        $reviewStatusRaw = $request->string('review_status')->trim()->value();
        $reviewStatusRaw = $reviewStatusRaw !== '' ? strtolower($reviewStatusRaw) : 'all';
        $allowedReviewStatuses = ['all', TechnicianReviewStatus::Pending->value, TechnicianReviewStatus::Approved->value, TechnicianReviewStatus::Rejected->value];
        if (! in_array($reviewStatusRaw, $allowedReviewStatuses, true)) {
            $reviewStatusRaw = 'all';
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Technician::query()
            ->with(['user', 'company', 'serviceCategories'])
            ->orderByDesc('id');

        if ($reviewStatusRaw !== 'all') {
            $query->where('review_status', $reviewStatusRaw);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('display_name_ar', 'like', $like)
                    ->orWhere('display_name_en', 'like', $like)
                    ->orWhere('employee_code', 'like', $like)
                    ->orWhereHas('user', function ($uq) use ($like): void {
                        $uq->where('name', 'like', $like)
                            ->orWhere('name_ar', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    });
            });
        }

        $technicians = $query->paginate($perPage)->withQueryString();

        $technicians->through(function (Technician $t) use ($request): array {
            $avatarUrl = null;
            if (! empty($t->user?->avatar_path)) {
                $avatarUrl = Storage::disk('public')->url($t->user->avatar_path);
            }

            return [
                'id' => $t->id,
                'is_active' => (bool) $t->is_active,
                'employee_code' => $t->employee_code,
                'availability_status' => $t->availability_status?->value,
                'availability_status_label' => $t->availability_status?->label(),
                'review_status' => $t->review_status?->value,
                'reviewed_at' => $t->reviewed_at?->toISOString(),
                'rejection_reason' => $t->rejection_reason,
                'created_at' => $t->created_at?->toISOString(),
                'company' => [
                    'id' => $t->company->id,
                    'name' => $t->company->display_name,
                ],
                'user' => [
                    'name' => $t->user->name,
                    'email' => $t->user->email,
                    'phone' => $t->user->phone,
                    'avatar_url' => $avatarUrl,
                ],
                'service_categories' => $t->serviceCategories->map(fn ($c) => [
                    'id' => $c->id,
                    'name_ar' => $c->name_ar,
                    'name_en' => $c->name_en,
                ])->values()->all(),
                'can_update' => $request->user()?->can('update', $t) ?? false,
            ];
        });

        return Inertia::render('Platform/Technicians/Index', [
            'technicians' => $technicians,
            'filters' => [
                'search' => $search,
                'review_status' => $reviewStatusRaw,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Technician $technician): Response
    {
        $this->authorize('view', $technician);

        $technician->load(['user', 'company', 'serviceCategories']);
        $avatarUrl = null;
        if (! empty($technician->user?->avatar_path)) {
            $avatarUrl = Storage::disk('public')->url($technician->user->avatar_path);
        }

        return Inertia::render('Platform/Technicians/Show', [
            'technician' => [
                'id' => $technician->id,
                'employee_code' => $technician->employee_code,
                'bio' => $technician->bio,
                'job_title' => $technician->job_title,
                'availability_status' => $technician->availability_status?->value,
                'availability_status_label' => $technician->availability_status?->label(),
                'is_active' => (bool) $technician->is_active,
                'company' => [
                    'id' => $technician->company->id,
                    'name' => $technician->company->display_name,
                    'slug' => $technician->company->slug,
                ],
                'user' => [
                    'name' => $technician->user->name,
                    'email' => $technician->user->email,
                    'phone' => $technician->user->phone,
                    'avatar_url' => $avatarUrl,
                ],
                'service_categories' => $technician->serviceCategories->map(fn ($c) => [
                    'id' => $c->id,
                    'name_ar' => $c->name_ar,
                    'name_en' => $c->name_en,
                ])->values()->all(),
                'can_update' => request()->user()?->can('update', $technician) ?? false,
            ],
        ]);
    }

    public function edit(Technician $technician): Response
    {
        $this->authorize('update', $technician);

        $technician->load(['user', 'serviceCategories', 'company']);

        return Inertia::render('Platform/Technicians/Edit', [
            'technician' => [
                'id' => $technician->id,
                'employee_code' => $technician->employee_code,
                'display_name_ar' => $technician->display_name_ar,
                'display_name_en' => $technician->display_name_en,
                'bio_ar' => $technician->bio_ar,
                'bio_en' => $technician->bio_en,
                'job_title_ar' => $technician->job_title_ar,
                'job_title_en' => $technician->job_title_en,
                'is_active' => (bool) $technician->is_active,
                'user' => [
                    'name' => $technician->user->name,
                    'name_ar' => $technician->user->name_ar,
                    'name_en' => $technician->user->name_en,
                    'email' => $technician->user->email,
                    'phone' => $technician->user->phone,
                ],
                'company' => [
                    'id' => $technician->company->id,
                    'name' => $technician->company->display_name,
                ],
                'service_category_ids' => $technician->serviceCategories->pluck('id')->all(),
            ],
            'serviceCategories' => $this->activeServiceCategories(),
        ]);
    }

    public function update(UpdateTechnicianRequest $request, Technician $technician)
    {
        $validated = $request->validated();
        $categoryIds = $validated['service_category_ids'] ?? null;
        unset($validated['service_category_ids']);
        $password = $validated['password'] ?? null;
        unset($validated['password']);

        DB::transaction(function () use ($validated, $categoryIds, $password, $technician): void {
            $technician->user->fill([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);

            if (! empty($password)) {
                $technician->user->password = $password;
            }

            $technician->user->save();

            $technician->fill([
                'employee_code' => $validated['employee_code'] ?? null,
                'display_name_ar' => $validated['display_name_ar'] ?? null,
                'display_name_en' => $validated['display_name_en'] ?? null,
                'bio_ar' => $validated['bio_ar'] ?? null,
                'bio_en' => $validated['bio_en'] ?? null,
                'job_title_ar' => $validated['job_title_ar'] ?? null,
                'job_title_en' => $validated['job_title_en'] ?? null,
                'is_active' => array_key_exists('is_active', $validated)
                    ? (bool) $validated['is_active']
                    : $technician->is_active,
            ]);
            $technician->save();

            if (is_array($categoryIds)) {
                $technician->serviceCategories()->sync($categoryIds);
            }
        });

        return redirect()->route('platform.technicians.show', $technician)
            ->with('success', true);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, name_ar: string, name_en: string|null}>
     */
    private function activeServiceCategories()
    {
        return ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name_ar', 'name_en']);
    }
}
