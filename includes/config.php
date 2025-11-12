<?php
// ===========================================
// Configuración de la Carga de Variables de Entorno
// ===========================================
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Apunta a la raíz del proyecto (user-api)
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

// Correo que aparecerá como remitente
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL')); 

// Nombre que aparecerá como remitente
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME'));
// ===========================================
// URL base acceso frontend despues de envio de email
// ===========================================
define('BASE_URL_FRONTEND', getenv('BASE_URL_FRONTEND'));