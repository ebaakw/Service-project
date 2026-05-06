<?php

namespace App\Http\Controllers;

use App\Actions\Platform\CreateCompanyAction;
use App\Actions\Platform\UpdateCompanyAction;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Http\Requests\Platform\StorePlatformCompanyRequest;
use App\Http\Requests\Platform\UpdatePlatformCompanyRequest;
use App\Models\Company;
use App\Services\CompanyPrimaryUserService;
use App\Services\Media\MediaUploadService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformCompaniesController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Company::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $subscriptionStatus = $request->string('subscription_status')->trim()->value();

        $validCompanyStatuses = array_map(fn (CompanyStatus $e) => $e->value, CompanyStatus::cases());
        $validSubscriptionStatuses = array_map(fn (SubscriptionStatus $e) => $e->value, SubscriptionStatus::cases());

        $query = Company::query()
            ->withCount(['members', 'technicians', 'orders'])
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        if ($status !== '' && in_array($status, $validCompanyStatuses, true)) {
            $query->where('status', $status);
        }

        if ($subscriptionStatus !== '' && in_array($subscriptionStatus, $validSubscriptionStatuses, true)) {
            $query->where('subscription_status', $subscriptionStatus);
        }

        $filename = 'platform-companies-'.now()->format('Y-m-d_H-i-s').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'ID',
                'Name',
                'Name (AR)',
                'Name (EN)',
                'Slug',
                'Phone',
                'Email',
                'Status',
                'Subscription Status',
                'Subscription Starts At',
                'Subscription Ends At',
                'Members Count',
                'Technicians Count',
                'Orders Count',
                'Created At',
            ]);

            $locale = app()->getLocale();

            $query->chunkById(500, function ($companies) use ($out, $locale): void {
                foreach ($companies as $company) {
                    /** @var Company $company */
                    $displayName = $locale === 'ar'
                        ? ($company->name_ar ?: $company->name_en ?: $company->name)
                        : ($company->name_en ?: $company->name_ar ?: $company->name);

                    fputcsv($out, [
                        $company->id,
                        $displayName,
                        $company->name_ar,
                        $company->name_en,
                        $company->slug,
                        $company->phone,
                        $company->email,
                        $company->status?->label() ?? $company->status?->value,
                        $company->subscription_status?->label() ?? $company->subscription_status?->value,
                        optional($company->subscription_starts_at)->toDateString(),
                        optional($company->subscription_ends_at)->toDateString(),
                        $company->members_count,
                        $company->technicians_count,
                        $company->orders_count,
                        optional($company->created_at)->toDateTimeString(),
                    ]);
                }
            }, 'id');

            fclose($out);
        }, $filename, $headers);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Company::class);
        $actor = $request->user();

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $subscriptionStatus = $request->string('subscription_status')->trim()->value();
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $query = Company::query()
            ->withCount(['members', 'technicians', 'orders'])
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        if ($status !== '' && in_array($status, array_map(fn (CompanyStatus $e) => $e->value, CompanyStatus::cases()), true)) {
            $query->where('status', $status);
        }

        if ($subscriptionStatus !== '' && in_array($subscriptionStatus, array_map(fn (SubscriptionStatus $e) => $e->value, SubscriptionStatus::cases()), true)) {
            $query->where('subscription_status', $subscriptionStatus);
        }

        $companies = $query->paginate($perPage)->withQueryString();

        $locale = app()->getLocale();
        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $companies->through(function (Company $company) use ($locale, $publicDisk, $actor): array {
            $displayName = $locale === 'ar'
                ? ($company->name_ar ?: $company->name_en ?: $company->name)
                : ($company->name_en ?: $company->name_ar ?: $company->name);

            return [
                'id' => $company->id,
                'ulid' => $company->ulid,
                'display_name' => $displayName,
                'name_ar' => $company->name_ar,
                'name_en' => $company->name_en,
                'slug' => $company->slug,
                'logo_url' => $company->logo_path ? $publicDisk->url($company->logo_path) : null,
                'phone' => $company->phone,
                'email' => $company->email,
                'status' => $company->status?->value,
                'status_label' => $company->status?->label(),
                'subscription_status' => $company->subscription_status?->value,
                'subscription_status_label' => $company->subscription_status?->label(),
                'subscription_starts_at' => optional($company->subscription_starts_at)->toDateString(),
                'subscription_ends_at' => optional($company->subscription_ends_at)->toDateString(),
                'is_featured' => (bool) $company->is_featured,
                'default_inspection_fee' => $company->default_inspection_fee,
                'allow_final_invoice_without_items' => (bool) $company->allow_final_invoice_without_items,
                'rating_average' => $company->rating_average,
                'rating_count' => $company->rating_count,
                'members_count' => $company->members_count,
                'technicians_count' => $company->technicians_count,
                'orders_count' => $company->orders_count,
                'created_at' => optional($company->created_at)->toDateTimeString(),
                'can_update' => $actor !== null && $actor->can('update', $company),
                'can_delete' => $actor !== null && $actor->can('delete', $company),
            ];
        });

        return Inertia::render('Platform/Companies/Index', [
            'companies' => $companies,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'subscription_status' => $subscriptionStatus,
                'per_page' => $perPage,
            ],
            'enums' => [
                'company_status' => array_map(fn (CompanyStatus $e) => ['value' => $e->value, 'label' => $e->label()], CompanyStatus::cases()),
                'subscription_status' => array_map(fn (SubscriptionStatus $e) => ['value' => $e->value, 'label' => $e->label()], SubscriptionStatus::cases()),
            ],
            'can' => [
                'create' => $actor !== null && $actor->can('create', Company::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Company::class);

        return Inertia::render('Platform/Companies/Create', [
            'enums' => [
                'company_status' => array_map(fn (CompanyStatus $e) => ['value' => $e->value, 'label' => $e->label()], CompanyStatus::cases()),
                'subscription_status' => array_map(fn (SubscriptionStatus $e) => ['value' => $e->value, 'label' => $e->label()], SubscriptionStatus::cases()),
            ],
        ]);
    }

    public function store(StorePlatformCompanyRequest $request, CreateCompanyAction $action): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $action->execute($request);

        return redirect()
            ->route('platform.companies.index')
            ->with('flash', ['type' => 'success', 'message' => __('tenant.companies.createdSuccessfully')]);
    }

    public function edit(Company $company): Response
    {
        $this->authorize('update', $company);

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        $primaryUser = app(CompanyPrimaryUserService::class)->getPrimaryUser($company);

        return Inertia::render('Platform/Companies/Edit', [
            'company' => [
                'id' => $company->id,
                'ulid' => $company->ulid,
                'name_ar' => $company->name_ar,
                'name_en' => $company->name_en,
                'slug' => $company->slug,
                'logo_path' => $company->logo_path,
                'logo_url' => $company->logo_path ? $publicDisk->url($company->logo_path) : null,
                'short_description_ar' => $company->short_description_ar,
                'short_description_en' => $company->short_description_en,
                'description_ar' => $company->description_ar,
                'description_en' => $company->description_en,
                'phone' => $company->phone,
                'email' => $company->email,
                'status' => $company->status?->value,
                'subscription_status' => $company->subscription_status?->value,
                'subscription_starts_at' => optional($company->subscription_starts_at)->toDateString(),
                'subscription_ends_at' => optional($company->subscription_ends_at)->toDateString(),
                'is_featured' => (bool) $company->is_featured,
                'default_inspection_fee' => $company->default_inspection_fee,
                'allow_final_invoice_without_items' => (bool) $company->allow_final_invoice_without_items,
            ],
            'primaryUser' => $primaryUser ? [
                'id' => $primaryUser->id,
                'name' => $primaryUser->name,
                'name_ar' => $primaryUser->name_ar,
                'name_en' => $primaryUser->name_en,
                'email' => $primaryUser->email,
                'phone' => $primaryUser->phone,
                'status' => $primaryUser->status?->value,
            ] : null,
            'enums' => [
                'company_status' => array_map(fn (CompanyStatus $e) => ['value' => $e->value, 'label' => $e->label()], CompanyStatus::cases()),
                'subscription_status' => array_map(fn (SubscriptionStatus $e) => ['value' => $e->value, 'label' => $e->label()], SubscriptionStatus::cases()),
            ],
            'userStatuses' => array_map(fn (UserStatus $e) => ['value' => $e->value, 'label' => $e->value], UserStatus::cases()),
        ]);
    }

    public function update(UpdatePlatformCompanyRequest $request, Company $company, UpdateCompanyAction $action): RedirectResponse
    {
        $this->authorize('update', $company);

        $action->execute($request, $company);

        return redirect()
            ->route('platform.companies.edit', $company)
            ->with('flash', ['type' => 'success', 'message' => __('tenant.companies.updatedSuccessfully')]);
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return redirect()
            ->route('platform.companies.index')
            ->with('flash', ['type' => 'success', 'message' => __('tenant.companies.deletedSuccessfully')]);
    }

    public function uploadLogo(Request $request, Company $company, MediaUploadService $media): RedirectResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'logo' => ['required', 'image', 'max:'.(int) config('media.max_image_size_kb', 2048)],
        ]);

        $media->storeCompanyLogo($company, $validated['logo']);

        return redirect()
            ->route('platform.companies.edit', $company)
            ->with('flash', ['type' => 'success', 'message' => __('tenant.companies.updatedSuccessfully')]);
    }
}
