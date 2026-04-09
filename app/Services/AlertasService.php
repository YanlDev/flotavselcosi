<?php

namespace App\Services;

use App\Models\Conductor;
use App\Models\DocumentoVehicular;
use App\Models\Mantenimiento;
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
            ->when(! $user->esAdmin(), fn ($q) => $q->whereHas(
                'vehiculo',
                fn ($v) => $v->where('sucursal_id', $user->sucursal_id)
            ))
            ->orderBy('vencimiento')
            ->get();
    }

    /**
     * Mantenimientos cuya próxima fecha ≤ 30 días o km restantes ≤ 1000.
     *
     * @return Collection<int, Mantenimiento>
     */
    public function mantenimientosAlerta(User $user): Collection
    {
        $vehiculosKm = Vehiculo::query()
            ->when(! $user->esAdmin(), fn ($q) => $q->where('sucursal_id', $user->sucursal_id))
            ->whereNotNull('km_actuales')
            ->pluck('km_actuales', 'id');

        return Mantenimiento::with(['vehiculo.sucursal'])
            ->whereHas('vehiculo', function ($q) use ($user) {
                $q->when(! $user->esAdmin(), fn ($v) => $v->where('sucursal_id', $user->sucursal_id));
            })
            ->where(function ($q) use ($vehiculosKm) {
                $q->where(fn ($q2) => $q2
                    ->whereNotNull('proxima_fecha')
                    ->where('proxima_fecha', '<=', now()->addDays(30)->endOfDay())
                );

                if ($vehiculosKm->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($vehiculosKm) {
                        $q2->whereNotNull('proximo_km');
                        foreach ($vehiculosKm as $vehiculoId => $kmActuales) {
                            $q2->orWhere(function ($q3) use ($vehiculoId, $kmActuales) {
                                $q3->where('vehiculo_id', $vehiculoId)
                                    ->whereRaw('proximo_km - ? <= 1000', [$kmActuales]);
                            });
                        }
                    });
                }
            })
            ->orderByRaw('proxima_fecha IS NULL ASC')
            ->orderBy('proxima_fecha')
            ->get();
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
            ->when(! $user->esAdmin(), fn ($q) => $q->where('sucursal_id', $user->sucursal_id))
            ->where('activo', true)
            ->orderBy('licencia_vencimiento')
            ->get();
    }

    /**
     * Total de alertas activas (para badge en sidebar). Cache 5 min.
     */
    public function totalAlertas(User $user): int
    {
        return Cache::remember("alertas.total.{$user->id}", 300, fn () => $this->documentosAlerta($user)->count()
            + $this->mantenimientosAlerta($user)->count()
            + $this->licenciasAlerta($user)->count());
    }

    /**
     * Invalida el cache del badge para un usuario.
     */
    public function invalidarCache(User $user): void
    {
        Cache::forget("alertas.total.{$user->id}");
    }
}
