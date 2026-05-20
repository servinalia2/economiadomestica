# Economía Doméstica

Primera versión funcional de una aplicación web sencilla para controlar ingresos y gastos familiares. Está creada con PHP, MySQL, Bootstrap 5 y una estructura mínima fácil de subir a un hosting compartido.

## Funcionalidades incluidas

- Acceso básico con usuario y contraseña configurables.
- Dashboard mensual con ingresos, gastos, balance, resumen por categorías y últimos movimientos.
- CRUD de categorías y subcategorías.
- CRUD de movimientos manuales.
- Importador de movimientos desde CSV y Excel (`.xls`/`.xlsx` si se instala PhpSpreadsheet).
- Clasificación automática por reglas de palabras clave.
- Pantalla de movimientos pendientes con opción de crear nuevas reglas.
- Evita duplicados mediante `hash_movimiento`.
- Archivos de ejemplo en `samples/`:
  - `ejemplo_movimientos_banco.csv`
  - `ejemplo_categorias.csv`

## Requisitos

- PHP 8.1 o superior.
- MySQL 5.7/8 o MariaDB compatible.
- Extensiones PHP: `pdo_mysql`, `mbstring`.
- Composer para instalar PhpSpreadsheet si quieres importar Excel real.

## Instalación local

1. Crea la base de datos y tablas:

   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. Instala dependencias:

   ```bash
   composer install
   ```

   Si todavía no instalas dependencias, el importador acepta CSV igualmente. Para Excel `.xls`/`.xlsx` necesitas `composer install`.

3. Configura conexión mediante variables de entorno o crea `config/local.php`:

   ```php
   <?php
   return [
       'db' => [
           'host' => '127.0.0.1',
           'database' => 'economia_domestica',
           'username' => 'root',
           'password' => '',
       ],
       'auth' => [
           'username' => 'admin',
           'password' => 'cambia-esta-clave',
       ],
   ];
   ```

4. Arranca un servidor local:

   ```bash
   php -S localhost:8000 -t public
   ```

5. Abre `http://localhost:8000`.

## Credenciales iniciales

- Usuario: `admin`
- Contraseña: `admin123`

Cámbialas en variables de entorno (`APP_USERNAME`, `APP_PASSWORD`) o en `config/local.php` antes de usar la aplicación con datos reales.

## Formato de importación

El archivo debe incluir estas columnas, separadas por `;` o `,` si es CSV:

```text
FECHA | FECHA VALOR | MOVIMIENTO | MÁS DATOS | IMPORTE | SALDO
```

Columnas mínimas obligatorias: `FECHA`, `MOVIMIENTO`, `IMPORTE`.

- `IMPORTE` positivo: ingreso.
- `IMPORTE` negativo: gasto.
- `MOVIMIENTO` se compara con reglas activas de clasificación.
- Si no hay regla, el movimiento queda pendiente de revisión.

## Subida a hosting PHP

1. Sube todos los archivos del proyecto.
2. Configura el document root del dominio o subdominio apuntando a `public/`.
3. Ejecuta `database/schema.sql` en el panel MySQL del hosting.
4. Ejecuta `composer install --no-dev` en el servidor o sube también la carpeta `vendor/` generada localmente.
5. Crea `config/local.php` con los datos MySQL y credenciales de acceso.
6. Asegura permisos de escritura para `storage/uploads/`.

## Nota de alcance

Esta es solo la primera versión funcional. No incluye mejoras avanzadas como usuarios múltiples, gráficas complejas, conciliación de efectivo, perfiles de bancos, APIs bancarias ni contabilidad empresarial.
