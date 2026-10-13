<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManagedUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);
        $search = trim((string) $request->query('search'));
        $role = $request->query('role');
        $allowedRoles = $this->availableRoles($request->user());

        return Inertia::render('Admin/Users/Index', [
            'search' => $search,
            'role' => $role,
            'roles' => $allowedRoles->values(),
            'users' => User::query()->with('role:id,name,slug')
                ->when(! $request->user()->hasRole('super-admin'), fn ($query) => $query->whereHas('role', fn ($query) => $query->whereIn('slug', ['teacher', 'guardian', 'student'])))
                ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
                ->when($allowedRoles->contains('slug', $role), fn ($query) => $query->whereHas('role', fn ($query) => $query->where('slug', $role)))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Users/Form', ['roles' => $this->availableRoles($request->user())->values()]);
    }

    public function store(ManagedUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $data = $request->validated();
        $this->ensureRoleIsAssignable($request->user(), (int) $data['role_id']);

        DB::transaction(function () use ($request, $data) {
            $user = User::create($data);
            $this->log($request, 'user.created', $user, ['role_id' => $user->role_id]);
        });

        return to_route('admin.users.index')->with('success', 'User account created.');
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Admin/Users/Form', ['user' => $user->load('role:id,name,slug'), 'roles' => $this->availableRoles($request->user())->values()]);
    }

    public function update(ManagedUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $data = $request->validated();
        $this->ensureRoleIsAssignable($request->user(), (int) $data['role_id']);
        $this->ensureLastSuperAdminIsProtected($user, (int) $data['role_id']);
        if (blank($data['password'])) {
            unset($data['password']);
        }

        DB::transaction(function () use ($request, $user, $data) {
            $user->update($data);
            $this->log($request, 'user.updated', $user, ['role_id' => $user->role_id]);
        });

        return to_route('admin.users.index')->with('success', 'User account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $this->ensureLastSuperAdminIsProtected($user);
        if ($user->notices()->exists() || $user->events()->exists()) {
            throw ValidationException::withMessages(['account' => 'This account has published content and cannot be deleted. Change its role instead.']);
        }

        DB::transaction(function () use ($request, $user) {
            $this->log($request, 'user.deleted', $user, ['email' => $user->email]);
            $user->delete();
        });

        return to_route('admin.users.index')->with('success', 'User account deleted.');
    }

    private function availableRoles(User $actor)
    {
        return Role::query()->when(! $actor->hasRole('super-admin'), fn ($query) => $query->whereIn('slug', ['teacher', 'guardian', 'student']))->orderBy('id')->get(['id', 'name', 'slug']);
    }

    private function ensureRoleIsAssignable(User $actor, int $roleId): void
    {
        abort_unless($this->availableRoles($actor)->contains('id', $roleId), 403);
    }

    private function ensureLastSuperAdminIsProtected(User $user, ?int $nextRoleId = null): void
    {
        if (! $user->hasRole('super-admin') || ($nextRoleId !== null && $nextRoleId === $user->role_id)) {
            return;
        }

        if (User::query()->whereHas('role', fn ($query) => $query->where('slug', 'super-admin'))->count() <= 1) {
            throw ValidationException::withMessages(['role_id' => 'The last Super Admin account cannot be removed or changed.']);
        }
    }

    private function log(Request $request, string $action, User $user, array $properties): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'properties' => json_encode($properties),
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
