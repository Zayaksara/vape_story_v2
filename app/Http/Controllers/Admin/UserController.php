<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'role', 'email_verified_at', 'created_at'])
            ->map(fn (User $u) => [
                'id'                => $u->id,
                'name'              => $u->name,
                'email'             => $u->email,
                'role'              => $u->role instanceof UserRole ? $u->role->value : (string) $u->role,
                'email_verified_at' => $u->email_verified_at?->toIso8601String(),
                'created_at'        => $u->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/ManajemenAkun', [
            'users' => $users,
            'roles' => collect(UserRole::cases())->map(fn ($r) => [
                'value' => $r->value,
                'label' => ucfirst($r->value),
            ])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role'     => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => ['required', 'confirmed', Password::min(8)],
            'verified' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'role'              => $data['role'],
            'password'          => Hash::make($data['password']),
            'email_verified_at' => ($data['verified'] ?? false) ? now() : null,
        ]);

        return back()->with('success', 'Akun berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'verified' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if (array_key_exists('verified', $data)) {
            $payload['email_verified_at'] = $data['verified'] ? ($user->email_verified_at ?? now()) : null;
        }

        $user->update($payload);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        $user->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
