<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
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
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $user = $request->user();

        $userPayload = null;
        $currentCompanyPayload = null;

        if ($user !== null) {
            $permissionCompanyId = $request->attributes->get('current_company')?->getKey();

            $userPayload = array_merge($user->toArray(), [
                'tenant' => config('app.name'),
                'branches' => [],
                'currentBranch' => null,
                'roles' => [],
                'permissions' => $user->permissionKeys($permissionCompanyId),
            ]);
        }

        if ($request->attributes->has('current_company')) {
            $company = $request->attributes->get('current_company');
            $currentCompanyPayload = [
                'id' => $company->id,
                'name' => $company->name,
                'name_ar' => $company->name_ar,
                'name_en' => $company->name_en,
                'display_name' => $company->localized('name'),
                'slug' => $company->slug,
                'ulid' => $company->ulid,
            ];
        }

        $dashboardTranslations = trans('dashboard');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userPayload,
            ],
            'currentCompany' => $currentCompanyPayload,
            'locale' => $locale,
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
            't' => is_array($dashboardTranslations) ? $dashboardTranslations : [],
            'enumLabels' => [
                'order_status' => Lang::get('enums.order_status'),
                'technician_availability' => Lang::get('enums.technician_availability'),
                'invoice_status' => Lang::get('enums.invoice_status'),
                'payment_status' => Lang::get('enums.payment_status'),
                'company_status' => Lang::get('enums.company_status'),
                'subscription_status' => Lang::get('enums.subscription_status'),
                'price_type' => Lang::get('enums.price_type'),
            ],
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
            'tenant_support_unread_count' => 0,
            'tenant_notifications_unread_count' => 0,
            'tenant_latest_unread_notification' => null,
        ];
    }
}
