<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            "type" => ["nullable", "in:food,drink,bread,salad,sauce"],
            "q" => ["nullable", "string", "max:200"],
        ]);

        $query = MenuItem::query()->orderBy("name");

        if (!empty($validated["type"])) {
            $query->where("type", $validated["type"]);
        }

        if (!empty($validated["q"])) {
            $query->where("name", "like", "%" . $validated["q"] . "%");
        }

        $items = $query->paginate(30)->withQueryString();

        return view("menu.index", [
            "items" => $items,
            "filters" => $validated,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:200"],
            "type" => ["required", "in:food,drink,bread,salad,sauce"],
            "price" => ["nullable", "numeric", "min:0"],
            "stock_quantity" => ["nullable", "integer", "min:0"],
            "unit" => ["nullable", "string", "max:20"],
            "description" => ["nullable", "string"],
        ]);

        $item = MenuItem::query()->create($validated + ["is_active" => true]);
        ActivityLogger::log("menu.create", $item, "Menyu mahsuloti yaratildi.");

        return back()->with("status", 'Mahsulot qo\'shildi.');
    }

    public function update(
        Request $request,
        MenuItem $menuItem,
    ): RedirectResponse {
        $validated = $request->validate([
            "name" => ["required", "string", "max:200"],
            "type" => ["required", "in:food,drink,bread,salad,sauce"],
            "price" => ["nullable", "numeric", "min:0"],
            "stock_quantity" => ["nullable", "integer", "min:0"],
            "unit" => ["nullable", "string", "max:20"],
            "description" => ["nullable", "string"],
        ]);

        $menuItem->update($validated);
        ActivityLogger::log(
            "menu.update",
            $menuItem,
            "Menyu mahsuloti yangilandi.",
        );

        return back()->with("status", "Mahsulot yangilandi.");
    }

    public function toggleActive(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->update(["is_active" => !$menuItem->is_active]);
        ActivityLogger::log(
            "menu.toggle_active",
            $menuItem,
            "Menyu mahsuloti faolligi almashtirildi.",
        );

        return back()->with("status", "Mahsulot holati yangilandi.");
    }
}
