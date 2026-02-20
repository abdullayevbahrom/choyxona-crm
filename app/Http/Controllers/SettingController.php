<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $setting = Setting::current();

        return view("settings.index", compact("setting"));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            "company_name" => ["required", "string", "max:150"],
            "company_address" => ["nullable", "string", "max:255"],
            "company_phone" => ["nullable", "string", "max:50"],
            "receipt_footer" => ["nullable", "string", "max:255"],
        ]);

        $setting = Setting::current();
        $setting->update($validated);
        ActivityLogger::log(
            "settings.update",
            $setting,
            "Sozlamalar yangilandi.",
        );

        return back()->with("status", "Sozlamalar yangilandi.");
    }
}
