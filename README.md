# Sistema Web de Control de Asistencia

Sistema web desarrollado para el Instituto de Educación Superior "Víctor Raúl Haya de la Torre" (IES VRHT) que permite gestionar y controlar la asistencia de los estudiantes y docentes de los diferentes programas de estudio.

## Características Principales

- **Gestión de Usuarios y Roles:** Autenticación segura con distintos niveles de acceso (Administrador, Docente, Estudiante).
- **Control de Asistencia:** Interfaz rápida y responsiva para que los docentes registren la asistencia por sesión.
- **Regla del 30%:** Cálculo automatizado que clasifica a los estudiantes según su porcentaje de inasistencias (Activo, En Riesgo, Inhabilitado).
- **Importación Masiva:** Carga de estudiantes, docentes y horarios mediante archivos CSV/Excel.
- **Auditoría:** Registro de trazabilidad y log de cambios sobre las modificaciones de estado de asistencia.
- **Dashboard e Indicadores:** Resumen estadístico del estado académico de los programas de estudio en tiempo real.

## Stack Tecnológico

- **Backend:** PHP puro
- **Base de Datos:** MySQL
- **Frontend:** HTML, CSS, Tailwind CSS (via CDN) y JavaScript (Vanilla)
- **Iconos:** Lucide Icons

## Instalación y Configuración (Entorno Local)

1. Clonar el repositorio en el directorio raíz de su servidor local (ej. `htdocs` en XAMPP o `www` en Laragon).
2. Crear una base de datos en MySQL llamada `control_asistencia`.
3. Importar el archivo SQL ubicado en `database/schema.sql` a la base de datos recién creada. *(Nota: Este archivo ya incluye la estructura completa y datos de prueba reales para los 3 programas de estudio).*
4. Acceder a la plataforma desde su navegador local (ej. `http://localhost/CONTROL_DE_ASISTENCIA/public`).

### Usuarios de Prueba (Demo)
Para probar las funcionalidades de los diferentes roles, puede acceder con las siguientes credenciales:
- **Administrador:** Usuario: `admin` | Contraseña: `admin`
- **Docente:** Usuario: `docente` | Contraseña: `docente`
- **Estudiante:** Usuario: `estudiante` | Contraseña: `estudiante`

## Equipo Desarrollador

- **Jefe de Proyecto:** Zapata Guardia, Rildo Benjamin
- **Analista de Sistemas:** Trejo Támara, Sugey Milagros
- **Diseñador de Base de Datos (DBA):** Gonzáles Ríos, César André
- **Programador:** Wong Bustamante, Martin Samuel
- **Tester / QA:** Urrieta Milla, Dinora Jimena

## Licencia
Proyecto desarrollado con fines académicos para el IES "Víctor Raúl Haya de la Torre".
