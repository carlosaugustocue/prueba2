<?php

namespace App\Modules\AdminUsers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminUsers\Requests\StoreUserRequest;
use App\Modules\AdminUsers\Requests\UpdateUserRequest;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $roleId = $request->integer('role_id') ?: null;
        $status = $request->string('status')->toString(); // active|inactive|null

        $query = User::query()->with('role');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($roleId) {
            $query->where('role_id', $roleId);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'is_active' => (bool) $u->is_active,
                'role' => $u->role ? [
                    'id' => $u->role->id,
                    'name' => $u->role->name,
                    'display_name' => $u->role->display_name,
                ] : null,
                'created_at_formatted' => $u->created_at?->format('Y-m-d H:i'),
            ]);

        $roles = Role::query()
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name', 'permissions']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search ?: null,
                'role_id' => $roleId,
                'status' => $status ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        $roles = Role::query()
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name', 'permissions']);

        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        User::create($data);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user): Response
    {
        $roles = Role::query()
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name', 'permissions']);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role_id' => $user->role_id,
                'is_active' => (bool) $user->is_active,
            ],
            'roles' => $roles,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($request->user()?->id === $user->id && array_key_exists('is_active', $data) && $data['is_active'] === false) {
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        if (! isset($data['password']) || $data['password'] === null || $data['password'] === '') {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $user->update([
            'is_active' => ! (bool) $user->is_active,
        ]);

        return back()->with('success', 'Estado del usuario actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario eliminado.');
    }
}

