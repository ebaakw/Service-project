<?php

namespace App\Http\Controllers\Company;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ResetTechnicianPasswordRequest;
use App\Http\Requests\Company\StoreTechnicianRequest;
use App\Http\Requests\Company\UpdateTechnicianRequest;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Technician::class);

        $companyId = current_company()?->getKey();

        $technicians = Technician::query()
            ->with(['user', 'serviceCategories'])
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Company/Technicians/Index', [
            'technicians' => $technicians,
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
        $categoryIds = $validated['service_category_ids'] ?? [];
        unset($validated['service_category_ids']);
        $isActive = $request->boolean('is_active', true);

        DB::transaction(function () use ($validated, $categoryIds, $companyId, $isActive): void {
            $user = User::query()->create([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'type' => UserType::Technician,
                'status' => UserStatus::Active,
            ]);

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
                'is_active' => $isActive,
            ]);

            $technician->serviceCategories()->sync($categoryIds);
        });

        return redirect()->route('company.technicians.index')
            ->with('success', true);
    }

    public function edit(Technician $technician): Response
    {
        $this->authorize('update', $technician);

        $technician->load(['user', 'serviceCategories']);

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
                'user' => [
                    'name' => $technician->user->name,
                    'name_ar' => $technician->user->name_ar,
                    'name_en' => $technician->user->name_en,
                    'email' => $technician->user->email,
                    'phone' => $technician->user->phone,
                ],
                'service_category_ids' => $technician->serviceCategories->pluck('id')->all(),
            ],
            'serviceCategories' => $this->activeServiceCategories(),
        ]);
    }

    public function update(UpdateTechnicianRequest $request, Technician $technician): RedirectResponse
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

        return redirect()->route('company.technicians.index')
            ->with('success', true);
    }

    public function destroy(Technician $technician): RedirectResponse
    {
        $this->authorize('delete', $technician);

        DB::transaction(function () use ($technician): void {
            $technician->delete();
            $technician->user->delete();
        });

        return redirect()->route('company.technicians.index')
            ->with('success', true);
    }

    public function resetPassword(ResetTechnicianPasswordRequest $request, Technician $technician): RedirectResponse
    {
        $technician->user->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        return redirect()->route('company.technicians.edit', $technician)
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
