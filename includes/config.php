<?php
// config_init.php
// Este archivo maneja la carga de variables de entorno y las constantes globales.

require __DIR__ . '/../vendor/autoload.php'; // Asegura que Dotenv esté cargado

use Dotenv\Dotenv;

$rootPath = __DIR__ . '/..';
$is_development = file_exists($rootPath . '/.env.local');

if ($is_development) {
    // 1. Entorno de Desarrollo (Local: .env.local existe)
    // Usamos createMutable para asegurar que las variables se establezcan, 
    // y solo le pasamos el archivo que existe.
    $dotenv = Dotenv::createMutable($rootPath, '.env.local');
    $dotenv->safeLoad(); // Carga .env.local si existe

}

// ===========================================
// Configuración de la base de datos
// ===========================================
define('DB_SERVER', getenv('DB_SERVER') ?: $_ENV['DB_SERVER']);
define('DB_USERNAME', getenv('DB_USERNAME') ?: $_ENV['DB_USERNAME']);
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: $_ENV['DB_PASSWORD']);
define('DB_NAME', getenv('DB_NAME') ?: $_ENV['DB_NAME']);
define('DB_PORT', getenv('DB_PORT') ?: $_ENV['DB_PORT']);
// ===========================================
// Configuración de la URL base
// ===========================================
define('BASE_URL', getenv('BASE_URL') ?: $_ENV['BASE_URL']);

// ===========================================
// Configuración de la Clave Secreta para JWT
// ===========================================
define('JWT_SECRET_KEY', getenv('JWT_SECRET_KEY') ?: $_ENV['JWT_SECRET_KEY']);

// ===========================================
// CONSTANTES DE CONFIGURACIÓN DE CORREO (SMTP)
// ===========================================

// Servidor SMTP (Gmail, Outlook, o tu proveedor)
define('SMTP_HOST', getenv('SMTP_HOST') ?: $_ENV['SMTP_HOST']);
// Puerto SMTP (587 para TLS o 465 para SSL)
define('SMTP_PORT', getenv('SMTP_PORT') ?: $_ENV['SMTP_PORT']);

// Nombre de usuario de la cuenta de correo
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: $_ENV['SMTP_USERNAME']); 

// Contraseña de la cuenta de correo (USA CONTRASEÑA DE APLICACIÓN para Gmail)
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: $_ENV['SMTP_PASSWORD']); 

// Tipo de seguridad (tls o ssl)
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: $_ENV['SMTP_SECURE']);

// Correo que aparecerá como remitente
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: $_ENV['MAIL_FROM_EMAIL']); 

// Nombre que aparecerá como remitente
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: $_ENV['MAIL_FROM_NAME']);
// ===========================================
// URL base acceso frontend despues de envio de email
// ===========================================
define('BASE_URL_FRONTEND', getenv('BASE_URL_FRONTEND') ?: $_ENV['BASE_URL_FRONTEND']);
// Define una constante auxiliar para la URL de la API local.
// SI NO ES DESARROLLO (producción), usa la URL de Producción (BASE_URL).
// Esto hace que la URL para enlaces sea consistente en producción.
if ($is_development) {
    define('BASE_URL_PARA_ENLACES_INTERNOS', getenv('BASE_URL_API_LOCAL') ?: $_ENV['BASE_URL_API_LOCAL']);
} else {
    // En producción, el enlace interno es la propia BASE_URL de la API
    define('BASE_URL_PARA_ENLACES_INTERNOS', BASE_URL); 
}