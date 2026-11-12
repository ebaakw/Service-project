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
use App\Support\TechnicianProfileSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                    ->orWhere('first_name_ar', 'like', $like)
                    ->orWhere('second_name_ar', 'like', $like)
                    ->orWhere('family_name_ar', 'like', $like)
                    ->orWhere('civil_id_number', 'like', $like)
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
        $civilIdDocument = $request->file('civil_id_document');
        $categoryIds = $validated['service_category_ids'] ?? [];
        unset($validated['service_category_ids'], $validated['avatar'], $validated['civil_id_document']);

        DB::transaction(function () use ($validated, $categoryIds, $companyId, $avatar, $civilIdDocument): void {
            $nameAr = Technician::composeFullName(
                $validated['first_name_ar'],
                $validated['second_name_ar'],
                $validated['family_name_ar'],
            );
            $nameEn = Technician::composeFullName(
                $validated['first_name_en'] ?? null,
                $validated['second_name_en'] ?? null,
                $validated['family_name_en'] ?? null,
            );

            $user = User::query()->create([
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'email' => $validated['email'] ?? null,
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
                'first_name_ar' => $validated['first_name_ar'],
                'second_name_ar' => $validated['second_name_ar'],
                'family_name_ar' => $validated['family_name_ar'],
                'first_name_en' => $validated['first_name_en'] ?? null,
                'second_name_en' => $validated['second_name_en'] ?? null,
                'family_name_en' => $validated['family_name_en'] ?? null,
                'civil_id_number' => $validated['civil_id_number'],
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
                'pending_changes' => null,
            ]);

            if ($civilIdDocument !== null) {
                app(MediaUploadService::class)->storeTechnicianCivilId($technician, $civilIdDocument);
            }

            $technician->serviceCategories()->sync($categoryIds);
        });

        return redirect()->route('company.technicians.index')
            ->with('type', 'success')
            ->with('message', __('tenant.technicians.createdSuccessfully'));
    }

    public function edit(Technician $technician): Response
    {
        $this->authorize('update', $technician);

        $technician->load(['user', 'serviceCategories', 'company', 'media']);
        $avatarUrl = null;
        if (! empty($technician->user?->avatar_path)) {
            $avatarUrl = Storage::disk('public')->url($technician->user->avatar_path);
        }

        $civilId = $technician->civilIdMedia();

        return Inertia::render('Company/Technicians/Edit', [
            'technician' => [
                'id' => $technician->id,
                'first_name_ar' => $technician->first_name_ar,
                'second_name_ar' => $technician->second_name_ar,
                'family_name_ar' => $technician->family_name_ar,
                'first_name_en' => $technician->first_name_en,
                'second_name_en' => $technician->second_name_en,
                'family_name_en' => $technician->family_name_en,
                'civil_id_number' => $technician->civil_id_number,
                'civil_id_document_url' => $civilId ? MediaUploadService::publicUrl($civilId) : null,
                'civil_id_document_name' => $civilId?->file_name,
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
                'is_accepting_orders' => (bool) $technician->is_accepting_orders,
                'estimated_job_duration_minutes' => $technician->estimated_job_duration_minutes !== null
                    ? (int) $technician->estimated_job_duration_minutes
                    : null,
                'review_status' => $technician->review_status?->value,
                'rejection_reason' => $technician->rejection_reason,
                'pending_changes' => $technician->pending_changes,
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
        $civilIdDocument = $request->file('civil_id_document');
        $categoryIds = $validated['service_category_ids'] ?? null;

        $hasAcceptingOrders = array_key_exists('is_accepting_orders', $validated);
        $hasEstimatedDuration = array_key_exists('estimated_job_duration_minutes', $validated);
        $isAcceptingOrders = $hasAcceptingOrders ? (bool) $validated['is_accepting_orders'] : null;
        $estimatedJobDurationMinutes = $hasEstimatedDuration ? $validated['estimated_job_duration_minutes'] : null;

        unset(
            $validated['service_category_ids'],
            $validated['avatar'],
            $validated['civil_id_document'],
            $validated['is_accepting_orders'],
            $validated['estimated_job_duration_minutes'],
        );

        $status = $technician->review_status;

        DB::transaction(function () use (
            $validated,
            $categoryIds,
            $technician,
            $avatar,
            $civilIdDocument,
            $status,
            $hasAcceptingOrders,
            $hasEstimatedDuration,
            $isAcceptingOrders,
            $estimatedJobDurationMinutes,
        ): void {
            if ($status?->appliesLiveOnCompanyEdit()) {
                TechnicianProfileSync::applyLive(
                    $technician,
                    $validated,
                    $avatar,
                    $civilIdDocument,
                    is_array($categoryIds) ? $categoryIds : [],
                    resubmitForCreateReview: true,
                );
            } else {
                TechnicianProfileSync::stagePending(
                    $technician,
                    $validated,
                    $avatar,
                    $civilIdDocument,
                    is_array($categoryIds) ? $categoryIds : null,
                );
            }

            $queuePayload = [];
            if ($hasAcceptingOrders) {
                $queuePayload['is_accepting_orders'] = $isAcceptingOrders;
            }
            if ($hasEstimatedDuration) {
                $queuePayload['estimated_job_duration_minutes'] = $estimatedJobDurationMinutes;
            }

            if ($queuePayload !== []) {
                $technician->forceFill($queuePayload)->save();
            }
        });

        $message = $status?->appliesLiveOnCompanyEdit()
            ? __('tenant.technicians.updatedSuccessfully')
            : __('tenant.technicians.updateSubmittedForReview');

        return redirect()->route('company.technicians.index')
            ->with('type', 'success')
            ->with('message', $message);
    }

    public function destroy(Technician $technician): RedirectResponse
    {
        $this->authorize('delete', $technician);

        $status = $technician->review_status;

        if ($status?->isLiveApprovedLike()) {
            TechnicianProfileSync::requestDeletion($technician);

            return redirect()->route('company.technicians.index')
                ->with('type', 'success')
                ->with('message', __('tenant.technicians.deletionSubmittedForReview'));
        }

        DB::transaction(function () use ($technician): void {
            $technician->delete();
            $technician->user->delete();
        });

        return redirect()->route('company.technicians.index')
            ->with('type', 'success')
            ->with('message', __('tenant.technicians.deletedSuccessfully'));
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
     * @return Collection<int, array{id: int, name_ar: string, name_en: string|null}>
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
