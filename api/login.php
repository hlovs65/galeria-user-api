<?php

// login.php - API REST para manejar el inicio de sesión de usuarios

ob_start(); // Iniciar el almacenamiento en búfer de salida

// ----------------------------------------------------
// Configuración Inicial y CORS (Necesario para React)
// ----------------------------------------------------
require_once __DIR__ . '/../config/cors_setup.php';


// ----------------------------------------------------
// PASO CLAVE: INCLUSIÓN DE COMPOSER Y CONFIGURACIÓN
// ----------------------------------------------------
require_once '../vendor/autoload.php'; // Incluir Composer autoload si es necesario
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once '../includes/config.php'; // Incluir la configuración (base de datos, JWT_SECRET_KEY, etc.)

// ----------------------------------------------------
// INCLUSIONES DE UTILIDADES
// ----------------------------------------------------
require_once '../includes/functions.php'; // Incluir las funciones de manejo de errores con json
require_once '../includes/jwt_utils.php'; // Incluir las funciones de JWT
require_once '../includes/db_connection.php'; // Incluir el archivo de conexión a la base de datos
require_once __DIR__ . '/../utils/RateLimiter.php'; // Incluir el RATE LIMITER

// VERIFICAR EL LÍMITE DE TASA AL INICIO
if (is_rate_limited()) {
    // Si la función retorna true, ya ha enviado la respuesta 429 y ha detenido la ejecución con exit().
    // Si retorna false, la ejecución continúa.
}


// ----------------------------------------------------
// PROCESAMIENTO DE PETICIÓN
// ----------------------------------------------------

try {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // 1. Leer el cuerpo crudo de la solicitud
        $json_data = file_get_contents('php://input');

        // 2. Decodificar el JSON en un objeto o array asociativo de PHP
        // true para que sea un array asociativo
        $data = json_decode($json_data, true); 

        // 3. Verificar si la decodificación fue exitosa y si los campos existen
        if ($data === null) {
        // Fallo en la decodificación JSON o cuerpo vacío
        send_json_error(400, "Formato JSON inválido.");
        }



        // 4. Recibir los datos del  formulario y que no estén vacíos
        $errores    = []; // Array para almacenar errores
        $usuario    = []; // Array para almacenar datos del usuario
        $identificador = htmlspecialchars(trim($data['username-or-email'] ?? '')); // Usamos htmlspecialchars para evitar XSS
        $contraseña = trim($data['password'] ?? ''); // Usamos el operador de fusión de null para evitar errores si no se envía el campo

    
        // Validar que los campos no estén vacíos
        if (empty($identificador) || empty($contraseña)) {
            $errores[] = "El nombre de usuario o correo electrónico y la contraseña son obligatorios.";
        }

        // 5. Si hay errores iniciales, los mostramos
        // Si hay errores de validación o credenciales incorrectas:
        if (!empty($errores)) {
            send_json_error(implode($errores, " "), 400);
        }
    
        // 6. Buscar el usuario en la base de datos por nombre de usuario o correo electrónico
        $columna_to_select = "*"; // Seleccionar todas las columnas necesarias
        $condicion = [ 'username' => $identificador, 'email' => $identificador ]; // Condición para buscar por usuario o email

        $usuario = get_user_data_by_conditions($conn, $columna_to_select, $condicion);
    
        // 7. Verificar si se encontró el usuario
        if ($usuario === null) {

            send_json_error("Error de servicio. Intentelo más tarde.", 500); // Enviar error 500 si hay un error en la consulta

        } else if (empty($usuario) || $usuario['id'] === 0) {

            send_json_error("Usuario no registrado, Por favor usar formulario para registrarse!", 404);

        } else if ($usuario['id'] > 0) {
            // Si encontramos un usuario, verificamos la contraseña         
            if (password_verify($contraseña, $usuario['password'])) {
                // Contraseña correcta, iniciamos sesión

                // Actualizar el estado del usuario a true (conectado) en la base de datos
                $current_db_status = $usuario['estado'];
                $current_db_correo_verificado = $usuario['correo_verificado'];

               // Solo actualizamos si el usuario estaba desconectado
                if ( $current_db_status === false && $current_db_correo_verificado === true) { // Solo actualizamos si el usuario estaba desconectado
                    // Preparar los valores para la actualización
                    $nombre_columna = 'estado';
                    $valor_columna = true;

                   $update_success = update_user_field($conn, $usuario['id'], $nombre_columna, $valor_columna); // Actualizar el estado del usuario a true (conectado)
                    if (!$update_success) {
                        throw new Exception("Error: No se pudo actualizar el estado del usuario.");
                    }
                } else if ($current_db_correo_verificado === false) {
                    // Si el correo no está verificado, no permitimos el inicio de sesión
                    // Definir datos de la respuesta 403
                    $response_data = [
                        "status" => "error",
                        "message" => "Tu correo electrónico no ha sido verificado. Por favor, solicita un nuevo enlace de activacion.",
                        "action" => "RESEND_VERIFICATION_EMAIL_NEEDED",
                        "email " => $usuario['email']
                    ];
                    // Enviar respuesta JSON con código 403 personalizada
                    send_json_response($response_data, 403);
                }

                // generar JWT y enviar respuesta de éxito
                $jwt = generate_jwt($usuario);

                // ENVIAR RESPUESTA DE ÉXITO
                $mensaje_exito = "Bienvenido de nuevo, " . htmlspecialchars($usuario['username']) . "!";
                $datos_usuario = [
                    "token" => $jwt, // El token JWT generado
                    "user_id" => $usuario['id'],
                    "username" => $usuario['username'],
                    "email" => $usuario['email'],
                    "estado" => $valor_columna, // Usuario conectado (true)
                    "role" => $usuario['role_name']
                ];

                send_json_success($mensaje_exito, $datos_usuario, 200); // Enviar respuesta de éxito
            } else {
                send_json_error("Usuario o contraseña incorrectos.", 401); // Enviar error 401 con los mensajes de error
            }
        }
    } else {
        // Si no es una solicitud POST, enviamos un error 405 (Método no permitido)
        send_json_error("Método no permitido. Usa POST para enviar datos." . json_encode($identificador), 405); // Enviar error 405 si no es POST
    }

} catch (PDOException $e) {
    error_log("Error de BD (PDO): " . $e->getMessage());
    send_json_error("Ocurrió un error interno del servidor. Por favor, inténtalo de nuevo más tarde.", 500);

} catch (Exception $e) {
    error_log("Error inesperado: " . $e->getMessage());
    send_json_error("Ocurrió un error interno del servidor." . $e->getMessage(), 500);

} finally {
    // Asegurarse de cerrar la declaración y la conexión en el bloque finally
    if (isset($stmt)) { $stmt = null; }
    if (isset($conn)) { $conn = null; }
}
?>