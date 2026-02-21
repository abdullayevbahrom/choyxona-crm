<?php

namespace App\Http\Controllers;

use App\Http\Requests\Menu\MenuIndexRequest;
use App\Http\Requests\Menu\MenuUpsertRequest;
use App\Models\MenuItem;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(MenuIndexRequest $request): View
    {
        $validated = $request->validated();

        $query = MenuItem::query()->orderBy('name');

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (! empty($validated['q'])) {
            $query->where('name', 'like', '%'.$validated['q'].'%');
        }

        $perPage =
            (int) ($validated['per_page'] ??
                config('pagination.default_per_page', 10));

        $items = $query->paginate($perPage)->withQueryString();

        return view('menu.index', [
            'items' => $items,
            'filters' => $validated,
            'perPageOptions' => config('pagination.allowed_per_page'),
        ]);
    }

    public function store(MenuUpsertRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $item = MenuItem::query()->create($validated + ['is_active' => true]);
        ActivityLogger::log('menu.create', $item, 'Menyu mahsuloti yaratildi.');

        return back()->with('status', 'Mahsulot qo\'shildi.');
    }

    public function update(
        MenuUpsertRequest $request,
        MenuItem $menuItem,
    ): RedirectResponse {
        $validated = $request->validated();

        $menuItem->update($validated);
        ActivityLogger::log(
            'menu.update',
            $menuItem,
            'Menyu mahsuloti yangilandi.',
        );

        return back()->with('status', 'Mahsulot yangilandi.');
    }

    public function toggleActive(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->update(['is_active' => ! $menuItem->is_active]);
        ActivityLogger::log(
            'menu.toggle_active',
            $menuItem,
            'Menyu mahsuloti faolligi almashtirildi.',
        );

        return back()->with('status', 'Mahsulot holati yangilandi.');
    }
}
