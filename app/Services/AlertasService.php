<?php

namespace App\Services;

use App\Models\Conductor;
use App\Models\DocumentoVehicular;
use App\Models\EquipamientoVehicular;
use App\Models\Mantenimiento;
use App\Models\RegistroCombustible;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AlertasService
{
    /**
     * Documentos vehiculares vencidos o próximos a vencer.
     *
     * @return Collection<int, DocumentoVehicular>
     */
    public function documentosAlerta(User $user, int $dias = 30): Collection
    {
        return DocumentoVehicular::with(['vehiculo.sucursal'])
            ->whereNotNull('vencimiento')
            ->where('vencimiento', '<=', now()->addDays($dias)->endOfDay())
            ->when(! $user->puedeVerTodo(), fn ($q) => $q->whereHas(
                'vehiculo',
                fn ($v) => $v->where('sucursal_id', $user->sucursal_id)
            ))
            ->orderBy('vencimiento')
            ->get();
    }

    /**
     * Mantenimientos con ≤ 300 km restantes para el próximo servicio.
     * Rojo: ≤ 100 km | Amarillo: 101–300 km
     *
     * @return \Illuminate\Support\Collection<int, Mantenimiento>
     */
    public function mantenimientosAlerta(User $user): \Illuminate\Support\Collection
    {
        $vehiculosKm = Vehiculo::query()
            ->when(! $user->puedeVerTodo(), fn ($q) => $q->where('sucursal_id', $user->sucursal_id))
            ->whereNotNull('km_actuales')
            ->pluck('km_actuales', 'id');

        if ($vehiculosKm->isEmpty()) {
            return collect();
        }

        $query = Mantenimiento::with(['vehiculo.sucursal'])
            ->ultimoPorCategoria()
            ->whereHas('vehiculo', function ($q) use ($user) {
                $q->when(! $user->puedeVerTodo(), fn ($v) => $v->where('sucursal_id', $user->sucursal_id));
            })
            ->whereNotNull('proximo_km')
            ->where(function ($q) use ($vehiculosKm) {
                foreach ($vehiculosKm as $vehiculoId => $kmActuales) {
                    $q->orWhere(function ($q2) use ($vehiculoId, $kmActuales) {
                        $q2->where('vehiculo_id', $vehiculoId)
                            ->whereRaw('proximo_km - ? <= 300', [$kmActuales]);
                    });
                }
            });

        return $query->get()->sortBy(function (Mantenimiento $m) use ($vehiculosKm) {
            $kmActuales = $vehiculosKm[$m->vehiculo_id] ?? 0;

            return $m->proximo_km - $kmActuales;
        })->values();
    }

    /**
     * Conductores con licencia vencida o próxima a vencer.
     *
     * @return Collection<int, Conductor>
     */
    public function licenciasAlerta(User $user, int $dias = 30): Collection
    {
        return Conductor::with('sucursal')
            ->whereNotNull('licencia_vencimiento')
            ->where('licencia_vencimiento', '<=', now()->addDays($dias)->endOfDay())
            ->when(! $user->puedeVerTodo(), fn ($q) => $q->where('sucursal_id', $user->sucursal_id))
            ->where('activo', true)
            ->orderBy('licencia_vencimiento')
            ->get();
    }

    /**
     * Extintores vencidos o próximos a vencer (≤30 días).
     *
     * @return Collection<int, EquipamientoVehicular>
     */
    public function equipamientoAlerta(User $user, int $dias = 30): Collection
    {
        return EquipamientoVehicular::with(['vehiculo.sucursal'])
            ->whereNotNull('vencimiento')
            ->where('vencimiento', '<=', now()->addDays($dias)->endOfDay())
            ->when(! $user->puedeVerTodo(), fn ($q) => $q->whereHas(
                'vehiculo',
                fn ($v) => $v->where('sucursal_id', $user->sucursal_id)
            ))
            ->orderBy('vencimiento')
            ->get();
    }

    /**
     * Registros de combustible pendientes de revisión.
     *
     * - admin / visor: todos los pendientes visibles (scope forUser).
     * - jefe_resguardo: solo sus propios envíos pendientes (para ver su backlog).
     */
    public function combustiblePendiente(User $user): int
    {
        return Cache::remember(
            "alertas.combustible.{$user->id}",
            300,
            fn () => RegistroCombustible::pendientes()
                ->when(
                    $user->esJefeResguardo(),
                    fn ($q) => $q->where('enviado_por', $user->id),
                    fn ($q) => $q->forUser($user),
                )
                ->count()
        );
    }

    /**
     * Total de alertas activas (para badge en sidebar). Cache 5 min.
     */
    public function totalAlertas(User $user): int
    {
        return Cache::remember("alertas.total.{$user->id}", 300, fn () => $this->documentosAlerta($user)->count()
            + $this->mantenimientosAlerta($user)->count()
            + $this->licenciasAlerta($user)->count()
            + $this->equipamientoAlerta($user)->count()
            + $this->combustiblePendiente($user));
    }

    /**
     * Invalida el cache de badges para un usuario.
     */
    public function invalidarCache(User $user): void
    {
        Cache::forget("alertas.total.{$user->id}");
        Cache::forget("alertas.combustible.{$user->id}");
    }

    /**
     * Invalida el cache de combustible para TODOS los usuarios relevantes.
     * Útil cuando se crea/aprueba/rechaza un registro: afecta el badge del jefe
     * que lo envió y el de todos los admins/visores.
     */
    public function invalidarCacheCombustible(?User $envioPor = null): void
    {
        // Admin y visor ven el global — invalidamos a todos los que tienen ese rol
        User::role(['admin', 'visor'])
            ->pluck('id')
            ->each(function (int $id) {
                Cache::forget("alertas.combustible.{$id}");
                Cache::forget("alertas.total.{$id}");
            });

        if ($envioPor !== null) {
            Cache::forget("alertas.combustible.{$envioPor->id}");
            Cache::forget("alertas.total.{$envioPor->id}");
        }
    }
}
