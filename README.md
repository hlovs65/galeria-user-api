# galeria-user-api
API RESTful de Autenticación y Usuarios (PHP). Gestiona el registro, inicio de sesión (Login/Logout), verificación de email, y el control de acceso mediante tokens JWT. Implementa Rate Limiting para la seguridad.

# 🛡️ Galería - API de Autenticación y Usuarios (galeria-user-api)

Este repositorio aloja el servicio de backend (construido con PHP) dedicado a la **gestión de usuarios, autenticación y seguridad** de la aplicación Galería de Imágenes.

Se adhiere a una arquitectura de microservicios, proporcionando un punto final (`user-api`) separado del servicio principal de gestión de imágenes (`galeria-api-server`).

## 🔑 Funcionalidades Clave

* **Registro y Activación de Cuenta:** Manejo del flujo de registro de nuevos usuarios y verificación de email mediante tokens temporales.
* **Inicio de Sesión y Sesiones:** Procesa el inicio y cierre de sesión (`login.php` y `logout.php`).
* **Seguridad y Acceso:** Generación y validación de tokens **JSON Web Token (JWT)** para autenticar las solicitudes del frontend y gestionar los permisos (roles).
* **Rate Limiting:** Implementa un control de límite de tasa por IP para prevenir ataques de fuerza bruta en los endpoints críticos (`login.php`).
* **Configuración Segura:** Utiliza variables de entorno (`.env`) y la librería `vlucas/phpdotenv` para manejar de forma segura las credenciales de la Base de Datos y SMTP.

## 🛠️ Tecnologías Usadas

* **Lenguaje:** PHP 8.x
* **Base de Datos:** MySQL/MariaDB (vía MySQLi)
* **Gestor de Dependencias:** Composer
* **Librerías Clave:** `firebase/php-jwt` (para tokens), `vlucas/phpdotenv` (para entorno).
