<?php
// ===========================================
// Configuración de la Carga de Variables de Entorno
// ===========================================
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Apunta a la raíz del proyecto (user-api)
$dotenv->safeLoad(); // Carga las variables desde el archivo .env si existe

// ===========================================
// Configuración de la base de datos
// ===========================================
define('DB_SERVER', $_ENV['DB_SERVER']);
define('DB_USERNAME', $_ENV['DB_USERNAME']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
define('DB_NAME', $_ENV['DB_NAME']);

// ===========================================
// Configuración de la URL base
// ===========================================
define('BASE_URL', $_ENV['BASE_URL']);

// ===========================================
// Configuración de la Clave Secreta para JWT
// ===========================================
define('JWT_SECRET_KEY', $_ENV['JWT_SECRET_KEY']);

// ===========================================
// CONSTANTES DE CONFIGURACIÓN DE CORREO (SMTP)
// ===========================================

// Servidor SMTP (Gmail, Outlook, o tu proveedor)
define('SMTP_HOST', $_ENV['SMTP_HOST']); 

// Puerto SMTP (587 para TLS o 465 para SSL)
define('SMTP_PORT', $_ENV['SMTP_PORT']); 

// Nombre de usuario de la cuenta de correo
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME']); 

// Contraseña de la cuenta de correo (USA CONTRASEÑA DE APLICACIÓN para Gmail)
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD']); 

// Correo que aparecerá como remitente
define('MAIL_FROM_EMAIL', $_ENV['MAIL_FROM_EMAIL']); 

// Nombre que aparecerá como remitente
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME']);
// ===========================================
// URL base acceso frontend despues de envio de email
// ===========================================
define('BASE_URL_FRONTEND', $_ENV['BASE_URL_FRONTEND']);
// Esto hará que MySQLi lance una excepción (Error) cuando un query falle.
// Esto es mucho mejor que depender de 'if ($stmt === false)'
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);