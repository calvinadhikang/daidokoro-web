<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminLoginController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('admin.menus.index');
        }

        return Inertia::render('admin/login');
    }

    public function store(StoreAdminLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! Auth::attempt([
            'email' => $validated['username'],
            'password' => $validated['password'],
        ])) {
            throw ValidationException::withMessages([
                'username' => 'The username or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.menus.index'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
