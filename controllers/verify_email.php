<?php

// verify_email.php- Controlador (archivo para activar la cuenta de usuarios)

// ----------------------------------------------------
// PASO CLAVE: INCLUSIÓN DE COMPOSER Y CONFIGURACIÓN
// ----------------------------------------------------
require_once '../vendor/autoload.php'; // Incluir Composer autoload si es necesario
// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require_once '../includes/config.php'; // Incluir el archivo de configuración

// ----------------------------------------------------
// INCLUSIONES DE UTILIDADES
// ----------------------------------------------------
require_once '../includes/functions.php'; // Incluir las funciones de manejo de mensajes flash
require_once '../includes/db_connection.php'; // Incluir el archivo de conexión a la base de datos

// *** Validación para verificar si el token existe o esta vacio***
if (!isset($_GET["token"])  or empty($_GET["token"])) {
    if ($conn && $conn->ping()) {$conn->close();}
    // Token no proporcionado
    header ("location: " . BASE_URL_FRONTEND . "/resend_token?status=missing_token"); 
    exit;
    //send_json_error("Token no válido. Solicita uno nuevo o cancela.", 400);
}

$token = htmlspecialchars(trim($_GET["token"]));
$nombre_tabla = "email_verifications"; // Nombre de la tabla para las verificaciones de email

// Obtener el ID del usuario asociado al token, validando el token si existe y está activo
$user_id = get_user_current_id($conn, $token, $nombre_tabla);
if (!$user_id) {
    if ($conn && $conn->ping()) {$conn->close();}
    // Token no válido o no encontrado
    header ("location: " . BASE_URL_FRONTEND . "/resend_token?status=invalid_token"); 
    exit;
}

// Actualizaemos el campo de verificación de correo en la tabla de usuarios actualizando el valor a 1 (verificado)
if (update_user_field($conn, $user_id,  "correo_verificado", 1)) {

    // 3. Eliminar el token de verificación de email
    delete_record_by_field($conn, $nombre_tabla, "token", $token);
    // El borrado puede fallar sin ser un error crítico, así que no detenemos la ejecución.

    if ($conn && $conn->ping()) {$conn->close();}
    // 4. Éxito: Redirigir al frontend con la señal de éxito
    header ("location: " . BASE_URL_FRONTEND . "/login?status=verified");
    exit;
} else {
    // Fallo al actualizar base de datos
    if ($conn && $conn->ping()) {$conn->close();}
    header("location: " . BASE_URL_FRONTEND . "/login?status=update_error");
    exit;
}
?>

