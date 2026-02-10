# Guía de Instalación Rápida en Plesk VPS

## 📦 Paso 1: Subir archivos al servidor

### Opción A: Usando el Administrador de Archivos de Plesk
1. Acceder a Plesk Panel
2. Ir a "Archivos" → "Administrador de archivos"
3. Navegar a la carpeta raíz del dominio (normalmente `httpdocs` o `public_html`)
4. Subir todos los archivos del sistema

### Opción B: Usando FTP
```bash
# Comprimir el proyecto localmente
zip -r sistema-facturas.zip sistema-facturas/

# Subir vía FTP a tu VPS
# Luego descomprimir en el servidor
```

## 🗄️ Paso 2: Crear la base de datos en Plesk

1. En Plesk, ir a "Bases de datos"
2. Hacer clic en "Añadir base de datos"
3. Configurar:
   - **Nombre:** `sistema_facturas`
   - **Usuario:** crear un nuevo usuario
   - **Contraseña:** generar contraseña segura
4. Guardar las credenciales

## 📊 Paso 3: Importar la estructura de la base de datos

### Opción A: Usando phpMyAdmin en Plesk
1. Ir a "Bases de datos" → hacer clic en "phpMyAdmin"
2. Seleccionar la base de datos `sistema_facturas`
3. Ir a la pestaña "Importar"
4. Seleccionar el archivo `database.sql`
5. Hacer clic en "Continuar"

### Opción B: Usando línea de comandos (SSH)
```bash
mysql -u usuario -p sistema_facturas < database.sql
```

## ⚙️ Paso 4: Configurar la conexión a la base de datos

1. Editar el archivo `config/database.php`
2. Actualizar las credenciales:

```php
private $host = "localhost";
private $db_name = "sistema_facturas";
private $username = "tu_usuario_plesk";
private $password = "tu_contraseña_plesk";
```

## 🔒 Paso 5: Configurar permisos de carpetas

En el Administrador de Archivos de Plesk:

1. Navegar a `assets/uploads`
2. Hacer clic derecho → "Cambiar permisos"
3. Establecer permisos a **777** (rwxrwxrwx)
4. Repetir para `assets/uploads/facturas`

Si tienes acceso SSH:
```bash
cd /var/www/vhosts/tu-dominio.com/httpdocs
chmod -R 777 assets/uploads
```

## 📚 Paso 6: (Opcional) Instalar librería PDF

Si tu VPS tiene acceso SSH y Composer:

```bash
cd /var/www/vhosts/tu-dominio.com/httpdocs
composer install
```

Si no tienes Composer, el sistema funcionará pero sin extracción automática de datos de PDFs.

## 🌐 Paso 7: Configurar dominio/subdominio

### Opción A: Dominio principal
El sistema se accederá desde: `http://tu-dominio.com`

### Opción B: Subdominio
1. En Plesk, ir a "Subdominios"
2. Crear subdominio: `facturas.tu-dominio.com`
3. Configurar la raíz del documento a la carpeta del sistema
4. Acceder: `http://facturas.tu-dominio.com`

## 🔐 Paso 8: Configurar SSL (Recomendado)

1. En Plesk, ir a "SSL/TLS Certificates"
2. Seleccionar "Install a free basic certificate provided by Let's Encrypt"
3. Activar para tu dominio
4. Ahora accederás vía: `https://tu-dominio.com`

## ✅ Paso 9: Verificar instalación

1. Abrir navegador y acceder a tu dominio
2. Deberías ver el Dashboard del sistema
3. Verificar que puedes:
   - Ver el dashboard
   - Agregar un proveedor
   - Crear una factura

## 🚨 Solución de Problemas Comunes

### Error de conexión a base de datos
- Verificar credenciales en `config/database.php`
- Asegurarse de que el usuario tiene permisos en la BD

### No se pueden subir archivos
- Verificar permisos de carpeta `assets/uploads` (debe ser 777)
- Verificar configuración PHP en Plesk:
  - `upload_max_filesize = 10M`
  - `post_max_size = 10M`

### Páginas en blanco
- Activar mostrar errores PHP temporalmente
- Revisar logs de error de Apache/PHP en Plesk
- En `config/database.php` agregar:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

### No se ven estilos CSS
- Verificar que el CDN de Tailwind CSS esté cargando
- Revisar firewall/configuración de red

## 📞 Contacto de Soporte

Para asistencia técnica, contactar al Área de TI.

---

**Tiempo estimado de instalación:** 15-30 minutos

¡Sistema listo para usar! 🎉
