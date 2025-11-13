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
            throw new Exception("Error al actualizar el estado del usuario en la base de datos.");
        }
        // ----------------------------------------------------
        // PASO 6: Responder con éxito
        // ----------------------------------------------------
            send_json_success("Usuario desconectado exitosamente.", [], 200);
    } else {
        // Método no permitido
        send_json_error("Método no permitido", 405);
    }
} catch (PDOException $e) {
        error_log("EXCEPCIÓN CRÍTICA DE BD EN LOGOUT (PDO): " . $e->getMessage());
        send_json_error("Error de servidor: Fallo crítico de base de datos.", 500);

    } catch (Exception $e) {
        error_log("EXCEPCIÓN CRÍTICA EN LOGOUT: " . $e->getMessage());
        send_json_error("Error de servidor: Fallo crítico.", 500);

} finally {
        if (isset($conn)) { $conn = null; } // Cerrar la conexión a la base de datos
}
?>