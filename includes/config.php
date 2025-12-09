<?php
// config_init.php
// Este archivo maneja la carga de variables de entorno y las constantes globales.

require __DIR__ . '/../vendor/autoload.php'; // Asegura que Dotenv esté cargado

use Dotenv\Dotenv;

// Lógica de Carga de Variables (El patrón de sobreescritura que discutimos)
$dotenv = Dotenv::createImmutable(__DIR__ . '/..'); // Apunta a la raiz del proyecto

// 1. Cargar .env.local para desarrollo (si existe, anula valores de produccion)
if (file_exists(__DIR__ . '/../.env.local')) {
    $dotenv->load(); 
}

// 2. Cargar .env principal y variables de sistema (inyecciones de Render)
$dotenv->safeLoad(); // Carga las variables desde el archivo .env si existe

// ===========================================
// Configuración de la base de datos
// ===========================================
define('DB_SERVER', getenv('DB_SERVER'));
define('DB_USERNAME', getenv('DB_USERNAME'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_PORT', getenv('DB_PORT'));

// ===========================================
// Configuración de la URL base
// ===========================================
define('BASE_URL', getenv('BASE_URL'));

// ===========================================
// Configuración de la Clave Secreta para JWT
// ===========================================
define('JWT_SECRET_KEY', getenv('JWT_SECRET_KEY'));

// ===========================================
// CONSTANTES DE CONFIGURACIÓN DE CORREO (SMTP)
// ===========================================

// Servidor SMTP (Gmail, Outlook, o tu proveedor)
define('SMTP_HOST', getenv('SMTP_HOST')); 
// Puerto SMTP (587 para TLS o 465 para SSL)
define('SMTP_PORT', getenv('SMTP_PORT')); 

// Nombre de usuario de la cuenta de correo
define('SMTP_USERNAME', getenv('SMTP_USERNAME')); 

// Contraseña de la cuenta de correo (USA CONTRASEÑA DE APLICACIÓN para Gmail)
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD')); 

// Tipo de seguridad (tls o ssl)
define('SMTP_SECURE', getenv('SMTP_SECURE'));

// Correo que aparecerá como remitente
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL')); 

// Nombre que aparecerá como remitente
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME'));
// ===========================================
// URL base acceso frontend despues de envio de email
// ===========================================
define('BASE_URL_FRONTEND', getenv('BASE_URL_FRONTEND'));