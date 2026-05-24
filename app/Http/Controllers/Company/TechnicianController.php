<?php

namespace App\Http\Controllers\Company;

use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ResetTechnicianPasswordRequest;
use App\Http\Requests\Company\StoreTechnicianRequest;
use App\Http\Requests\Company\UpdateTechnicianRequest;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
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

        $companyId = current_company()?->getKey();
        if ($companyId === null) {
            abort(403);
        }

        $search = $request->string('search')->trim()->value();
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Technician::query()
            ->with(['user', 'serviceCategories'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

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

        return Inertia::render('Company/Technicians/Index', [
            'technicians' => $technicians->through(function (Technician $t): array {
                return [
                    'id' => $t->id,
                    'is_active' => (bool) $t->is_active,
                    'review_status' => $t->review_status?->value,
                    'rejection_reason' => $t->rejection_reason,
                    'created_at' => $t->created_at?->toISOString(),
                    'user' => [
                        'name' => $t->user?->name,
                        'email' => $t->user?->email,
                        'phone' => $t->user?->phone,
                    ],
                    'service_categories' => $t->serviceCategories->map(fn ($c) => [
                        'id' => $c->id,
                        'name_ar' => $c->name_ar,
                        'name_en' => $c->name_en,
                    ])->values()->all(),
                ];
            }),
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Technician::class);

        return Inertia::render('Company/Technicians/Create', [
            'serviceCategories' => $this->activeServiceCategories(),
        ]);
    }

    public function store(StoreTechnicianRequest $request): RedirectResponse
    {
        $companyId = current_company()?->getKey();
        if ($companyId === null) {
            abort(403);
        }

        $validated = $request->validated();
        $avatar = $request->file('avatar');
        $categoryIds = $validated['service_category_ids'] ?? [];
        unset($validated['service_category_ids']);
        unset($validated['avatar']);

        DB::transaction(function () use ($validated, $categoryIds, $companyId, $avatar): void {
            $user = User::query()->create([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'type' => UserType::Technician,
                'status' => UserStatus::Inactive,
            ]);

            if ($avatar !== null) {
                app(MediaUploadService::class)->storeUserAvatar($user, $avatar);
            }

            $technician = Technician::query()->create([
                'company_id' => $companyId,
                'user_id' => $user->getKey(),
                'employee_code' => $validated['employee_code'] ?? null,
                'display_name_ar' => $validated['display_name_ar'] ?? null,
                'display_name_en' => $validated['display_name_en'] ?? null,
                'bio_ar' => $validated['bio_ar'] ?? null,
                'bio_en' => $validated['bio_en'] ?? null,
                'job_title_ar' => $validated['job_title_ar'] ?? null,
                'job_title_en' => $validated['job_title_en'] ?? null,
                'is_active' => false,
                'review_status' => TechnicianReviewStatus::Pending,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]);

            $technician->serviceCategories()->sync($categoryIds);
        });

        return redirect()->route('company.technicians.index')
            ->with('type', 'success')
            ->with('message', __('tenant.technicians.createdSuccessfully'));
    }

    public function edit(Technician $technician): Response
    {
        $this->authorize('update', $technician);

        $technician->load(['user', 'serviceCategories', 'company']);
        $avatarUrl = null;
        if (! empty($technician->user?->avatar_path)) {
            $avatarUrl = Storage::disk('public')->url($technician->user->avatar_path);
        }

        return Inertia::render('Company/Technicians/Edit', [
            'technician' => [
                'id' => $technician->id,
                'employee_code' => $technician->employee_code,
                'display_name_ar' => $technician->display_name_ar,
                'display_name_en' => $technician->display_name_en,
                'bio_ar' => $technician->bio_ar,
                'bio_en' => $technician->bio_en,
                'job_title_ar' => $technician->job_title_ar,
                'job_title_en' => $technician->job_title_en,
                'is_active' => $technician->is_active,
                'availability_status' => $technician->availability_status?->value,
                'availability_status_label' => $technician->availability_status?->label(),
                'review_status' => $technician->review_status?->value,
                'rejection_reason' => $technician->rejection_reason,
                'created_at' => $technician->created_at?->toISOString(),
                'company' => [
                    'id' => $technician->company?->id,
                    'name' => $technician->company?->display_name,
                    'slug' => $technician->company?->slug,
                ],
                'user' => [
                    'name' => $technician->user->name,
                    'name_ar' => $technician->user->name_ar,
                    'name_en' => $technician->user->name_en,
                    'email' => $technician->user->email,
                    'phone' => $technician->user->phone,
                    'avatar_url' => $avatarUrl,
                ],
                'service_category_ids' => $technician->serviceCategories->pluck('id')->all(),
            ],
            'serviceCategories' => $this->activeServiceCategories(),
        ]);
    }

    public function update(UpdateTechnicianRequest $request, Technician $technician): RedirectResponse
    {
        $validated = $request->validated();
        $avatar = $request->file('avatar');
        $categoryIds = $validated['service_category_ids'] ?? null;
        unset($validated['service_category_ids']);
        $password = $validated['password'] ?? null;
        unset($validated['password']);
        unset($validated['avatar']);

        DB::transaction(function () use ($validated, $categoryIds, $password, $technician, $avatar): void {
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

            if ($avatar !== null) {
                app(MediaUploadService::class)->storeUserAvatar($technician->user, $avatar);
            }

            $technician->fill([
                'employee_code' => $validated['employee_code'] ?? null,
                'display_name_ar' => $validated['display_name_ar'] ?? null,
                'display_name_en' => $validated['display_name_en'] ?? null,
                'bio_ar' => $validated['bio_ar'] ?? null,
                'bio_en' => $validated['bio_en'] ?? null,
                'job_title_ar' => $validated['job_title_ar'] ?? null,
                'job_title_en' => $validated['job_title_en'] ?? null,
                // Any company edit re-submits the technician for platform review.
                'is_active' => false,
                'review_status' => TechnicianReviewStatus::Pending,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]);
            $technician->save();

            // Company edits force technician user inactive until platform re-approval.
            $technician->user->forceFill(['status' => UserStatus::Inactive])->save();

            if (is_array($categoryIds)) {
                $technician->serviceCategories()->sync($categoryIds);
            }
        });

        return redirect()->route('company.technicians.index')
            ->with('type', 'success')
            ->with('message', __('tenant.technicians.updatedSuccessfully'));
    }

    public function destroy(Technician $technician): RedirectResponse
    {
        $this->authorize('delete', $technician);

        DB::transaction(function () use ($technician): void {
            $technician->delete();
            $technician->user->delete();
        });

        return redirect()->route('company.technicians.index')
            ->with('type', 'success')
            ->with('message', 'saved');
    }

    public function resetPassword(ResetTechnicianPasswordRequest $request, Technician $technician): RedirectResponse
    {
        $technician->user->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        return redirect()->route('company.technicians.edit', $technician)
            ->with('type', 'success')
            ->with('message', 'saved');
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
