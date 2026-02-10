
-- Tabla de proveedores
CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    ruc VARCHAR(20) UNIQUE NOT NULL,
    direccion TEXT,
    telefono VARCHAR(50),
    email VARCHAR(100),
    contacto VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías de servicios
CREATE TABLE IF NOT EXISTS categorias_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    color VARCHAR(7) DEFAULT '#72BF44',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de facturas
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    categoria_id INT,
    numero_factura VARCHAR(50) NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    monto_total DECIMAL(12,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'PEN',
    igv DECIMAL(12,2) DEFAULT 0,
    subtotal DECIMAL(12,2) DEFAULT 0,
    descripcion TEXT,
    archivo_pdf VARCHAR(255),
    estado ENUM('pendiente', 'pagada', 'vencida', 'anulada') DEFAULT 'pendiente',
    fecha_pago DATE NULL,
    metodo_pago VARCHAR(50) NULL,
    referencia_pago VARCHAR(100) NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias_servicios(id) ON DELETE SET NULL,
    INDEX idx_estado (estado),
    INDEX idx_fecha_vencimiento (fecha_vencimiento),
    INDEX idx_proveedor (proveedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de detalles de factura (líneas de items)
CREATE TABLE IF NOT EXISTS factura_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    cantidad DECIMAL(10,2) DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de pagos
CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50),
    referencia VARCHAR(100),
    observaciones TEXT,
    usuario VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar categorías predeterminadas
INSERT INTO categorias_servicios (nombre, descripcion, color) VALUES
('Internet y Telecomunicaciones', 'Servicios de internet, telefonía y comunicaciones', '#00BBE7'),
('Electricidad', 'Suministro eléctrico', '#FFDD00'),
('Agua y Saneamiento', 'Servicios de agua potable', '#72BF44'),
('Mantenimiento', 'Servicios de mantenimiento general', '#F99B1C'),
('Software y Licencias', 'Licencias de software y servicios cloud', '#6F605A'),
('Seguridad', 'Servicios de seguridad física y digital', '#2BB458'),
('Limpieza', 'Servicios de limpieza', '#72BE44'),
('Otros Servicios', 'Otros servicios varios', '#6F605A');

-- Insertar proveedores de ejemplo
INSERT INTO proveedores (nombre, ruc, direccion, telefono, email, contacto) VALUES
('Luz del Sur S.A.A.', '20331898008', 'Av. Canaval y Moreyra 380, San Isidro', '01-6175000', 'contacto@luzdelsur.com.pe', 'Atención al Cliente'),
('Telefónica del Perú S.A.A.', '20100017491', 'Av. Arequipa 1155, Lima', '01-1000', 'empresas@telefonica.com.pe', 'Empresas'),
('Sedapal', '20100152356', 'Av. Benavides 1180, Miraflores', '01-3175000', 'contacto@sedapal.com.pe', 'Atención Empresas');
