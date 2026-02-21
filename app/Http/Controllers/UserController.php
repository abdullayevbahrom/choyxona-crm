<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UserIndexRequest;
use App\Http\Requests\Users\UserStoreRequest;
use App\Http\Requests\Users\UserUpdateRequest;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(UserIndexRequest $request): View
    {
        $validated = $request->validated();
        $perPage =
            (int) ($validated['per_page'] ??
                config('pagination.default_per_page', 10));

        $query = User::query();

        if (! empty($validated['name'])) {
            $query->where('name', 'like', '%'.$validated['name'].'%');
        }

        if (! empty($validated['email'])) {
            $query->where('email', 'like', '%'.$validated['email'].'%');
        }

        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        return view('users.index', [
            'users' => $query
                ->orderBy('name')
                ->paginate($perPage)
                ->withQueryString(),
            'roles' => User::availableRoles(),
            'filters' => $validated,
            'perPageOptions' => config('pagination.allowed_per_page'),
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
