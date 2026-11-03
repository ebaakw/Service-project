<?php

namespace App\Http\Controllers\Platform;

use App\Enums\SettingValueType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdatePlatformSettingsRequest;
use App\Models\Setting;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSettingsController extends Controller
{
    /**
     * @var array<string, array{0: SettingValueType, 1: string|null, 2: string|null}>
     */
    private const KEY_META = [
        'inspection_fee' => [SettingValueType::Number, 'orders', 'Default inspection fee (KWD)'],
        'currency' => [SettingValueType::String, 'billing', 'Display currency code'],
        'order_offer_expiry_minutes' => [SettingValueType::Number, 'orders', 'Order offer expiry (minutes)'],
        'technician_accept_sla_minutes' => [SettingValueType::Number, 'orders', 'Minutes allowed for technician to accept after assignment'],
        'technician_arrival_sla_minutes' => [SettingValueType::Number, 'orders', 'Minutes allowed for technician to arrive after acceptance'],
        'platform_commission_percentage' => [SettingValueType::Number, 'billing', 'Platform commission percent'],
        'support_phone' => [SettingValueType::String, 'support', 'Public support phone'],
        'support_email' => [SettingValueType::String, 'support', 'Public support email'],
    ];

    public function edit(SettingsService $settings): Response
    {
        $this->authorize('viewAny', Setting::class);

        $values = [];
        foreach (array_keys(self::KEY_META) as $key) {
            $values[$key] = $settings->getGlobal($key);
        }

        $rows = Setting::query()->orderBy('group')->orderBy('key')->get(['key', 'description', 'group', 'is_public']);

        return Inertia::render('Platform/Settings/Edit', [
            'values' => $values,
            'keys' => array_keys(self::KEY_META),
            'meta' => $rows->keyBy('key'),
        ]);
    }

    public function update(UpdatePlatformSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $this->authorize('create', Setting::class);

        $input = $request->validated('settings') ?? [];

        foreach (self::KEY_META as $key => [$type, $group, $description]) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            $isPublic = in_array($key, ['support_phone', 'support_email'], true);

            $settings->setGlobal($key, $value, $type, $group, $description, $isPublic);
        }

        return redirect()
            ->route('platform.settings.edit')
            ->with('message', 'Global settings updated.');
    }
}
