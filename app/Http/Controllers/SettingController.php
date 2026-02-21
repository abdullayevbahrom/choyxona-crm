<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\SettingUpdateRequest;
use App\Models\Setting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        unset($validated['notification_logo_file']);

        $setting = Setting::current();

        if ($request->hasFile('notification_logo_file')) {
            $uploaded = $request->file('notification_logo_file');
            if (! $uploaded instanceof UploadedFile) {
                throw ValidationException::withMessages([
                    'notification_logo_file' => "Logo fayli noto'g'ri yuborildi.",
                ]);
            }

            $oldPath = $this->extractPublicPathFromUrl(
                (string) $setting->notification_logo_url,
            );
            $newPath = $this->storeLogoAsPng($uploaded);

            $validated['notification_logo_url'] = asset('storage/'.$newPath);

            if ($oldPath !== null && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $setting->update($validated);
        ActivityLogger::log(
            'settings.update',
            $setting,
            'Sozlamalar yangilandi.',
        );

        return back()->with('status', 'Sozlamalar yangilandi.');
    }

    private function storeLogoAsPng(UploadedFile $file): string
    {
        $contents = $file->getContent();
        $image = @imagecreatefromstring((string) $contents);

        if ($image === false) {
            throw ValidationException::withMessages([
                'notification_logo_file' => "Logo faylini PNG formatga o'girishda xatolik yuz berdi.",
            ]);
        }

        ob_start();
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagepng($image);
        imagedestroy($image);
        $pngBinary = (string) ob_get_clean();

        $path = 'branding/qr-logos/'.Str::uuid().'.png';
        Storage::disk('public')->put($path, $pngBinary);

        return $path;
    }

    private function extractPublicPathFromUrl(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        $storagePrefix = '/storage/';
        if (! str_starts_with($path, $storagePrefix)) {
            return null;
        }

        return ltrim(substr($path, strlen($storagePrefix)), '/');
    }
}
