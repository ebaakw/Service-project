<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Technician::class);

        $search = $request->string('search')->trim()->value();

        $query = Technician::query()
            ->with(['user', 'company', 'serviceCategories'])
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

        $technicians = $query->paginate(15)->withQueryString();

        return Inertia::render('Platform/Technicians/Index', [
            'technicians' => $technicians,
        ]);
    }

    public function show(Technician $technician): Response
    {
        $this->authorize('view', $technician);

        $technician->load(['user', 'company', 'serviceCategories']);

        return Inertia::render('Platform/Technicians/Show', [
            'technician' => $technician,
        ]);
    }
}
