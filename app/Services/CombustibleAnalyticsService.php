<?php

namespace App\Services;

use App\Models\RegistroCombustible;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CombustibleAnalyticsService
{
    /**
     * Construye el query base de registros aprobados, ya filtrado por usuario
     * (scope sucursal para jefe_resguardo) y por los filtros adicionales.
     *
     * @param  array{vehiculo_id?: int|null, sucursal_id?: int|null, desde?: string|null, hasta?: string|null, tipo_combustible?: string|null}  $filtros
     */
    public function baseQuery(User $user, array $filtros = []): Builder
    {
        return RegistroCombustible::query()
            ->aprobados()
            ->forUser($user)
            ->when($filtros['vehiculo_id'] ?? null, fn ($q, $v) => $q->where('vehiculo_id', $v))
            ->when(
                ($filtros['sucursal_id'] ?? null) && $user->puedeVerTodo(),
                fn ($q) => $q->where('sucursal_id', $filtros['sucursal_id'])
            )
            ->when($filtros['tipo_combustible'] ?? null, fn ($q, $t) => $q->where('tipo_combustible', $t))
            ->when(
                $filtros['desde'] ?? null,
                fn ($q, $d) => $q->whereDate('fecha_carga', '>=', $d)
            )
            ->when(
                $filtros['hasta'] ?? null,
                fn ($q, $h) => $q->whereDate('fecha_carga', '<=', $h)
            );
    }

    /**
     * KPIs del periodo filtrado.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{cargas: int, galones: float, monto: float, vehiculos: int, precio_promedio: float}
     */
    public function kpis(User $user, array $filtros = []): array
    {
        $row = $this->baseQuery($user, $filtros)
            ->selectRaw('COUNT(*) AS cargas')
            ->selectRaw('COALESCE(SUM(galones), 0) AS galones')
            ->selectRaw('COALESCE(SUM(monto_total), 0) AS monto')
            ->selectRaw('COUNT(DISTINCT vehiculo_id) AS vehiculos')
            ->first();

        $cargas = (int) ($row->cargas ?? 0);
        $galones = (float) ($row->galones ?? 0);
        $monto = (float) ($row->monto ?? 0);
        $vehiculos = (int) ($row->vehiculos ?? 0);

        return [
            'cargas' => $cargas,
            'galones' => $galones,
            'monto' => $monto,
            'vehiculos' => $vehiculos,
            'precio_promedio' => $galones > 0 ? $monto / $galones : 0.0,
        ];
    }

    /**
     * Galones y monto agrupados por mes (YYYY-MM). Si no se especifica un rango
     * en los filtros, retorna los últimos 12 meses rellenando con cero los
     * meses sin cargas.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{labels: list<string>, galones: list<float>, monto: list<float>}
     */
    public function porMes(User $user, array $filtros = []): array
    {
        $desde = isset($filtros['desde'])
            ? CarbonImmutable::parse($filtros['desde'])->startOfMonth()
            : CarbonImmutable::now()->subMonths(11)->startOfMonth();
        $hasta = isset($filtros['hasta'])
            ? CarbonImmutable::parse($filtros['hasta'])->endOfMonth()
            : CarbonImmutable::now()->endOfMonth();

        $filtrosConRango = array_merge($filtros, [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
        ]);

        $expr = match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char(fecha_carga, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', fecha_carga)",
            default => "DATE_FORMAT(fecha_carga, '%Y-%m')",
        };

        $filas = $this->baseQuery($user, $filtrosConRango)
            ->selectRaw("{$expr} AS mes")
            ->selectRaw('COALESCE(SUM(galones), 0) AS galones')
            ->selectRaw('COALESCE(SUM(monto_total), 0) AS monto')
            ->groupBy(DB::raw($expr))
            ->orderBy(DB::raw($expr))
            ->get()
            ->keyBy('mes');

        $labels = [];
        $galones = [];
        $monto = [];

        $cursor = $desde;
        while ($cursor->lessThanOrEqualTo($hasta)) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->translatedFormat('M Y');
            $galones[] = (float) ($filas[$key]->galones ?? 0);
            $monto[] = (float) ($filas[$key]->monto ?? 0);
            $cursor = $cursor->addMonth();
        }

        return compact('labels', 'galones', 'monto');
    }

    /**
     * Top N vehículos por gasto en el periodo filtrado.
     *
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, object>
     */
    public function topVehiculos(User $user, array $filtros = [], int $limite = 10): Collection
    {
        return $this->baseQuery($user, $filtros)
            ->join('vehiculos', 'vehiculos.id', '=', 'registros_combustible.vehiculo_id')
            ->groupBy('registros_combustible.vehiculo_id', 'vehiculos.placa', 'vehiculos.marca', 'vehiculos.modelo')
            ->selectRaw('registros_combustible.vehiculo_id AS vehiculo_id')
            ->selectRaw('vehiculos.placa AS placa')
            ->selectRaw('vehiculos.marca AS marca')
            ->selectRaw('vehiculos.modelo AS modelo')
            ->selectRaw('COUNT(*) AS cargas')
            ->selectRaw('COALESCE(SUM(registros_combustible.galones), 0) AS galones')
            ->selectRaw('COALESCE(SUM(registros_combustible.monto_total), 0) AS monto')
            ->orderByDesc('monto')
            ->limit($limite)
            ->get()
            ->map(fn ($row) => (object) [
                'vehiculo_id' => (int) $row->vehiculo_id,
                'placa' => $row->placa,
                'marca' => $row->marca,
                'modelo' => $row->modelo,
                'cargas' => (int) $row->cargas,
                'galones' => (float) $row->galones,
                'monto' => (float) $row->monto,
            ]);
    }

    /**
     * Distribución de gasto por sucursal (solo cuando el usuario ve todo).
     *
     * @param  array<string, mixed>  $filtros
     * @return array{labels: list<string>, monto: list<float>}
     */
    public function porSucursal(User $user, array $filtros = []): array
    {
        if (! $user->puedeVerTodo()) {
            return ['labels' => [], 'monto' => []];
        }

        $filas = $this->baseQuery($user, $filtros)
            ->join('sucursales', 'sucursales.id', '=', 'registros_combustible.sucursal_id')
            ->groupBy('sucursales.id', 'sucursales.nombre')
            ->selectRaw('sucursales.nombre AS nombre')
            ->selectRaw('COALESCE(SUM(monto_total), 0) AS monto')
            ->orderByDesc('monto')
            ->get();

        return [
            'labels' => $filas->pluck('nombre')->all(),
            'monto' => $filas->pluck('monto')->map(fn ($v) => (float) $v)->all(),
        ];
    }

    /**
     * Distribución de cargas por tipo de combustible.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{labels: list<string>, cargas: list<int>}
     */
    public function porTipoCombustible(User $user, array $filtros = []): array
    {
        $filas = $this->baseQuery($user, $filtros)
            ->whereNotNull('tipo_combustible')
            ->groupBy('tipo_combustible')
            ->selectRaw('tipo_combustible')
            ->selectRaw('COUNT(*) AS cargas')
            ->orderByDesc('cargas')
            ->get();

        return [
            'labels' => $filas->pluck('tipo_combustible')->map(fn ($t) => ucfirst((string) $t))->all(),
            'cargas' => $filas->pluck('cargas')->map(fn ($v) => (int) $v)->all(),
        ];
    }
}
