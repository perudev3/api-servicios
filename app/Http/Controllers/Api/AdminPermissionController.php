<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminPermissionController extends Controller
{
    const MODULES = ['dashboard', 'users', 'categories'];

    public function index()
    {
        $admins = User::where('role', 'admin')
            ->where('id', '!=', auth()->id())
            ->with('adminPermission')
            ->get()
            ->map(fn($u) => $this->formatAdmin($u));

        return response()->json([
            'success' => true,
            'admins'  => $admins,
            'modules' => self::MODULES,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo el super administrador puede crear otros administradores.',
            ], 403);
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'modules'   => 'required|array',
            'modules.*' => 'boolean',
        ]);

        $modules = $this->sanitizeModules($request->modules);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        AdminPermission::create([
            'user_id'        => $user->id,
            'modules'        => $modules,
            'is_super_admin' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => "✅ Sub-administrador {$user->name} creado correctamente.",
            'admin'   => $this->formatAdmin($user->load('adminPermission')),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Sin autorización.'], 403);
        }

        if ($user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'No puedes editar al super administrador.'], 422);
        }

        $request->validate([
            'name'      => 'sometimes|string|max:255',
            'modules'   => 'sometimes|array',
            'modules.*' => 'boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->has('name')) {
            $user->update(['name' => $request->name]);
        }

        if ($request->has('is_active')) {
            $user->update(['is_active' => $request->is_active]);
            if (!$request->is_active) {
                $user->tokens()->delete();
            }
        }

        if ($request->has('modules')) {
            $modules = $this->sanitizeModules($request->modules);
            $user->adminPermission->update(['modules' => $modules]);
        }

        return response()->json([
            'success' => true,
            'message' => '✅ Permisos actualizados correctamente.',
            'admin'   => $this->formatAdmin($user->fresh('adminPermission')),
        ]);
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Sin autorización.'], 403);
        }

        if ($user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'No puedes eliminar al super administrador.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => '🗑️ Sub-administrador eliminado.',
        ]);
    }

    private function sanitizeModules(array $input): array
    {
        $modules = [];
        foreach (self::MODULES as $mod) {
            $modules[$mod] = (bool) ($input[$mod] ?? false);
        }
        return $modules;
    }

    private function formatAdmin(User $user): array
    {
        $perm    = $user->adminPermission;
        $modules = [];
        foreach (self::MODULES as $mod) {
            $modules[$mod] = $perm ? (bool) ($perm->modules[$mod] ?? false) : true;
        }

        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'is_active'      => $user->is_active,
            'is_super_admin' => $user->isSuperAdmin(),
            'modules'        => $modules,
            'created_at'     => $user->created_at->format('d/m/Y'),
        ];
    }
}