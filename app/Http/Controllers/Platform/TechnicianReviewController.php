<?php

namespace App\Http\Controllers\Platform;

use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Technician;
use App\Services\Media\MediaUploadService;
use App\Support\TechnicianProfileSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewReviews', Technician::class);

        $search = $request->string('search')->trim()->value();
        $reviewStatusRaw = $request->string('review_status')->trim()->value();
        $reviewStatusRaw = $reviewStatusRaw !== '' ? strtolower($reviewStatusRaw) : 'pending';

        $companyId = $request->integer('company_id') ?: null;
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 15;

        $query = Technician::query()
            ->with(['user', 'company', 'serviceCategories', 'reviewedBy', 'media'])
            ->orderByDesc('id');

        if ($reviewStatusRaw === 'pending') {
            $query->whereIn('review_status', TechnicianReviewStatus::actionableValues());
        } elseif ($reviewStatusRaw !== 'all') {
            $query->where('review_status', $reviewStatusRaw);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('employee_code', 'like', $like)
                    ->orWhere('display_name_ar', 'like', $like)
                    ->orWhere('display_name_en', 'like', $like)
                    ->orWhere('civil_id_number', 'like', $like)
                    ->orWhere('first_name_ar', 'like', $like)
                    ->orWhere('second_name_ar', 'like', $like)
                    ->orWhere('family_name_ar', 'like', $like)
                    ->orWhereHas('company', function ($cq) use ($like): void {
                        $cq->where('name', 'like', $like)
                            ->orWhere('name_ar', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    })
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
            $canApprove = $request->user()?->can('approveReview', $t) ?? false;
            $canReject = $request->user()?->can('rejectReview', $t) ?? false;
            $civilId = $t->civilIdMedia();

            return [
                'id' => $t->id,
                'employee_code' => $t->employee_code,
                'display_name' => $t->localizedDisplayName(),
                'job_title' => $t->job_title,
                'civil_id_number' => $t->civil_id_number,
                'review_status' => $t->review_status?->value,
                'reviewed_at' => $t->reviewed_at?->toISOString(),
                'rejection_reason' => $t->rejection_reason,
                'created_at' => $t->created_at?->toISOString(),
                'company' => [
                    'id' => $t->company?->id,
                    'name' => $t->company?->display_name,
                    'slug' => $t->company?->slug,
                ],
                'user' => [
                    'name' => $t->user?->name,
                    'email' => $t->user?->email,
                    'phone' => $t->user?->phone,
                    'avatar_url' => $this->userAvatarUrl($t->user?->avatar_path),
                ],
                'service_categories' => $t->serviceCategories->map(fn ($c) => [
                    'id' => $c->id,
                    'name_ar' => $c->name_ar,
                    'name_en' => $c->name_en,
                ])->values()->all(),
                'civil_id_document_url' => $civilId ? MediaUploadService::publicUrl($civilId) : null,
                'can' => [
                    'approve' => $canApprove,
                    'reject' => $canReject,
                ],
            ];
        });

        $companies = Company::query()
            ->orderBy('id')
            ->get(['id', 'name', 'name_ar', 'name_en', 'slug'])
            ->map(fn (Company $c) => [
                'id' => $c->id,
                'name' => $c->display_name,
            ])
            ->values()
            ->all();

        return Inertia::render('Platform/TechnicianReviews/Index', [
            'technicians' => $technicians,
            'filters' => [
                'search' => $search,
                'review_status' => $reviewStatusRaw,
                'company_id' => $companyId,
                'per_page' => $perPage,
            ],
            'companies' => $companies,
        ]);
    }

    public function show(Request $request, Technician $technician): Response
    {
        $this->authorize('viewReviews', Technician::class);

        $technician->load(['user', 'company', 'serviceCategories', 'reviewedBy', 'media']);
        $civilId = $technician->civilIdMedia();

        return Inertia::render('Platform/TechnicianReviews/Show', [
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
                'review_status' => $technician->review_status?->value,
                'reviewed_at' => $technician->reviewed_at?->toISOString(),
                'rejection_reason' => $technician->rejection_reason,
                'pending_changes' => $technician->pending_changes,
                'reviewed_by' => $technician->reviewedBy?->only(['id', 'name', 'email']),
                'company' => [
                    'id' => $technician->company?->id,
                    'name' => $technician->company?->display_name,
                    'slug' => $technician->company?->slug,
                ],
                'user' => [
                    'id' => $technician->user?->id,
                    'name' => $technician->user?->name,
                    'email' => $technician->user?->email,
                    'phone' => $technician->user?->phone,
                    'status' => $technician->user?->status?->value,
                    'avatar_url' => $this->userAvatarUrl($technician->user?->avatar_path),
                ],
                'service_categories' => $technician->serviceCategories->map(fn ($c) => [
                    'id' => $c->id,
                    'name_ar' => $c->name_ar,
                    'name_en' => $c->name_en,
                ])->values()->all(),
                'can' => [
                    'approve' => $request->user()?->can('approveReview', $technician) ?? false,
                    'reject' => $request->user()?->can('rejectReview', $technician) ?? false,
                ],
            ],
        ]);
    }

    public function approve(Request $request, Technician $technician): RedirectResponse
    {
        $this->authorize('approveReview', $technician);

        $technician->loadMissing(['user', 'media']);

        $status = $technician->review_status;
        if (! in_array($status, TechnicianReviewStatus::actionable(), true)
            && $status !== TechnicianReviewStatus::Rejected) {
            abort(422);
        }

        DB::transaction(function () use ($request, $technician, $status): void {
            if ($status === TechnicianReviewStatus::PendingDeletion) {
                $technician->delete();
                $technician->user?->delete();

                return;
            }

            if ($status === TechnicianReviewStatus::PendingUpdate) {
                TechnicianProfileSync::applyPendingChanges($technician);
                $technician->forceFill([
                    'reviewed_by_user_id' => $request->user()?->getKey(),
                    'reviewed_at' => now(),
                    'rejection_reason' => null,
                ])->save();

                return;
            }

            $technician->forceFill([
                'review_status' => TechnicianReviewStatus::Approved,
                'reviewed_by_user_id' => $request->user()?->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'is_active' => true,
                'pending_changes' => null,
            ])->save();

            $technician->user?->forceFill([
                'status' => UserStatus::Active,
            ])->save();
        });

        $message = match ($status) {
            TechnicianReviewStatus::PendingDeletion => 'technician_reviews.deletion_approved_successfully',
            TechnicianReviewStatus::PendingUpdate => 'technician_reviews.update_approved_successfully',
            default => 'technician_reviews.approved_successfully',
        };

        return redirect()->back()->with('type', 'success')->with('message', $message);
    }

    public function reject(Request $request, Technician $technician): RedirectResponse
    {
        $this->authorize('rejectReview', $technician);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $technician->loadMissing('user');
        $status = $technician->review_status;

        DB::transaction(function () use ($request, $technician, $validated, $status): void {
            if (in_array($status, [TechnicianReviewStatus::PendingUpdate, TechnicianReviewStatus::PendingDeletion], true)) {
                TechnicianProfileSync::clearPendingChanges($technician, $validated['rejection_reason']);
                $technician->forceFill([
                    'reviewed_by_user_id' => $request->user()?->getKey(),
                    'reviewed_at' => now(),
                ])->save();

                return;
            }

            $technician->forceFill([
                'review_status' => TechnicianReviewStatus::Rejected,
                'reviewed_by_user_id' => $request->user()?->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
                'is_active' => false,
                'pending_changes' => null,
            ])->save();

            $technician->user?->forceFill([
                'status' => UserStatus::Inactive,
            ])->save();
        });

        $message = in_array($status, [TechnicianReviewStatus::PendingUpdate, TechnicianReviewStatus::PendingDeletion], true)
            ? 'technician_reviews.request_rejected_successfully'
            : 'technician_reviews.rejected_successfully';

        return redirect()->back()->with('type', 'success')->with('message', $message);
    }

    private function userAvatarUrl(?string $avatarPath): ?string
    {
        $avatarPath = trim((string) $avatarPath);

        if ($avatarPath === '') {
            return null;
        }

        if (str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')) {
            return $avatarPath;
        }

        $avatarPath = ltrim($avatarPath, '/');
        if (str_starts_with($avatarPath, 'storage/')) {
            $avatarPath = substr($avatarPath, strlen('storage/'));
        }

        return asset('storage/'.$avatarPath);
    }
}
