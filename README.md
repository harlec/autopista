# Sistema de Gestión de Facturas - Área de TI

Sistema modular para la administración de facturas y pagos de servicios, desarrollado específicamente para el área de TI de concesionarias de peajes.

## 🚀 Características

- ✅ **Carga y procesamiento de PDFs** - Extracción automática de datos de facturas
- 📊 **Dashboard con métricas** - Visualización de estadísticas en tiempo real
- 🏢 **Gestión de proveedores** - Control completo de proveedores y servicios
- 💰 **Control de pagos** - Seguimiento de facturas pendientes, pagadas y vencidas
- 🔍 **Búsqueda y filtros** - Sistema de búsqueda avanzada y filtros múltiples
- 📱 **Diseño responsivo** - Interfaz adaptable con Tailwind CSS
- 🎨 **Colores corporativos** - Utiliza los colores de Aleatica/Aunor Perú

## 🛠️ Tecnologías

- **Backend:** PHP 7.4+
- **Base de datos:** MySQL 5.7+
- **Frontend:** Tailwind CSS 3.x
- **Iconos:** Font Awesome 6.x
- **Procesamiento PDF:** smalot/pdfparser

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Composer (opcional, para extracción automática de PDFs)
- VPS con Plesk o cualquier hosting con PHP/MySQL

## 🔧 Instalación

### 1. Configurar la base de datos

```bash
# Acceder a MySQL
mysql -u root -p

# Ejecutar el script SQL
mysql -u root -p < database.sql
```

### 2. Configurar la conexión a la base de datos

Editar el archivo `config/database.php`:

```php
private $host = "localhost";
private $db_name = "sistema_facturas";
private $username = "tu_usuario";
private $password = "tu_contraseña";
```

### 3. Configurar permisos de carpetas

```bash
chmod 777 assets/uploads
chmod 777 assets/uploads/facturas
```

### 4. (Opcional) Instalar librería para extracción de PDFs

```bash
cd /ruta/del/proyecto
composer require smalot/pdfparser
```

Si no instalas Composer, el sistema funcionará igualmente, pero deberás ingresar los datos de las facturas manualmente.

### 5. Acceder al sistema

Abrir en el navegador:
```
http://tu-dominio.com/
```

## 📁 Estructura del Proyecto

```
sistema-facturas/
├── config/
│   └── database.php          # Configuración de base de datos
├── includes/
│   ├── header.php            # Header común
│   ├── footer.php            # Footer común
│   └── functions.php         # Funciones auxiliares
├── modules/
│   ├── facturas/
│   │   ├── lista.php         # Lista de facturas
│   │   ├── nueva.php         # Crear nueva factura
│   │   ├── procesar_pdf.php  # Procesar PDF cargado
│   │   └── guardar_factura.php
│   └── proveedores/
│       ├── lista.php         # Lista de proveedores
│       └── guardar_proveedor.php
├── assets/
│   ├── uploads/              # Archivos cargados
│   ├── css/                  # Estilos personalizados
│   └── js/                   # Scripts JavaScript
├── index.php                 # Dashboard principal
├── database.sql              # Script de base de datos
└── README.md                 # Este archivo
```

## 🎨 Colores Corporativos

El sistema utiliza los siguientes colores de la marca:

- **Verde principal:** `#72BF44` / `#2BB458`
- **Amarillo:** `#FFDD00`
- **Naranja:** `#F99B1C`
- **Azul:** `#00BBE7`
- **Gris:** `#6F605A`

## 💡 Uso Básico

### Registrar una nueva factura

1. Ir a "Nueva Factura" en el menú lateral
2. Cargar el PDF de la factura (opcional)
3. Si cargaste un PDF, hacer clic en "Extraer Datos del PDF"
4. Verificar y completar los datos
5. Guardar la factura

### Gestionar proveedores

1. Ir a "Proveedores" en el menú
2. Hacer clic en "Nuevo Proveedor"
3. Completar los datos del proveedor
4. Guardar

### Marcar una factura como pagada

1. En la lista de facturas, hacer clic en el icono de check verde
2. Ingresar la fecha de pago
3. Confirmar

## 🔄 Próximos Módulos

Este sistema está diseñado para crecer. Próximos módulos planificados:

- 📧 **Notificaciones por email** - Alertas de vencimiento
- 📈 **Reportes y gráficos** - Análisis de gastos
- 👥 **Gestión de usuarios** - Control de accesos
- 🔔 **Recordatorios automáticos** - Sistema de alertas
- 📱 **API REST** - Integración con otros sistemas
- 🗂️ **Gestión de contratos** - Control de contratos de servicios

## 🤝 Soporte

Para soporte técnico o consultas:
- Área de TI - Concesionaria de Peajes

## 📄 Licencia

Sistema desarrollado para uso interno de la organización.

---

**Versión:** 1.0.0  
**Desarrollado para:** Área de TI - Aleatica/Aunor Perú  
**Fecha:** Febrero 2026
