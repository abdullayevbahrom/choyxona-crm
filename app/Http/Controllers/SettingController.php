<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\SettingUpdateRequest;
use App\Models\Setting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $setting = Setting::current();

        return view('settings.index', compact('setting'));
    }

    public function update(SettingUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $setting = Setting::current();
        $setting->update($validated);
        ActivityLogger::log(
            'settings.update',
            $setting,
            'Sozlamalar yangilandi.',
        );

        return back()->with('status', 'Sozlamalar yangilandi.');
    }
}
