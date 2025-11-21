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

try {
    // *** Validación para verificar si el token existe o esta vacio***
    if (!isset($_GET["token"])  or empty($_GET["token"])) {
        // Token no proporcionado
        header ("location: " . BASE_URL_FRONTEND . "/resend_token?status=missing_token"); 
        exit;
    }

    $token = htmlspecialchars(trim($_GET["token"]));
    $nombre_tabla = "email_verifications"; // Nombre de la tabla para las verificaciones de email

    // Obtener el ID del usuario asociado al token, validando el token si existe y está activo
    $user_id = get_user_current_id($conn, $token, $nombre_tabla);

    if ($user_id === 0) {
        // Token no válido o no encontrado
        header ("location: " . BASE_URL_FRONTEND . "/resend_token?status=invalid_token"); 
        exit;
    }

    // Actualizaemos el campo de verificación de correo en la tabla de usuarios actualizando el valor a true (verificado)
    update_user_field($conn, $user_id,  "correo_verificado", true);

    if (update_user_field === false) {
        throw new Exception("Error al actualizar el estado de verificación de correo para el usuario con ID: " . $user_id);
    }

    // 3. Eliminar el token de verificación de email
    delete_record_by_field($conn, $nombre_tabla, "token", $token);
    // El borrado puede fallar sin ser un error crítico, así que no detenemos la ejecución.

    // 4. Éxito: Redirigir al frontend con la señal de éxito
    header ("location: " . BASE_URL_FRONTEND . "/login?status=verified");
    exit;

} catch (InvalidArgumentException $e) {
    // Manejo de errores de argumentos inválidos
    error_log("InvalidArgumentException in verify_email.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine());
    header("location: " . BASE_URL_FRONTEND . "/login?status=invalid_argument");
    exit;

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    $error_message = "Database error in verify_email.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() . 
                     " | Trace: " . $e->getTraceAsString();
    error_log($error_message);
    header("location: " . BASE_URL_FRONTEND . "/login?status=db_error");
    exit;

} catch (Exception $e) {
    // Manejo de errores generales
    $error_message = "Unexpected error in verify_email.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() .
                     " | Trace: " . $e->getTraceAsString();
    error_log($error_message);
    header("location: " . BASE_URL_FRONTEND . "/login?status=error");
    exit;

} finally {
    // Cerrar la conexión a la base de datos
    $conn = null;
}
?>