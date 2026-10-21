# Directrices de Desarrollo - Plataforma de Reservas (res-vaed)

Actúa como un arquitecto y desarrollador senior full-stack en Laravel. Esta aplicación es una plataforma multiempresa para la gestión y reserva de instalaciones deportivas (pistas de pádel, tenis, fútbol, etc.).

---

## 1. Stack Técnico
- **Framework:** Laravel 12.x
- **PHP:** 8.2 (estricta compatibilidad con PHP 8.2.29 en producción). No sugieras sintaxis exclusiva de PHP 8.3+.
- **Frontend / UI:** Tailwind CSS, Alpine.js y componentes Blade de Laravel Jetstream.
- **Base de Datos:** MySQL.
- **Autorización:** Spatie Laravel Permission (`spatie/laravel-permission`).

---

## 2. Arquitectura de Dominio y Multi-Empresa

La plataforma da servicio a múltiples empresas o clubes independientes:
- Cada instalación deportiva pertenece a una entidad **Company** (o `Tenant`).
- Todo recurso de gestión (pistas, tarifas, franjas horarias y bloqueos) debe tener ámbito (`scope`) por `company_id`.
- Un usuario administrador gestiona exclusivamente los datos de su propia empresa.

---

## 3. Roles y Ámbitos de Acceso (Spatie)

1. **`super-admin` (Plataforma):** Control total global, alta y mantenimiento de empresas/clientes de software.
2. **`company-admin` (Administrador de la instalación):**
   - Acceso al panel de administración (`/admin` o `/dashboard`).
   - Mantenimiento completo: creación de pistas, definición de horarios de apertura, precios, tarifas por tramo horario y bloqueos técnicos.
   - Consulta del calendario global de reservas de su instalación y gestión de cancelaciones manuales.
3. **`customer` (Usuario público / Jugador):**
   - Acceso desde la web pública y panel privado del cliente (`/perfil`, `/mis-reservas`).
   - Registro con Jetstream, búsqueda de disponibilidad por empresa/fecha/deporte y reserva de franjas horarias.

---

## 4. Entidades y Modelos Principales

- **`Company`:** Datos del club/empresa (nombre, slug, CIF, teléfono, horarios generales, estado).
- **`User`:**
  - Implementa `HasRoles`.
  - Puede tener `company_id` asignado si es staff o administrador de una empresa concreta.
- **`Court` (Pista):**
  - `company_id`, nombre (ej. "Pista 1"), tipo de deporte (`padel`, `tenis`), características (cubierta, cristal, muro), estado (`active`, `maintenance`).
- **`TimeSlot` / `Schedule` (Franja / Horario):**
  - Configuración de intervalos (ej. turnos de 60 o 90 minutos), días de la semana y precios por franja.
- **`Booking` (Reserva):**
  - `company_id`, `court_id`, `user_id`, `date`, `start_time`, `end_time`, `price`, `status` (`pending`, `confirmed`, `cancelled`).
- **`CourtBlock` (Bloqueos):**
  - Cierres temporales por mantenimiento, torneos o días festivos que inhabilitan la reserva.

---

## 5. Reglas de Negocio Esenciales

1. **Prevención de Solapamientos (Overbooking):**
   - Una pista no puede reservarse dos veces en el mismo intervalo de tiempo. Cualquier inserción de reserva debe validar previamente la no existencia de solapamiento en `Booking` y `CourtBlock`.
2. **Aislamiento Multiempresa:**
   - En las consultas del panel de administración, aplicar siempre un Global Scope o filtrar por `where('company_id', auth()->user()->company_id)`.
3. **Flujo de Navegación según Rol:**
   - Al autenticarse, si el usuario tiene el rol `company-admin` o `super-admin`, redirigir al panel de administración. Si es `customer`, redirigir al catálogo de pistas o a su historial de reservas.
4. **Validación:**
   - La lógica de disponibilidad y cálculo de precio no va en el controlador; se gestiona mediante Services (`BookingService`, `AvailabilityService`) o Actions dedicadas.
   - Formularios siempre validados con Form Requests (`App\Http\Requests\...`).