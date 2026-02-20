<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UserStoreRequest;
use App\Http\Requests\Users\UserUpdateRequest;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->orderBy('name')->paginate(20),
            'roles' => User::availableRoles(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create($validated);
        ActivityLogger::log('users.create', $user, 'Foydalanuvchi yaratildi.');

        return back()->with('status', 'Foydalanuvchi qo\'shildi.');
    }

    public function update(
        UserUpdateRequest $request,
        User $user,
    ): RedirectResponse {
        $validated = $request->validated();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);
        ActivityLogger::log('users.update', $user, 'Foydalanuvchi yangilandi.');

        return back()->with('status', 'Foydalanuvchi yangilandi.');
    }
}
