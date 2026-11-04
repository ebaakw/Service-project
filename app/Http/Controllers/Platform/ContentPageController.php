<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreContentPageRequest;
use App\Http\Requests\Platform\UpdateContentPageRequest;
use App\Models\ContentPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContentPage::class);

        $actor = $request->user();
        $search = $request->string('search')->trim()->value();

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $query = ContentPage::query()->orderBy('slug');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('slug', 'like', '%'.$search.'%')
                    ->orWhere('title_ar', 'like', '%'.$search.'%')
                    ->orWhere('title_en', 'like', '%'.$search.'%');
            });
        }

        $pages = $query->paginate($perPage)->withQueryString();

        $pages->through(function (ContentPage $page) use ($actor): array {
            return [
                'id' => $page->id,
                'slug' => $page->slug,
                'title_ar' => $page->title_ar,
                'title_en' => $page->title_en,
                'is_active' => (bool) $page->is_active,
                'can_update' => $actor !== null && $actor->can('update', $page),
                'can_delete' => $actor !== null && $actor->can('delete', $page),
            ];
        });

        return Inertia::render('Platform/ContentPages/Index', [
            'pages' => $pages,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $actor !== null && $actor->can('create', ContentPage::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ContentPage::class);

        return Inertia::render('Platform/ContentPages/Create');
    }

    public function store(StoreContentPageRequest $request): RedirectResponse
    {
        $this->authorize('create', ContentPage::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        ContentPage::query()->create($data);

        return redirect()
            ->route('platform.content-pages.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function edit(ContentPage $content_page): Response
    {
        $this->authorize('update', $content_page);

        return Inertia::render('Platform/ContentPages/Edit', [
            'page' => $content_page,
        ]);
    }

    public function update(UpdateContentPageRequest $request, ContentPage $content_page): RedirectResponse
    {
        $this->authorize('update', $content_page);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $content_page->update($data);

        return redirect()
            ->route('platform.content-pages.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function destroy(ContentPage $content_page): RedirectResponse
    {
        $this->authorize('delete', $content_page);

        $content_page->delete();

        return redirect()
            ->route('platform.content-pages.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }
}
