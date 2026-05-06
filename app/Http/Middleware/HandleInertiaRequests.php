<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Enums\TechnicianReviewStatus;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Default root template for Breeze / non-tenant Inertia pages.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function rootView(Request $request): string
    {
        if (
            $request->is('tenant') || $request->is('tenant/*')
            || $request->is('platform') || $request->is('platform/*')
            || $request->is('company') || $request->is('company/*')
        ) {
            return 'app-tenant';
        }

        return 'app';
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Most fields here are wrapped in closures so Inertia resolves them lazily
     * at render time. This matters because route-level middleware (notably
     * ResolveCurrentCompany) runs AFTER this global Inertia middleware, so
     * `$request->attributes['current_company']` is only available later.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $dashboardTranslations = trans('dashboard');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $this->resolveUserPayload($request),
            ],
            'userType' => fn () => $this->resolveUserType($request),
            'currentCompany' => fn () => $this->resolveCurrentCompanyPayload($request),
            'locale' => $locale,
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
            't' => is_array($dashboardTranslations) ? $dashboardTranslations : [],
            'enumLabels' => [
                'order_status' => Lang::get('enums.order_status'),
                'technician_availability' => Lang::get('enums.technician_availability'),
                'technician_review_status' => Lang::get('enums.technician_review_status'),
                'invoice_status' => Lang::get('enums.invoice_status'),
                'payment_status' => Lang::get('enums.payment_status'),
                'company_status' => Lang::get('enums.company_status'),
                'subscription_status' => Lang::get('enums.subscription_status'),
                'price_type' => Lang::get('enums.price_type'),
            ],
            'sidebarBadges' => fn () => $this->resolveSidebarBadges($request),
            'today' => now()->timezone(config('app.timezone'))->format('d-m-Y'),
            'timezone' => config('app.timezone'),
            'flash' => [
                'type' => $request->session()->get('type'),
                'message' => $request->session()->get('message'),
                'params' => $request->session()->get('params'),
            ],
            'settings' => [
                'sidebar_bg' => 'sidebar-dark-primary',
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function resolveSidebarBadges(Request $request): array
    {
        $user = $request->user();
        if ($user === null) {
            return [];
        }

        if ($user->isPlatformAdmin() && $user->hasPermission('technician_reviews.view')) {
            return [
                'pendingTechnicianReviews' => Technician::query()
                    ->where('review_status', TechnicianReviewStatus::Pending)
                    ->count(),
            ];
        }

        return [
            'pendingTechnicianReviews' => 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveUserPayload(Request $request): ?array
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        $permissionCompanyId = $request->attributes->get('current_company')?->getKey();

        return [
            'id' => $user->id,
            'ulid' => $user->ulid,
            'name' => $user->name,
            'name_ar' => $user->name_ar,
            'name_en' => $user->name_en,
            'email' => $user->email,
            'phone' => $user->phone,
            'type' => $user->type instanceof UserType ? $user->type->value : null,
            'tenant' => config('app.name'),
            'permissions' => $user->permissionKeys($permissionCompanyId),
        ];
    }

    private function resolveUserType(Request $request): ?string
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        return $user->type instanceof UserType ? $user->type->value : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveCurrentCompanyPayload(Request $request): ?array
    {
        if (! $request->attributes->has('current_company')) {
            return null;
        }

        $company = $request->attributes->get('current_company');
        if ($company === null) {
            return null;
        }

        $logoUrl = null;
        if (! empty($company->logo_path)) {
            try {
                $logoUrl = Storage::disk('public')->url($company->logo_path);
            } catch (\Throwable) {
                $logoUrl = null;
            }
        }

        return [
            'id' => $company->id,
            'ulid' => $company->ulid,
            'display_name' => $company->localized('name'),
            'logo_url' => $logoUrl,
            'status' => $company->status?->value,
            'subscription_status' => $company->subscription_status?->value,
        ];
    }
}
