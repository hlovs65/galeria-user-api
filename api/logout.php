<?php
// logout.php - Cierra la sesión del usuario y actualiza su estado en la base de datos

// ----------------------------------------------------
// 1. Configuración Inicial y CORS (Necesario para React)
// ----------------------------------------------------
require_once __DIR__ . '/../config/cors_setup.php';

// ----------------------------------------------------
// PASO 3: Incluir las funciones de mensajes JWT. Conexion a la base de datos y otras utilidades.
// ----------------------------------------------------
require_once '../vendor/autoload.php'; // Incluir Composer autoload si es necesario
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require_once '../includes/config.php'; // Incluir la configuración (base de datos, JWT_SECRET_KEY, etc.)

// ----------------------------------------------------
// INCLUSIONES DE UTILIDADES
// ----------------------------------------------------
require_once '../includes/functions.php'; // Asegúrate de que este path sea correcto para tu proyecto
require_once '../includes/jwt_utils.php'; // Incluir las funciones de JWT
require_once '../includes/db_connection.php'; // Incluir el archivo de conexión a la base de datos


try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    

        // ----------------------------------------------------
        // PASO 4: Obtener el token de la cabecera Authorization
        // ----------------------------------------------------
        $user_id = get_user_id_from_token();

        if (!$user_id) {
        // Token inválido o no proporcionado
            send_json_error("Sesion invalida o expirada. Ya estas desconectado", 401);
        }   

        // ----------------------------------------------------
        // PASO 5: Actualizar el estado del usuario a false (desconectado) en la base de datos
        // ----------------------------------------------------
        $userid_int = (int)$user_id;
        $nombre_columna = 'estado';
        $valor_columna = false; // valor booleano para desconectado
        $update_success = update_user_field($conn, $userid_int, $nombre_columna, $valor_columna);

        if (!$update_success) {
            error_log("Error al actualizar el estado del usuario con ID $user_id a desconectado.");
            send_json_error("Error interno del servidor al desconectar el usuario.", 500);
        }

        // ----------------------------------------------------
        // PASO 6: Responder con éxito
        // ----------------------------------------------------
            send_json_success("Usuario desconectado exitosamente.", [], 200);

    } else {
        // Método no permitido
        send_json_error("Método no permitido", 405);
    }
    
} catch (InvalidArgumentException $e) {
        // Actualizacion de estado fallida
        error_log("Validacion error en logout: Argumento inválido. " . $e->getMessage());
        send_json_error("Solicitud inválida. Verifique sus datos.", 400);
} catch (SignatureInvalidException $e) {
        // Token con firma inválida
        error_log("JWT Error en logout: Firma inválida. " . $e->getMessage());
        send_json_error("Token inválido. Sesión inválida o expirada.", 401);

} catch (BeforeValidException $e) {
        // Token usado antes de ser válido
        error_log("JWT Error en logout: Token no válido aún. " . $e->getMessage());
        send_json_error("Token no válido aún. Sesión inválida o expirada.", 401);

} catch (ExpiredException $e) {
        // Token expirado
        error_log("JWT Error en logout: Token expirado. " . $e->getMessage());
        send_json_error("Token expirado. Sesión inválida o expirada.", 401);

} catch (PDOException $e) {
        $error_message = "Error de BD (PDO) en logout: " . $e->getMessage() . 
                     " | SQLSTATE: " . $e->getCode() . 
                     " | trace: \n " . $e->getTraceAsString();
        error_log($error_message);
        send_json_error("Error de servidor: Por favor , intentalo más tarde.", 500);

} catch (Exception $e) {
        error_log("Error inesperado en logout: " . $e->getMessage());
        send_json_error("Ocurrio error interno al desconectar.", 500);

} finally {
        if (isset($conn)) { $conn = null; } // Cerrar la conexión a la base de datos
}
?>