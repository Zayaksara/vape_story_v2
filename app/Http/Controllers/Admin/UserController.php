<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->abortIfNotAdmin($request);

        $users = User::query()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'role', 'created_at'])
            ->map(fn (User $u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->role instanceof UserRole ? $u->role->value : (string) $u->role,
                'created_at' => $u->created_at?->toIso8601String(),
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
        $this->abortIfNotAdmin($request);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255', 'regex:/^[\pL\s\.\-\']+$/u'],
            'email'    => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')],
            'role'     => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => ['required', 'confirmed', $this->strongPassword()],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'role'     => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        Log::channel(config('logging.default'))->info('admin.users.created', [
            'actor_id' => $request->user()?->id,
            'user_id'  => $user->id,
            'email'    => $user->email,
            'role'     => $data['role'],
        ]);

        return back()->with('success', 'Akun berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->abortIfNotAdmin($request);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255', 'regex:/^[\pL\s\.\-\']+$/u'],
            'email'    => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => ['nullable', 'confirmed', $this->strongPassword()],
        ]);

        $actor = $request->user();
        $isSelf = $actor?->id === $user->id;

        if ($isSelf && $data['role'] !== UserRole::ADMIN->value) {
            return back()->withErrors(['role' => 'Anda tidak dapat menurunkan role akun sendiri.']);
        }

        $currentRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        if ($currentRole === UserRole::ADMIN->value
            && $data['role'] !== UserRole::ADMIN->value
            && $this->countAdminsExcept($user->id) === 0) {
            return back()->withErrors(['role' => 'Tidak boleh menurunkan admin terakhir.']);
        }

        $payload = [
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        Log::channel(config('logging.default'))->info('admin.users.updated', [
            'actor_id' => $actor?->id,
            'user_id'  => $user->id,
            'changed'  => array_keys($payload),
        ]);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->abortIfNotAdmin($request);

        if ($request->user()?->id === $user->id) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        $currentRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        if ($currentRole === UserRole::ADMIN->value && $this->countAdminsExcept($user->id) === 0) {
            return back()->withErrors(['user' => 'Tidak boleh menghapus admin terakhir.']);
        }

        $email = $user->email;
        $id = $user->id;
        $user->delete();

        Log::channel(config('logging.default'))->warning('admin.users.deleted', [
            'actor_id' => $request->user()?->id,
            'user_id'  => $id,
            'email'    => $email,
        ]);

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    private function strongPassword(): Password
    {
        return Password::min(8)->letters()->mixedCase()->numbers()->uncompromised();
    }

    private function countAdminsExcept(string $exceptId): int
    {
        return DB::table('users')
            ->where('role', UserRole::ADMIN->value)
            ->where('id', '!=', $exceptId)
            ->count();
    }

    private function abortIfNotAdmin(Request $request): void
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }
    }
}
