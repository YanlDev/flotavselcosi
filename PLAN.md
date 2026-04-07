# SELCOSI FLOTA — Plan Maestro del Sistema

> Documento de planificación técnica y funcional.
> Versión 1.2 — Abril 2026 | Última actualización: 2026-04-07

---

## 1. Contexto del negocio

**Empresa:** SELCOSI EXPORT S.A.C. — empresa peruana con operaciones en 6 sucursales:
Juliaca, Lima, Trujillo, Pucallpa, Puno, Cusco.

**Problema que resuelve:** Gestión centralizada de la flota vehicular dispersa entre sucursales.
Cada sucursal tiene un jefe de resguardo que registra el consumo de combustible de sus vehículos.
La gerencia (admin) tiene visibilidad y control total desde cualquier sucursal.

**Contexto legal peruano:** Los vehículos tienen datos de la Tarjeta de Propiedad SUNARP
(número de motor, número de chasis, VIN/serie, propietario registrado).

---

## 2. Stack tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje servidor | PHP | 8.4 |
| Framework | Laravel | 13 |
| Frontend reactivo | Livewire | 4 |
| Componentes UI | Flux UI (Free) | 2 |
| CSS | Tailwind CSS | 4 |
| Auth backend | Laravel Fortify | 1 |
| Roles y permisos | Spatie Laravel Permission | ^6 |
| Base de datos | MySQL (Herd local) | 8 |
| Storage archivos | Wasabi S3 (bucket: `selcosilaravel`) | — |
| PDF | barryvdh/laravel-dompdf | ^3 |
| Excel | maatwebsite/excel | ^4 |
| Email | Laravel Mail + Resend | — |
| Queue/Jobs | Laravel Queue (database driver) | — |
| Testing | Pest PHP | 4 |
| Servidor local | Laravel Herd | — |

### Por qué este stack

- **Livewire 4 + Flux**: Interactividad sin escribir JavaScript. Formularios, modales, tablas
  reactivas con `wire:model`, `wire:click`, `wire:loading` — sin API routes separadas.
- **Spatie Permission**: El estándar para roles en Laravel. Integra con Gates/Policies.
- **MySQL local con Herd**: Sin dependencia externa para desarrollo.
- **Wasabi S3**: Bucket `selcosilaravel`. Storage de fotos y documentos vehiculares.
- **Resend**: Envío de emails de invitación y recuperación de contraseña.

---

## 3. Roles del sistema

| Rol | Descripción | Acceso |
|---|---|---|
| `admin` | Gerencia / administrador central | Sin restricciones. Control total del sistema y todas las sucursales. |
| `jefe_resguardo` | Jefe de resguardo de una sucursal | Solo ve vehículos de su sucursal. **Única acción de escritura: registrar combustible.** |
| `visor` | Usuario de solo lectura | Ve vehículos de su sucursal (si tiene) o toda la flota. No puede crear, editar ni eliminar nada. |

### Matriz de permisos detallada

| Acción | admin | jefe_resguardo | visor |
|---|:---:|:---:|:---:|
| Ver vehículos (todas las sucursales) | ✅ | ❌ (solo su sucursal) | ❌ (solo su sucursal) |
| Crear vehículo | ✅ | ❌ | ❌ |
| Editar vehículo | ✅ | ❌ | ❌ |
| Eliminar vehículo | ✅ | ❌ | ❌ |
| Enviar registro combustible (fotos) | ✅ | ✅ (solo su sucursal) | ❌ |
| Revisar y aprobar/rechazar combustible | ✅ | ❌ | ❌ |
| Ver historial combustible (aprobados) | ✅ | ✅ (solo su sucursal) | ✅ (solo su sucursal) |
| Subir documentos vehiculares | ✅ | ❌ | ❌ |
| Registrar mantenimientos | ✅ | ❌ | ❌ |
| Subir fotos | ✅ | ❌ | ❌ |
| Gestionar conductores | ✅ | ❌ | ❌ |
| Panel alertas | ✅ | ✅ (solo su sucursal) | ✅ (solo su sucursal) |
| Gestionar usuarios | ✅ | ❌ | ❌ |
| Gestionar invitaciones | ✅ | ❌ | ❌ |
| Gestionar sucursales | ✅ | ❌ | ❌ |
| Exportar Excel / PDF | ✅ | ❌ | ❌ |

---

## 4. Base de datos — Esquema completo

### Tabla `sucursales`

```
id              BIGINT PK auto_increment
nombre          VARCHAR(100)         — "Juliaca"
ciudad          VARCHAR(100)
region          VARCHAR(100) NULL
activa          BOOLEAN default true
created_at / updated_at
```

### Tabla `users` (extendida de Laravel default)

```
id              BIGINT PK
name            VARCHAR(255)
email           VARCHAR(255) UNIQUE
password        VARCHAR(255)
sucursal_id     BIGINT FK → sucursales.id NULL  (null para admin)
activo          BOOLEAN default true
remember_token
email_verified_at
two_factor_secret / two_factor_recovery_codes / two_factor_confirmed_at  (Fortify)
created_at / updated_at
```

> Roles via Spatie: tabla `model_has_roles` — no campo `rol` directo en users.

### Tabla `invitaciones`

```
id              BIGINT PK
token           VARCHAR(64) UNIQUE   — UUID hex, se envía en el link
email           VARCHAR(255)
rol             ENUM(admin, jefe_resguardo, visor)
sucursal_id     BIGINT FK NULL       — requerido si rol = jefe_resguardo o visor
invitado_por    BIGINT FK → users.id
usado_en        TIMESTAMP NULL
expira_en       TIMESTAMP            — default: now + 7 días
created_at / updated_at
```

### Tabla `vehiculos`

```
id              BIGINT PK
sucursal_id     BIGINT FK → sucursales.id
creado_por      BIGINT FK → users.id

-- Identificación
placa           VARCHAR(20) UNIQUE
tipo            ENUM(moto, auto, camioneta, minivan, furgon, bus, vehiculo_pesado)
marca           VARCHAR(100)
modelo          VARCHAR(100)
anio            SMALLINT
color           VARCHAR(50) NULL

-- Datos SUNARP
num_motor       VARCHAR(50) NULL
num_chasis      VARCHAR(50) NULL
vin             VARCHAR(50) NULL
propietario     VARCHAR(200) NULL
ruc_propietario VARCHAR(11) NULL

-- Estado operativo
estado          ENUM(operativo, parcialmente, fuera_de_servicio) default operativo
problema_activo TEXT NULL

-- Técnicos
combustible     ENUM(gasolina, diesel, glp, gnv, electrico, hibrido)
transmision     ENUM(manual, automatico) NULL
traccion        ENUM(4x2, 4x4) NULL
km_actuales     INT NULL
capacidad_carga VARCHAR(50) NULL

-- Conductor asignado (referencia simple)
conductor_nombre VARCHAR(200) NULL
conductor_tel    VARCHAR(20) NULL

-- Administrativos
fecha_adquisicion DATE NULL
gps_id           VARCHAR(100) NULL
observaciones    TEXT NULL

deleted_at      TIMESTAMP NULL       — soft delete
created_at / updated_at
```

### Tabla `conductores`

```
id              BIGINT PK
sucursal_id     BIGINT FK → sucursales.id
vehiculo_id     BIGINT FK → vehiculos.id NULL

nombre_completo VARCHAR(200)
dni             VARCHAR(8) UNIQUE
telefono        VARCHAR(20) NULL
email           VARCHAR(255) NULL
foto_path       VARCHAR(500) NULL

-- Licencia
licencia_numero      VARCHAR(20) NULL
licencia_categoria   VARCHAR(10) NULL
licencia_vencimiento DATE NULL

activo          BOOLEAN default true
deleted_at      TIMESTAMP NULL
created_at / updated_at
```

### Tabla `registros_combustible` ⭐ (único módulo de escritura del jefe_resguardo)

Flujo en dos pasos: jefe_resguardo sube fotos → admin revisa y completa los datos.

```
id              BIGINT PK
vehiculo_id     BIGINT FK → vehiculos.id
sucursal_id     BIGINT FK → sucursales.id  — desnormalizado para filtrar rápido

-- PASO 1: jefe_resguardo sube las fotos
enviado_por     BIGINT FK → users.id
foto_factura_key  VARCHAR(500)             — key Wasabi: foto de la factura/voucher
foto_odometro_key VARCHAR(500)             — key Wasabi: foto del odómetro
observaciones_envio TEXT NULL              — nota opcional del jefe al enviar
estado          ENUM(pendiente, aprobado, rechazado) default pendiente

-- PASO 2: admin revisa las fotos y completa los datos
revisado_por    BIGINT FK → users.id NULL
revisado_en     TIMESTAMP NULL
fecha_carga     DATE NULL                  — fecha real de la carga (del voucher)
km_al_cargar    INT NULL                   — km leído del odómetro
galones         DECIMAL(8,3) NULL          — galones cargados (del voucher)
precio_galon    DECIMAL(6,3) NULL          — S/ por galón
monto_total     DECIMAL(10,2) NULL         — total pagado en soles
tipo_combustible ENUM(gasolina, diesel, glp, gnv, electrico, hibrido) NULL
proveedor       VARCHAR(200) NULL          — nombre del grifo/estación
numero_voucher  VARCHAR(100) NULL
observaciones_revision TEXT NULL           — nota del admin al aprobar/rechazar

created_at / updated_at
```

### Tabla `documentos_vehiculares`

```
id              BIGINT PK
vehiculo_id     BIGINT FK → vehiculos.id
subido_por      BIGINT FK → users.id

tipo            ENUM(soat, revision_tecnica, tarjeta_propiedad, otro)
nombre          VARCHAR(255)
archivo_key     VARCHAR(500)         — key en Wasabi
mime_type       VARCHAR(100)
tamano_bytes    INT
vencimiento     DATE NULL
observaciones   TEXT NULL

created_at / updated_at
```

### Tabla `mantenimientos`

```
id              BIGINT PK
vehiculo_id     BIGINT FK → vehiculos.id
registrado_por  BIGINT FK → users.id

categoria       ENUM(aceite_filtros, llantas, frenos, liquidos, bateria,
                      alineacion_balanceo, suspension, transmision,
                      electricidad, revision_general, otro)
tipo            ENUM(preventivo, correctivo)
descripcion     TEXT NULL
taller          VARCHAR(200) NULL
costo           DECIMAL(10,2) NULL

fecha_servicio  DATE
km_servicio     INT NULL

proximo_km      INT NULL
proxima_fecha   DATE NULL

observaciones   TEXT NULL
created_at / updated_at
```

### Tabla `fotos_vehiculos`

```
id              BIGINT PK
vehiculo_id     BIGINT FK → vehiculos.id
subido_por      BIGINT FK → users.id
key             VARCHAR(500)
categoria       ENUM(frontal, lateral_izq, lateral_der, trasera, interior, otra)
descripcion     VARCHAR(255) NULL
created_at / updated_at
```

### Tabla `actividad_log`

```
id              BIGINT PK
user_id         BIGINT FK → users.id
accion          VARCHAR(100)         — "vehiculo.crear", "combustible.registrar", etc.
entidad_tipo    VARCHAR(100)
entidad_id      BIGINT
detalle         JSON NULL
ip              VARCHAR(45) NULL
created_at
```

---

## 5. Módulos y pantallas

### 5.1 Autenticación (`/login`, `/registro`, `/recuperar`)
- Login con email + password
- Registro solo por invitación (token en URL)
- Recuperar contraseña vía email (Resend)
- 2FA opcional (Fortify TOTP — Fase 3)

### 5.2 Dashboard (`/dashboard`)
**KPIs principales:**
- Total flota / Operativos / Parciales / Fuera de servicio
- Combustible registrado este mes (litros + monto total)
- Documentos próximos a vencer (30 días)
- Mantenimientos urgentes (≤1,000 km o 30 días)
- Últimos 5 vehículos registrados
- Flota por sucursal — solo admin

**Filtros por rol:**
- `admin`: ve todo el sistema
- `jefe_resguardo` y `visor`: solo datos de su sucursal

### 5.3 Vehículos (`/vehiculos`)
**Acceso:** admin (todo), jefe_resguardo y visor (solo su sucursal — solo lectura)

**Listado:**
- Búsqueda: placa, marca, modelo, conductor
- Filtros: estado, sucursal (admin), tipo
- Toggle grid / tabla
- Badge de alerta de mantenimiento
- Paginación (15 por página)
- Botón exportar Excel (solo admin)
- Botón "Nuevo vehículo" (solo admin)

**Detalle (`/vehiculos/{id}`):**
- Header: placa, tipo, marca/modelo/año, propietario, badge estado
- Alertas activas si las hay
- Tabs: Información | Combustible | Documentos | Mantenimientos | Fotos
- Botones: Editar (admin), Eliminar (admin), Cartilla PDF (admin)
- Tab Combustible: visible para jefe_resguardo con botón "Registrar carga"

### 5.4 Combustible (`/combustible`)
**El único módulo de escritura del jefe_resguardo. Flujo de aprobación en 2 pasos.**

#### Paso 1 — jefe_resguardo envía el registro

**Formulario de envío (modal simple):**
- Seleccionar vehículo de su sucursal
- Subir **foto de la factura/voucher** (JPG/PNG/PDF ≤10MB) → Wasabi
- Subir **foto del odómetro** (JPG/PNG ≤10MB) → Wasabi
- Observaciones opcionales (ej: "carga de emergencia en ruta")
- Botón "Enviar para revisión"

**Estado del registro:** `pendiente` hasta que el admin actúe.

**Vista jefe_resguardo en `/combustible`:**
- Lista de sus envíos con estado: Pendiente / Aprobado / Rechazado
- Badge de color: ámbar (pendiente), verde (aprobado), rojo (rechazado)
- Al hacer clic: ver las fotos enviadas + nota del admin si fue rechazado

#### Paso 2 — admin revisa y completa los datos

**Vista admin en `/combustible`:**
- Tabla con todos los registros del sistema
- Filtros: sucursal, estado (pendiente/aprobado/rechazado), vehículo, fecha
- Badge de alerta: count de registros pendientes en sidebar
- Al abrir un registro pendiente → panel de revisión:
  - Fotos a pantalla completa (factura + odómetro) para leer los datos
  - Formulario que el admin llena manualmente:
    - Fecha de la carga
    - Km al cargar (leído del odómetro)
    - Galones cargados (leído de la factura)
    - Precio por galón / Monto total
    - Tipo de combustible, proveedor, número de voucher
    - Observaciones de revisión
  - Botones: **Aprobar** / **Rechazar** (con nota obligatoria si rechaza)

**Historial por vehículo (tab en detalle del vehículo):**
- Solo registros aprobados
- Totales: galones acumulados, monto gastado, km/galón promedio

**Exportación Excel (admin):**
- Registros aprobados con todos los campos
- Agrupable por sucursal y período

### 5.5 Conductores (`/conductores`) — solo admin
- CRUD completo
- Datos: nombre, DNI, licencia (categoría, vencimiento), teléfono
- Vehículo asignado actualmente
- Badge de licencia próxima a vencer (<30 días)

### 5.6 Documentos vehiculares (`/vehiculos/{id}/documentos`) — solo admin
- Lista por tipo: SOAT, Rev. Técnica, Tarjeta de Propiedad, Otro
- Badge vencimiento: verde / ámbar (<30 días) / rojo (vencido)
- Upload: PDF/JPG/PNG ≤10MB → Wasabi
- Descarga con signed URL (1h)
- Eliminar

### 5.7 Mantenimientos (`/vehiculos/{id}/mantenimientos`) — solo admin
- Lista cronológica de servicios
- Form nuevo: categoría, tipo, fecha, km, costo, taller, próximo programado
- Alertas inline por km/fecha vencida

### 5.8 Fotos (`/vehiculos/{id}/fotos`) — solo admin
- Galería por categoría
- Upload drag & drop → Wasabi
- Eliminar con confirmación

### 5.9 Alertas (`/alertas`)
Panel unificado:
- Documentos vencidos o próximos a vencer
- Mantenimientos vencidos o próximos
- Licencias de conductores próximas a vencer
- `admin`: todas las sucursales
- `jefe_resguardo` / `visor`: solo su sucursal
- Badge en sidebar con total de alertas activas

### 5.10 Admin — Usuarios (`/admin/usuarios`) — solo admin
- Tabla: nombre, email, rol, sucursal, estado
- Acciones: cambiar rol, reasignar sucursal, activar/desactivar

### 5.11 Admin — Invitaciones (`/admin/invitaciones`) — solo admin
- Crear invitación → envía email con link (Resend)
- Roles disponibles: admin, jefe_resguardo, visor
- Tabla con estado: Activo / Usado / Expirado
- Copiar link manualmente

### 5.12 Admin — Sucursales (`/admin/sucursales`) — solo admin
- CRUD: nombre, ciudad, región, activa/inactiva
- Las inactivas no aparecen en selects

---

## 6. Arquitectura de código

### Estructura de directorios

```
app/
├── Actions/
│   └── Fortify/                — CreateNewUser, UpdateProfile, etc.
├── Http/
│   └── Controllers/            — Solo auth y PDF/Excel exports
├── Livewire/
│   ├── Dashboard.php
│   ├── Vehiculos/
│   │   ├── Index.php
│   │   ├── Form.php
│   │   ├── Show.php
│   │   ├── Combustible.php     — listado + registro de carga
│   │   ├── Documentos.php
│   │   ├── Mantenimientos.php
│   │   └── Fotos.php
│   ├── Conductores/
│   │   ├── Index.php
│   │   └── Form.php
│   ├── Alertas.php
│   └── Admin/
│       ├── Usuarios.php
│       ├── Invitaciones.php
│       └── Sucursales.php
├── Models/
│   ├── User.php
│   ├── Sucursal.php
│   ├── Vehiculo.php
│   ├── Conductor.php
│   ├── RegistroCombustible.php
│   ├── DocumentoVehicular.php
│   ├── Mantenimiento.php
│   ├── FotoVehiculo.php
│   ├── Invitacion.php
│   └── ActividadLog.php
├── Policies/
│   ├── VehiculoPolicy.php
│   ├── ConductorPolicy.php
│   ├── RegistroCombustiblePolicy.php
│   └── DocumentoPolicy.php
├── Services/
│   ├── WasabiService.php       — Upload, signed URL, delete (bucket: selcosilaravel)
│   └── AlertasService.php      — Lógica compartida de alertas
└── Notifications/
    └── InvitacionNotification.php   — via Resend
```

### Scopes de modelo por rol

```php
// En Vehiculo::scopeForUser() — aplica automáticamente el filtro de sucursal
public function scopeForUser(Builder $query, User $user): Builder
{
    if ($user->hasRole('admin')) {
        return $query;
    }
    return $query->where('sucursal_id', $user->sucursal_id);
}
```

### Policy ejemplo — jefe_resguardo solo puede registrar combustible

```php
// VehiculoPolicy
public function view(User $user, Vehiculo $vehiculo): bool
{
    if ($user->hasRole('admin')) return true;
    return $user->sucursal_id === $vehiculo->sucursal_id;  // jefe_resguardo + visor
}

public function update(User $user, Vehiculo $vehiculo): bool
{
    return $user->hasRole('admin');  // solo admin edita vehículos
}

// RegistroCombustiblePolicy
public function enviar(User $user, Vehiculo $vehiculo): bool
{
    // jefe_resguardo puede enviar fotos de vehículos de su sucursal
    if ($user->hasRole('admin')) return true;
    if ($user->hasRole('jefe_resguardo')) {
        return $user->sucursal_id === $vehiculo->sucursal_id;
    }
    return false;  // visor no puede
}

public function revisar(User $user): bool
{
    return $user->hasRole('admin');  // solo admin aprueba/rechaza
}
```

---

## 7. Fases de desarrollo

### FASE 1 — Fundación
**Objetivo:** Sistema funcional con auth, roles y CRUD de vehículos.

- [x] 1.1 Configurar MySQL en Herd + `.env` + instalar Spatie Permission (v7.3)
- [x] 1.2 Migración: sucursales
- [x] 1.3 Migración: users extendido (sucursal_id, activo) + columnas 2FA ya existentes
- [x] 1.4 Migración: invitaciones
- [x] 1.5 Migraciones: vehiculos, conductores, fotos_vehiculos, documentos_vehiculares, mantenimientos, registros_combustible, actividad_log
- [x] 1.6 Models completos: User, Sucursal, Vehiculo, Conductor, Invitacion, FotoVehiculo, DocumentoVehicular, Mantenimiento, RegistroCombustible, ActividadLog — con relaciones, scopes (`forUser`, `search`, `activas`, etc.) y casts
- [x] 1.7 Seeders: 3 roles (admin/jefe_resguardo/visor) + 6 sucursales + admin inicial (`admin@selcosi.com`)
- [x] 1.8 VehiculoPolicy con permisos granulares por rol
- [x] 1.9 Código formateado con Pint
- [x] 1.10 Auth: login, logout, registro por invitación (Fortify)
- [x] 1.11 Layout principal: sidebar Flux con navegación por rol
- [x] 1.12 Módulo Sucursales CRUD (admin)
- [x] 1.13 Módulo Usuarios — lista + acciones (admin)
- [x] 1.14 Módulo Invitaciones — crear + email Resend (admin)
- [ ] 1.15 CRUD Vehículos completo — Index + Form + Show (admin)
- [ ] 1.16 Vista vehículos solo lectura para jefe_resguardo y visor

**Notas de implementación:**
- `$table` explícito en modelos con nombre español plural no estándar: `sucursales`, `conductores`, `invitaciones`
- FK `conductores.vehiculo_id` se agrega en migración separada (orden de ejecución)
- Spatie v7.3 — `Role::firstOrCreate(['name' => ..., 'guard_name' => 'web'])`
- PHP ejecutable Herd: `/c/Users/Yanl/.config/herd/bin/php84/php.exe`
- Composer Herd: `/c/Users/Yanl/.config/herd/bin/composer.phar`

### FASE 2 — Operaciones core
**Objetivo:** Módulo de combustible + documentación + alertas.

- [ ] 2.1 WasabiService (bucket: selcosilaravel)
- [ ] 2.2 Módulo Combustible — registro de cargas (jefe_resguardo + admin)
- [ ] 2.3 Módulo Combustible — historial por vehículo + listado global (admin)
- [ ] 2.4 Módulo Documentos vehiculares — upload + alertas (admin)
- [ ] 2.5 Módulo Mantenimientos — registro + alertas (admin)
- [ ] 2.6 Módulo Fotos — galería + upload (admin)
- [ ] 2.7 Módulo Conductores completo (admin)
- [ ] 2.8 Dashboard con KPIs reales + cache 5 min
- [ ] 2.9 Panel de Alertas unificado (filtrado por rol)
- [ ] 2.10 Badge de alertas en sidebar

### FASE 3 — Reportes y pulido
**Objetivo:** Exportaciones, actividad log y 2FA.

- [ ] 3.1 Cartilla vehicular PDF (DomPDF)
- [ ] 3.2 Exportación Excel flota + combustible por período
- [ ] 3.3 Actividad log
- [ ] 3.4 2FA con Fortify (TOTP)
- [ ] 3.5 Tests de feature con Pest (un test por módulo CRUD mínimo)

---

## 8. UI / UX — Decisiones de diseño

### Sidebar (Flux)
- Colapsable: modo expandido (icono + label) / compacto (solo icono)
- Secciones: **Principal** (Dashboard, Vehículos, Combustible, Alertas) / **Administración** (Conductores, Usuarios, Invitaciones, Sucursales)
- Sección Administración visible solo para `admin`
- Badge rojo con count de alertas activas
- Usuario + rol + sucursal al pie
- Dark mode automático desde preferencia del sistema

### Navegación por rol
| Ítem sidebar | admin | jefe_resguardo | visor |
|---|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ |
| Vehículos | ✅ | ✅ (solo lectura, su sucursal) | ✅ (solo lectura) |
| Combustible | ✅ (revisar + aprobar + ver todo) | ✅ (enviar fotos + ver sus envíos) | ✅ (ver aprobados su sucursal) |
| Alertas | ✅ | ✅ (su sucursal) | ✅ (su sucursal) |
| Conductores | ✅ | ❌ | ❌ |
| Usuarios | ✅ | ❌ | ❌ |
| Invitaciones | ✅ | ❌ | ❌ |
| Sucursales | ✅ | ❌ | ❌ |

### Paleta de colores
- **Primario**: Azul — corporativo
- **Operativo**: Verde esmeralda
- **Parcialmente operativo**: Ámbar
- **Fuera de servicio**: Rojo
- **Alertas docs/mantenimiento**: Ámbar (<30 días) / Rojo (vencido)

---

## 9. Variables de entorno

```env
APP_NAME="Selcosi Flota"
APP_URL=https://selcosiflota.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=selcosiflota
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_ENCRYPTION=tls
MAIL_USERNAME=resend
MAIL_PASSWORD=re_xxxxx
MAIL_FROM_ADDRESS="noreply@selcosi.com"
MAIL_FROM_NAME="Selcosi Flota"

WASABI_REGION=us-east-1
WASABI_ENDPOINT=https://s3.wasabisys.com
WASABI_ACCESS_KEY_ID=
WASABI_SECRET_ACCESS_KEY=
WASABI_BUCKET=selcosilaravel

QUEUE_CONNECTION=database
```

---

## 10. Convenciones de código

- **Migrations**: snake_case, plural (`vehiculos`, `registros_combustible`)
- **Models**: PascalCase, singular (`Vehiculo`, `RegistroCombustible`)
- **Livewire components**: namespace por módulo (`Vehiculos\Index`, `Vehiculos\Combustible`)
- **Views**: `livewire/vehiculos/combustible.blade.php`
- **Scopes en modelos**: `scopeForUser()`, `scopeSearch()`, `scopeActivos()`
- **Policies**: una por modelo principal
- **Sin API routes**: todo CRUD por Livewire. Controllers solo para PDF y Excel.
- **Formateo**: `vendor/bin/pint --dirty` antes de finalizar cambios
- **Tests**: Pest, mínimo un feature test por módulo CRUD

---

## 11. Comandos de desarrollo

```bash
composer run dev                        # Servidor + queue + vite en paralelo
php artisan migrate:fresh --seed        # Resetea BD con datos de prueba
php artisan test --compact              # Corre todos los tests
vendor/bin/pint --dirty                 # Formatea archivos modificados
php artisan queue:work                  # Procesa jobs (emails de invitación)
```
