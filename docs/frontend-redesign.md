# Rediseño Frontend — Selcosi Flota

Documento base del rediseño visual del sistema. Todas las fases deben ejecutarse en orden y cada una debe quedar funcional antes de pasar a la siguiente.

## Objetivo

Transformar la UI actual (Flux por defecto, accent emerald genérico) en una interfaz propia, profesional y densa en información al estilo de dashboards administrativos tipo **Minia**, manteniendo Flux como base de componentes para no perder velocidad de desarrollo.

## Principios de diseño

1. **Identidad propia** — nada que haga pensar "es otro Flux default".
2. **Información primero** — datos densos, tipografía técnica para números, jerarquía clara.
3. **Clickeabilidad honesta** — si algo parece botón, es botón. Badges de estado NO son clickeables.
4. **Incremental** — se migra módulo por módulo; nunca romper lo que funciona.
5. **Dark mode de primera clase** — no es un afterthought.

---

## Sistema de diseño

### Paleta

**Primario — Verde corporativo Selcosi**
```
--color-brand-50:  #f0fdf4
--color-brand-100: #dcfce7
--color-brand-200: #bbf7d0
--color-brand-300: #86efac
--color-brand-400: #4ade80
--color-brand-500: #16a34a   ← base
--color-brand-600: #15803d   ← acciones principales
--color-brand-700: #166534
--color-brand-800: #14532d
--color-brand-900: #052e16
--color-brand-950: #022c1a
```

**Neutrales** — slate (ya configurado).

**Semánticos**
- `success` → brand (verde corporativo)
- `warning` → amber-500 / amber-600
- `danger`  → red-500 / red-600
- `info`    → sky-500 / sky-600

### Tipografía

- **UI:** `Inter` (400, 500, 600, 700) — reemplaza Instrument Sans.
- **Datos numéricos / códigos:** `JetBrains Mono` (400, 500) — para placas, kilometraje, litros, IDs visibles, timestamps.
- Escala: usar la default de Tailwind; títulos de página `text-2xl font-semibold`, secciones `text-lg font-semibold`, labels `text-xs uppercase tracking-wide text-slate-500`.

### Tokens visuales

- Radius base: `rounded-xl` (12px) para cards, `rounded-lg` para inputs/botones.
- Sombras: `shadow-sm` default en cards, `shadow-md` en hover/elevated.
- Bordes: 1px `border-slate-200` / `dark:border-slate-800`.
- Spacing de cards: `p-5` o `p-6`.

### Logo

`public/selcosilog.png` — usar en sidebar y auth. Validar si necesita versión para fondo oscuro.

---

## Fases de ejecución

### Fase 1 — Fundaciones (tokens + fuentes + paleta)
**Archivos:** `resources/css/app.css`, `resources/views/layouts/app.blade.php` (o donde esté `<head>`).

- [ ] Importar Inter y JetBrains Mono desde Bunny Fonts (o Google Fonts) en el layout.
- [ ] Actualizar `@theme` en `app.css`:
  - `--font-sans: 'Inter', ...`
  - `--font-mono: 'JetBrains Mono', ...`
  - Definir `--color-brand-*` (50–950).
  - Reapuntar `--color-accent` → `--color-brand-600`.
- [ ] Ajustar variante dark.
- [ ] Clase utilitaria `.font-mono-data` para tablas de datos.
- [ ] Smoke test: `php artisan test --compact` + revisar dashboard visualmente.

**Criterio de hecho:** al recargar cualquier página, la fuente y el verde corporativo ya se ven globalmente sin tocar más vistas.

### Fase 2 — Shell de la aplicación (sidebar + header)
**Archivos:** `resources/views/layouts/app/sidebar.blade.php`, `resources/views/layouts/app/header.blade.php`.

- [ ] Sidebar:
  - Logo Selcosi arriba.
  - Agrupaciones con header (`GESTIÓN`, `OPERACIÓN`, `ADMIN`).
  - Item activo con barra lateral verde corporativo + fondo sutil.
  - Iconos Lucide/Heroicons consistentes.
  - Colapsable en desktop, drawer en mobile.
- [ ] Header:
  - Breadcrumb dinámico.
  - Selector de sucursal activa (si aplica al rol).
  - Notificaciones / alertas pendientes.
  - Menú de usuario con avatar + rol.

**Criterio de hecho:** navegación visible y coherente en todos los módulos, sin romper rutas existentes.

### Fase 3 — Librería de componentes UI propios
**Carpeta nueva:** `resources/views/components/ui/`.

Componentes a crear:
- [ ] `stat-card.blade.php` — KPI con icono, valor, label, delta, color semántico.
- [ ] `page-header.blade.php` — título, subtítulo, breadcrumb, slot de acciones.
- [ ] `section-card.blade.php` — card con slots `header`, `body`, `footer`.
- [ ] `data-table.blade.php` — wrapper sobre `flux:table` con empty state, loading, slot de filtros.
- [ ] `badge-status.blade.php` — mapea estado → color semántico. Props: `status`, `map`.
- [ ] `empty-state.blade.php` — icono, título, descripción, CTA.
- [ ] `metric-trend.blade.php` — valor + flecha + % delta.

**Criterio de hecho:** cada componente documentado con ejemplo de uso en un comentario Blade arriba del archivo.

### Fase 4 — Dashboard (vitrina del nuevo estilo)
**Archivo:** `resources/views/pages/dashboard/⚡index.blade.php`.

- [ ] Reemplazar layout actual por grid de `stat-card`s (vehículos activos, combustible del mes, alertas pendientes, documentos por vencer).
- [ ] Sección de gráficos (mantenimientos, consumo) dentro de `section-card`.
- [ ] Tabla de últimas alertas con `data-table`.
- [ ] Aplicar `page-header`.

**Criterio de hecho:** el dashboard se ve claramente distinto y sirve de referencia visual para el resto de módulos.

### Fase 5 — Módulo Vehículos
Vistas: `index`, `show`, `form`, `fotos`, `documentos`, `mantenimientos`, `combustible`.

- [ ] `index`: data-table con placa (mono), marca/modelo, estado (badge), sucursal, acciones. Ocultar IDs y timestamps.
- [ ] `show`: layout de 2 columnas — info del vehículo + tabs (fotos, documentos, mantenimientos, combustible).
- [ ] `form`: secciones agrupadas con `section-card`.
- [ ] Aplicar `page-header` en todas.
- [ ] Decidir qué es clickeable: placa → show; conductor → ficha; acciones con iconos claros.

### Fase 6 — Módulo Combustible
Vistas: `index`, `show`. Aplicar mismos patrones. Valores numéricos en mono.

### Fase 7 — Módulo Conductores
Aplicar patrones. Avatar + nombre clickeable → ficha.

### Fase 8 — Módulo Alertas
Listado con badges semánticos por severidad. Resaltar vencidas.

### Fase 9 — Admin
Gestión de usuarios, roles, sucursales con data-tables consistentes.

### Fase 10 — Settings + Auth
- Settings: layout de 2 columnas (nav lateral + contenido).
- Auth (login, register, 2fa, password reset): aplicar branding nuevo + logo grande.

---

## Reglas transversales aplicables a todos los módulos

### Qué mostrar en listados
- Identificador humano (placa, nombre, código).
- Estado (badge).
- 2-4 campos clave del dominio.
- Acciones (ver, editar, eliminar) como iconos con tooltip.

### Qué ocultar en listados (mover a `show`)
- IDs internos.
- `created_at` / `updated_at` (salvo que sea crítico).
- Foreign keys crudas — mostrar la relación ya resuelta.
- Campos largos de texto / notas.

### Clickeable vs no clickeable
- **Clickeable:** fila completa o identificador principal → lleva a `show`.
- **Clickeable:** nombre de entidad relacionada → lleva a su ficha.
- **NO clickeable:** badges de estado, iconos decorativos, métricas en stat-cards.

### Estados vacíos
Toda tabla/listado debe usar `empty-state` cuando no hay datos, con CTA clara.

### Responsive
- Mobile: sidebar colapsa a drawer, tablas se transforman en cards apiladas o scroll horizontal con columnas priorizadas.
- Desktop: aprovechar ancho con grids de 2–4 columnas en formularios.

---

## Checklist de calidad por fase

Antes de cerrar una fase:
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact`
- [ ] Revisar en modo claro y oscuro.
- [ ] Revisar en viewport mobile.
- [ ] Confirmar con el usuario antes de pasar a la siguiente fase.
