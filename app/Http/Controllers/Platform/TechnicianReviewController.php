<?php

namespace App\Http\Controllers\Platform;

use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Technician;
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
            ->with(['user', 'company', 'serviceCategories', 'reviewedBy'])
            ->orderByDesc('id');

        if ($reviewStatusRaw !== 'all') {
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

            return [
                'id' => $t->id,
                'employee_code' => $t->employee_code,
                'review_status' => $t->review_status?->value,
                'reviewed_at' => $t->reviewed_at?->toISOString(),
                'rejection_reason' => $t->rejection_reason,
                'company' => [
                    'id' => $t->company?->id,
                    'name' => $t->company?->display_name,
                ],
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

        $technician->load(['user', 'company', 'serviceCategories', 'reviewedBy']);

        return Inertia::render('Platform/TechnicianReviews/Show', [
            'technician' => [
                'id' => $technician->id,
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

        $technician->loadMissing('user');

        if (! in_array($technician->review_status, [TechnicianReviewStatus::Pending, TechnicianReviewStatus::Rejected], true)) {
            abort(422);
        }

        DB::transaction(function () use ($request, $technician): void {
            $technician->forceFill([
                'review_status' => TechnicianReviewStatus::Approved,
                'reviewed_by_user_id' => $request->user()?->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'is_active' => true,
            ])->save();

            $technician->user?->forceFill([
                'status' => UserStatus::Active,
            ])->save();
        });

        return redirect()->back()->with('type', 'success')->with('message', 'technician_reviews.approved_successfully');
    }

    public function reject(Request $request, Technician $technician): RedirectResponse
    {
        $this->authorize('rejectReview', $technician);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $technician->loadMissing('user');

        DB::transaction(function () use ($request, $technician, $validated): void {
            $technician->forceFill([
                'review_status' => TechnicianReviewStatus::Rejected,
                'reviewed_by_user_id' => $request->user()?->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
                'is_active' => false,
            ])->save();

            $technician->user?->forceFill([
                'status' => UserStatus::Inactive,
            ])->save();
        });

        return redirect()->back()->with('type', 'success')->with('message', 'technician_reviews.rejected_successfully');
    }
}

