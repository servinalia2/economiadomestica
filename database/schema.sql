CREATE DATABASE IF NOT EXISTS economia_domestica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE economia_domestica;

CREATE TABLE IF NOT EXISTS categorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL UNIQUE,
  tipo_default ENUM('ingreso','gasto','ambos') NOT NULL DEFAULT 'gasto',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subcategorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_subcategoria (categoria_id, nombre),
  CONSTRAINT fk_subcategorias_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cuentas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  banco VARCHAR(120) NULL,
  descripcion TEXT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  concepto_banco VARCHAR(255) NOT NULL,
  mas_datos TEXT NULL,
  importe DECIMAL(12,2) NOT NULL,
  saldo DECIMAL(12,2) NULL,
  tipo ENUM('ingreso','gasto') NOT NULL,
  categoria_id INT UNSIGNED NULL,
  subcategoria_id INT UNSIGNED NULL,
  origen ENUM('excel','manual') NOT NULL DEFAULT 'manual',
  cuenta_bancaria VARCHAR(120) NULL,
  pendiente_revision TINYINT(1) NOT NULL DEFAULT 1,
  hash_movimiento CHAR(64) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_hash_movimiento (hash_movimiento),
  KEY idx_fecha (fecha),
  KEY idx_tipo (tipo),
  KEY idx_pendiente (pendiente_revision),
  CONSTRAINT fk_movimientos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
  CONSTRAINT fk_movimientos_subcategoria FOREIGN KEY (subcategoria_id) REFERENCES subcategorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reglas_clasificacion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  palabra_clave VARCHAR(120) NOT NULL,
  categoria_id INT UNSIGNED NOT NULL,
  subcategoria_id INT UNSIGNED NOT NULL,
  tipo ENUM('ingreso','gasto','ambos') NOT NULL DEFAULT 'gasto',
  prioridad INT NOT NULL DEFAULT 10,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_regla_activa (activa, prioridad),
  CONSTRAINT fk_reglas_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
  CONSTRAINT fk_reglas_subcategoria FOREIGN KEY (subcategoria_id) REFERENCES subcategorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO cuentas (id, nombre, banco, descripcion) VALUES (1, 'Cuenta principal', 'Banco', 'Cuenta familiar inicial');

INSERT IGNORE INTO categorias (nombre, tipo_default) VALUES
('ALIMENTACION', 'gasto'),
('SUMINISTROS', 'gasto'),
('COCHES', 'gasto'),
('OTROS GASTOS', 'gasto'),
('INGRESOS', 'ingreso');

INSERT IGNORE INTO subcategorias (categoria_id, nombre)
SELECT c.id, x.nombre FROM categorias c JOIN (
  SELECT 'ALIMENTACION' categoria, 'MERCADONA' nombre UNION ALL
  SELECT 'ALIMENTACION', 'LIDL' UNION ALL
  SELECT 'SUMINISTROS', 'ENDESA' UNION ALL
  SELECT 'SUMINISTROS', 'IBERDROLA' UNION ALL
  SELECT 'COCHES', 'COMBUSTIBLE' UNION ALL
  SELECT 'OTROS GASTOS', 'CAJERO' UNION ALL
  SELECT 'INGRESOS', 'NOMINA'
) x ON x.categoria = c.nombre;

INSERT IGNORE INTO reglas_clasificacion (palabra_clave, categoria_id, subcategoria_id, tipo, prioridad, activa)
SELECT s.nombre, c.id, s.id, CASE WHEN c.nombre = 'INGRESOS' THEN 'ingreso' ELSE 'gasto' END, 10, 1
FROM subcategorias s JOIN categorias c ON c.id = s.categoria_id
WHERE s.nombre IN ('MERCADONA','LIDL','ENDESA','IBERDROLA','COMBUSTIBLE','CAJERO','NOMINA');
