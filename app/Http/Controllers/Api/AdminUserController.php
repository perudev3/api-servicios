<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // ── Lista usuarios con filtros, búsqueda y paginación ──────────
    public function index(Request $request)
    {
        $query = User::query();

        // Búsqueda por nombre o email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por rol
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filtro por estado
        if ($request->filled('status') && $request->status !== 'all') {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;

                case 'inactive':
                    $query->where('is_active', false);
                    break;

                case 'verified':
                    $query->whereNotNull('email_verified_at');
                    break;

                case 'pending':
                    $query->whereNull('email_verified_at');
                    break;
            }
        }

        // Ordenamiento
        $sortable = ['name', 'email', 'created_at', 'role', 'is_active'];
        $sort     = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir      = $request->dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        // Paginación
        $perPage = in_array((int) $request->per_page, [10, 25, 50])
            ? (int) $request->per_page
            : 10;

        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'users'   => $users,
            'stats'   => $this->getStats(),
        ]);
    }

    // ── Ver detalle de un usuario ──────────────────────────────────
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'user'    => $user,
        ]);
    }

    // ── Actualizar datos de un usuario ─────────────────────────────
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role'  => 'sometimes|in:admin,client,professional',
        ]);

        $user->update($request->only('name', 'email', 'role'));

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'user'    => $user->fresh(),
        ]);
    }

    // ── Habilitar / Inhabilitar usuario (toggle) ───────────────────
    public function toggleStatus(User $user)
    {
        // El admin no puede inhabilitarse a sí mismo
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes inhabilitar tu propia cuenta.',
            ], 422);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        // Si se inhabilita → revocar todos sus tokens activos de Passport
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success'   => true,
            'is_active' => $user->is_active,
            'message'   => $user->is_active
                ? "✅ {$user->name} ha sido habilitado."
                : "🚫 {$user->name} ha sido inhabilitado y sus sesiones fueron revocadas.",
        ]);
    }

    // ── Acciones en lote ───────────────────────────────────────────
    public function bulk(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:users,id',
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        // Nunca afectar al admin autenticado
        $ids = collect($request->ids)
            ->reject(function ($id) {
                return $id === auth()->id();
            })
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede aplicar la acción a tu propia cuenta.',
            ], 422);
        }

        switch ($request->action) {
            case 'activate':
                User::whereIn('id', $ids)->update(['is_active' => true]);
                break;

            case 'deactivate':
                User::whereIn('id', $ids)->each(function ($user) {
                    $user->update(['is_active' => false]);
                    $user->tokens()->delete(); // revocar sesiones
                });
                break;

            case 'delete':
                User::whereIn('id', $ids)->delete();
                break;
        }

        switch ($request->action) {
            case 'activate':
                $label = 'habilitados';
                break;

            case 'deactivate':
                $label = 'inhabilitados';
                break;

            case 'delete':
                $label = 'eliminados';
                break;

            default:
                $label = '';
        }

        return response()->json([
            'success' => true,
            'message' => "{$ids->count()} usuarios {$label} correctamente.",
            'stats'   => $this->getStats(), // devolver stats actualizados
        ]);
    }

    // ── Stats del dashboard ────────────────────────────────────────
    public function stats()
    {
        return response()->json([
            'success' => true,
            'stats'   => $this->getStats(),
        ]);
    }

    // ── Método privado reutilizable para estadísticas ──────────────
    private function getStats(): array
    {
        return [
            'total'         => User::count(),
            'clients'       => User::where('role', 'client')->count(),
            'professionals' => User::where('role', 'professional')->count(),
            'admins'        => User::where('role', 'admin')->count(),
            'active'        => User::where('is_active', true)->count(),
            'inactive'      => User::where('is_active', false)->count(),
            'verified'      => User::whereNotNull('email_verified_at')->count(),
        ];
    }
}
