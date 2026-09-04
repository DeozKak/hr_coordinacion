# hr_coordinacion

ERP interno de operaciones de campo para una contratista de servicios públicos
en Colombia. Usuarios internos (coordinadores, residentes, inspectores,
supervisores), un solo cliente, sin multi-tenancy. Laravel 12 / PHP 8.2,
monolito Blade + Alpine, MySQL. Colas y caché sobre base de datos.

Módulos: coordinación de órdenes, programación de técnicos, producción y
cortes, bitácoras de inspector, PQRS, zonificación, nómina, stickers, SST.

## Reglas que rompen cosas si se ignoran

**Las migraciones ya son normales otra vez.** Hubo 88 migraciones del volcado
de esquema (`create_*`, `add_foreign_keys_*`) versionadas pero sin registrar, y
por eso todo se corría con `--path`. Se saldó: `php artisan migrate` funciona a
secas. Si te topas con una base donde el desfase sigue —producción, un dump
viejo, un entorno nuevo—, el comando que lo arregla está en el repo y sin
`--aplicar` sólo informa:

```bash
php artisan migraciones:marcar-aplicadas            # simula
php artisan migraciones:marcar-aplicadas --aplicar  # registra
```

Sólo marca migraciones cuyo objeto existe de verdad en la base (comprueba la
tabla con `hasTable`, y las claves ajenas contra `information_schema`); lo que
no puede verificar lo deja pendiente para que `migrate` lo ejecute.


**Un archivo de rutas nuevo no se carga solo.** No se usa el descubrimiento por
convención: cada archivo de `routes/<modulo>/` está listado a mano en el array
`web:` de `bootstrap/app.php`. Si creas uno y no lo registras ahí, las rutas
simplemente no existen y no hay error que lo diga.

**`CheckPermission` es un OR, no un AND.** `CheckPermission::class.':a,b'` deja
pasar a quien tenga *a* **o** *b* (ver `app/Http/Middleware/CheckPermission.php`).
Es intencional; no lo "arregles" a AND sin preguntar, hay rutas que dependen de
ese comportamiento.

## Convenciones

**Idioma.** El código —clases, métodos, variables, comentarios, mensajes de
usuario— va en español. Los *nombres de archivo* de rutas están en inglés
(`routes/payroll/`, `routes/scheduling/`, `routes/zoning/`). Es una mezcla
deliberada ya establecida: respeta cada lado donde está, no unifiques.

**Modelos: PascalCase, sin excepciones.** Los 48 modelos siguen `TblProgramacionBase`,
`Asignadas`, `TblInspCali`. Antes convivían con snake_case (`tbl_programacion_base`);
se unificaron y no queda ninguno.

**Al renombrar un modelo, cuidado con la tabla.** 22 modelos no declaran `$table` y
dejan que Eloquent la derive del nombre de la clase con
`Str::snake(Str::pluralStudly($clase))`. Cambiar el nombre de la clase puede cambiar
la tabla en silencio y sin error hasta que la consulta falla en producción.
Compruébalo antes, no lo supongas:

```bash
docker compose exec -T app php -r 'require "/var/www/html/vendor/autoload.php";
use Illuminate\Support\Str; echo Str::snake(Str::pluralStudly("TuModelo"));'
```

Y ojo con el reemplazo masivo: varios nombres de clase son idénticos a su nombre de
tabla (`asignadas`, `tbl_insp_cali`, `tbl_programacion_base`), que aparece además en
SQL crudo, `DB::table()`, reglas `unique:`/`exists:` y migraciones. Un
buscar-y-reemplazar por palabra corrompe esas cadenas.

**Lógica de negocio: en `app/Services/`, no en el controlador.** Es el patrón al
que está migrando el proyecto y ya cubre Home, Programación y PQRS. Un
controlador nuevo debería quedarse en recibir, delegar y responder. Los
controladores gordos que quedan (`CoordinacionController`, ~4.000 líneas) son
deuda conocida, no el modelo a imitar.

**Vistas.** Cada pantalla se parte en tres: `<vista>.blade.php` con el marcado,
`partials/<vista>-script.blade.php` con el JS y `partials/<vista>-modales.blade.php`
con los modales. Mantén ese corte al añadir pantallas.

**Comentarios.** Los que hay explican el *porqué* de una decisión, no lo que
hace la línea siguiente (buenos ejemplos en `routes/web.php` y en el manejador
de `InvalidSignatureException` de `bootstrap/app.php`). Si un comentario sólo
traduce el código a prosa, sobra.

## Desarrollo

```bash
docker compose up -d       # app :8080, phpMyAdmin :8081, MySQL :33060
npm run dev                # Vite en watch
php artisan test
```

Assets con Vite; el layout carga `@vite(['resources/css/app.css', 'resources/js/tw.js'])`.
Conviven Bootstrap 5 y Tailwind 4 (`layouts/app.blade.php` vs `layouts/tw/`) —
mira qué usa la pantalla en la que trabajas antes de elegir clases.

## Despliegue (Hostinger compartido, CloudLinux)

`/home/u377880665/domains/segoperacioneyc.site/public_html`. `public/build` está
en `.gitignore`, así que **`git pull` no trae los assets y sin
`public/build/manifest.json` todas las vistas dan 500**. Node existe pero no
está en el PATH:

```bash
export PATH="/opt/alt/alt-nodejs20/root/usr/bin:$PATH"
RAYON_NUM_THREADS=1 npm ci && RAYON_NUM_THREADS=1 npm run build
```

`RAYON_NUM_THREADS=1` no es opcional: el motor Rust de Tailwind v4 abre un hilo
por núcleo y el límite de procesos de CloudLinux lo mata con `EAGAIN`
(*The global thread pool has not been initialized*), un error que no menciona
la cuota y hace perder tiempo buscando en el sitio equivocado. `npm ci` sin
`--omit=dev`: Vite y Tailwind están en `devDependencies`.

Despliegue completo = `git pull` → build con las dos variables → `migrate`
→ `config:clear` + `route:clear` + `view:clear`.

## Integraciones

- **WhatsApp** (waapi + Meta) para notificaciones; su webhook está exento de CSRF.
- **OpenAI** sólo en `app/Services/ExtraerFechas.php`, para sacar fechas de
  texto libre del call center. Único punto de IA del proyecto.
- **Base externa de movilidad** por una segunda conexión MySQL (`DB_*_MOVILIDAD`).
- `laravel-auditing` y `activitylog` registran cambios: no desactives sus traits
  en modelos, hay pantallas de trazabilidad que leen esas tablas.
- Comandos programados en `routes/console.php` (stickers, zonificación,
  asignación de técnicos, limpieza de bitácora diaria, sync de tareas).

## Deuda conocida

No hace falta que la señales en cada sesión; está identificada y priorizada:

1. **Sin tests.** `tests/` sólo tiene los ejemplos de Laravel y dos `Tmp*`.
   Cualquier refactor grande va a ciegas.
2. **Validación floja.** Cero FormRequests y ~12 `validate()` en 33
   controladores. Al tocar un endpoint que reciba datos, aprovecha y valídalo.
3. `CoordinacionController` a 4.000 líneas, con Mpdf/PhpSpreadsheet/ZipArchive
   dentro.
4. Archivos de script Blade de 900 líneas, sin linting ni bundling.
5. 187 usos de `DB::` crudo conviviendo con Eloquent.
