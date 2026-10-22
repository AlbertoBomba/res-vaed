<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Usuarios iniciales

Para crear los dos usuarios de pruebas, ejecuta:

```sh
php artisan db:seed --class=UserSeeder
```

Tambien se incluye en `php artisan db:seed`. Las migraciones deben estar aplicadas.

| Usuario | Email | Contrasena | Rol |
| --- | --- | --- | --- |
| Administrador de instalaciones | admin@example.com | password | company-admin |
| Cliente de la web publica | cliente@example.com | password | customer |

Las contrasenas genericas estan definidas en el seeder exclusivamente para pruebas.
No utilices estas cuentas con estas credenciales en produccion.

Las contrasenas se almacenan mediante el cast `hashed` de Laravel. El seeder se
puede repetir sin duplicar usuarios ni roles, sin restablecer contrasenas o datos
personales existentes y sin eliminar otros roles. Los usuarios nuevos se crean
con el correo verificado.

El administrador no es un `super-admin`. `UserSeeder` llama a `ClubCompanySeeder`
para asociar al administrador de pruebas con `Club VAED` si no tiene empresa.
No modifica una empresa ya asignada ni vincula al cliente con ninguna empresa.

## Paneles privados

La interfaz privada utiliza una barra lateral morada, tarjetas y navegacion
responsive. `/dashboard` redirige segun el rol:

- `company-admin` y `super-admin`: `/admin`, panel de gestion.
- Clientes y cuentas sin rol: `/mi-cuenta`, area personal.

Ambos paneles requieren autenticacion. Los clientes reciben un 403 si intentan
abrir `/admin`. El perfil, la contrasena, el doble factor y la gestion de sesiones
siguen utilizando los componentes de Jetstream en `/user/profile`.

El mantenimiento de instalaciones y las reservas publicas estan disponibles.
No hay datos ni estadisticas simuladas.
Para compilar el diseño: `npm run build`. Al desplegar, subir tambien
`public/build` y regenerar las caches de configuracion, rutas y vistas.

## Reservas publicas

La portada `/` permite filtrar por club, deporte, fecha y duracion (horas
completas, conforme al horario semanal configurado). Solo publica pistas activas
y no archivadas; oculta franjas pasadas, ocupadas o bloqueadas. Los horarios
utilizan la zona horaria de la aplicacion (`config/app.php`).

Al elegir un horario, los visitantes van al registro con opcion de iniciar
sesion. Ambos flujos conservan la seleccion y vuelven a la confirmacion, sin
crear reservas automaticamente. Las nuevas cuentas reciben el rol `customer`.
Confirmar y consultar `/mis-reservas` requiere autenticacion; cada usuario solo
ve sus propias reservas, aunque reserve en distintos clubes.

`BookingService` bloquea la fila de la pista dentro de una transaccion, vuelve
a comprobar disponibilidad y precio, y guarda una reserva confirmada con la
empresa de la pista y el usuario autenticado. Ignora empresas, usuarios, estados
e importes adicionales enviados por el navegador. Si cambia la tarifa desde
la confirmacion, exige revisar el nuevo importe.

La tarifa diaria existente se aplica a las horas acumuladas del mismo jugador,
pista y fecha. `price_cents` guarda el ajuste respecto a reservas anteriores,
incluidos abonos negativos, que se muestran explicitamente. No hay pasarela
de pago ni cobro online; confirmar solo registra la reserva y su importe.
Las reservas pendientes y confirmadas ocupan horario; las canceladas no.
La gestion administrativa de reservas y bloqueos no forma parte de esta pantalla.
Al eliminar una cuenta con Jetstream se conservan las reservas sin vinculo al
usuario; la eliminacion de la cuenta no cancela automaticamente sus horarios.
Los futuros escritores de `court_blocks` deben adquirir el mismo bloqueo de
la pista para serializarse con las reservas.

Antes de desplegar la portada, ejecutar `php artisan migrate --force` para crear
`bookings` y `court_blocks`, compilar con `npm run build` y subir `public/build`.
Pruebas aisladas: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php vendor/bin/phpunit
tests/Feature/PublicBookingTest.php` (en PowerShell, establecer estas variables
mediante `$env:`).

## Mantenimiento de pistas (Livewire)

En `/admin/pistas` se pueden buscar y filtrar pistas, crearlas, editarlas,
archivarlas y recuperarlas. El archivo no elimina sus horarios, tarifas ni imagen.
El administrador de empresa solo consulta y modifica sus propias pistas, incluidas
las acciones Livewire. El superadministrador ve todas las empresas y selecciona
la propietaria al crear; editar una pista no permite cambiarla de empresa.
Una cuenta administradora sin empresa puede ver un aviso, pero no crear pistas.

Cada pista tiene nombre, deporte, tipo (exterior, cubierta o semicubierta),
superficie, cerramiento, aforo, iluminacion, acceso adaptado, descripcion y estado.
Los catalogos se encuentran en `config/courts.php`. Imagen opcional JPG, PNG o
WebP de hasta 5 MB y 6000 x 6000 px, almacenada en el disco publico. Al sustituirla
o retirarla se elimina la anterior; los fallos de limpieza se registran y notifican.

### Horario semanal y precios

- Cada dia contiene 24 franjas completas: 00:00-01:00 hasta 23:00-24:00.
- Solo las marcadas son alquilables. Se puede marcar/limpiar un dia y copiarlo a
  la semana. Una pista activa requiere al menos una franja.
- Tres precios finales por hora en EUR: 1 hora, 2 horas y 3 o mas horas por dia.
  Se permiten tarifas gratuitas (0); maximo 9999,99 EUR/h y dos decimales.
- Se acumulan las horas del **mismo cliente, pista y fecha**, aunque no sean
  consecutivas. La tarifa del tramo alcanzado se aplica a todas las horas del dia.
  Con 20/18/16 EUR/h, los totales son 20, 36, 48 y 64 EUR para 1/2/3/4 horas.
- `CourtPricingService` trabaja en centimos. `quote` devuelve horas, tarifa
  unitaria, total diario y ajuste respecto a lo ya cobrado. Si el cliente ya pago
  20 EUR y añade una segunda hora, el ajuste es 16 EUR. Un ajuste negativo es
  saldo a devolver, nunca se oculta.
- El simulador del formulario no consulta reservas ni cobra: valora las horas
  anteriores con las tarifas del formulario. El futuro servicio de reservas
  debera agrupar solo reservas vigentes del cliente/pista/fecha, descontar importes
  reales ya cobrados y aplicar disponibilidad, bloqueos, concurrencia y reembolsos.
  Estas operaciones no forman parte de este mantenimiento.

Las altas y cambios de horario son transaccionales. Un contador de version evita
sobrescribir ediciones concurrentes: si aparece el aviso de conflicto, recarga el
formulario antes de volver a editar.

### Despliegue

Con los archivos subidos y antes de habilitar las rutas nuevas:

```sh
php artisan migrate --force
php artisan db:seed --class=ClubCompanySeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

El seeder de empresa requiere el administrador de pruebas existente. No ejecuta
un reseteo ni cambia contrasenas. El servidor web debe poder escribir en
`storage` y leer `public/storage`. No subir `public/hot`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
