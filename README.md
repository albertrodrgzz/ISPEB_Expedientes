# Sistema de Gestión de Expedientes Digitales - ISPEB

Sistema completo de gestión de expedientes para la Dirección de Telemática del ISPEB.

## 🚀 Instalación Rápida (phpMyAdmin)

### Paso 1: Crear la Base de Datos

1. Abre **phpMyAdmin** en tu navegador: `http://localhost/phpmyadmin`
2. Haz clic en la pestaña **"Nueva"** o **"Bases de datos"**
3. Nombre de la base de datos: `ispeb_expedientes`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Haz clic en **"Crear"**

### Paso 2: Importar el Schema SQL

1. Selecciona la base de datos `ispeb_expedientes` en el panel izquierdo
2. Haz clic en la pestaña **"Importar"**
3. Haz clic en **"Seleccionar archivo"**
4. Navega a: `C:\xampp\htdocs\APP3\database\schema.sql`
5. Haz clic en **"Continuar"** o **"Ejecutar"**
6. Deberías ver el mensaje: **"Importación finalizada correctamente"**

### Paso 3: Verificar la Instalación

1. En phpMyAdmin, selecciona `ispeb_expedientes`
2. Deberías ver **8 tablas** creadas:
   - ✅ `auditoria`
   - ✅ `cargos`
   - ✅ `departamentos`
   - ✅ `expedientes_docs`
   - ✅ `funcionarios`
   - ✅ `movimientos`
   - ✅ `sesiones`
   - ✅ `usuarios`

3. Haz clic en la tabla **`cargos`** → Deberías ver **6 registros**
4. Haz clic en la tabla **`departamentos`** → Deberías ver **5 registros**
5. Haz clic en la tabla **`usuarios`** → Deberías ver **1 usuario admin**

### Paso 4: Configurar la Conexión (Opcional)

Si tu MySQL tiene contraseña, edita el archivo `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ispeb_expedientes');
define('DB_USER', 'root');
define('DB_PASS', ''); // ← Cambia aquí si tienes contraseña
```

### Paso 5: Acceder al Sistema

1. Abre tu navegador
2. Visita: `http://localhost/APP3/`
3. Usa las credenciales por defecto:
   - **Usuario:** `admin`
   - **Contraseña:** `admin123`

---

## 📋 Datos Semilla Incluidos

### 6 Cargos con Niveles de Acceso

| Cargo | Nivel | Permisos |
|-------|-------|----------|
| Director de la Dirección | 1 | Acceso total |
| Jefe de Dirección | 1 | Acceso total |
| Jefe de Departamento | 2 | Operativo (solo su departamento) |
| Secretaria | 2 | Operativo (todos los departamentos) |
| Asistente | 3 | Solo lectura |
| Técnico | 3 | Solo lectura |

### 5 Departamentos de Telemática

1. Soporte Técnico
2. Sistemas
3. Redes y Telecomunicaciones
4. Atención al Usuario
5. Reparaciones Electrónicas

### Usuario Administrador

- **Usuario:** admin
- **Contraseña:** admin123
- **Cargo:** Director de la Dirección
- **Nivel:** 1 (Acceso total)

---

## 🎨 Características del Sistema

### ✅ Implementado

- ✅ Sistema de login con validación de roles
- ✅ Dashboard con KPIs (Total personal, Activos, De vacaciones, Alertas)
- ✅ Diseño Clean & Minimal con paleta ISPEB
- ✅ Control de acceso por niveles (1, 2, 3)
- ✅ Auditoría automática de acciones
- ✅ Protección contra fuerza bruta (bloqueo tras 5 intentos)
- ✅ Base de datos completa con relaciones

### 🔜 Pendiente de Implementar

- ⏳ Módulo de gestión de funcionarios (CRUD)
- ⏳ Sistema de tabs en expediente digital
- ⏳ Carga de documentos (nombramientos, vacaciones, etc.)
- ⏳ Generación de reportes PDF con membrete ISPEB
- ⏳ Exportación a Excel
- ⏳ Respaldo y restauración de base de datos
- ⏳ Recuperación de contraseña

---

## 🔒 Seguridad

- **Contraseñas:** Hasheadas con `bcrypt` (PASSWORD_DEFAULT)
- **SQL Injection:** Protegido con PDO Prepared Statements
- **Sesiones:** Validación en cada página
- **Archivos:** Protección con `.htaccess`
- **Auditoría:** Registro de todas las acciones

---

## 📁 Estructura del Proyecto

```
APP3/
├── config/              # Configuraciones
├── controladores/       # Lógica de negocio
├── modelos/             # Modelos de datos
├── vistas/              # Interfaz de usuario
│   ├── dashboard/       # Dashboard principal
│   ├── funcionarios/    # Gestión de funcionarios
│   └── layout/          # Componentes reutilizables
├── publico/             # CSS, JS, imágenes
├── subidas/             # Archivos subidos (protegido)
├── database/            # Scripts SQL
└── index.php            # Punto de entrada (LOGIN)
```

---

## 🛠️ Tecnologías

- **Backend:** PHP 8+ (POO, MVC, PDO)
- **Base de Datos:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript Vanilla
- **Tipografía:** Inter (Google Fonts)
- **Diseño:** Clean & Minimal

---

## 📞 Soporte

Para más información, revisa la documentación completa en:
- [`estructura_carpetas.md`](file:///C:/Users/alber/.gemini/antigravity/brain/827f38bd-3d2b-4a9a-85a1-915ea8c47c75/estructura_carpetas.md)
- [`walkthrough.md`](file:///C:/Users/alber/.gemini/antigravity/brain/827f38bd-3d2b-4a9a-85a1-915ea8c47c75/walkthrough.md)

---

**© 2026 ISPEB - Dirección de Telemática**
