<?php

// reset_password_handler.php - Controlador para manejar el restablecimiento de contraseña

// ----------------------------------------------------
// PASO 1: CLAVE: INCLUSIÓN DE COMPOSER Y CONFIGURACIÓN
// ----------------------------------------------------
require_once '../vendor/autoload.php'; // Incluir Composer autoload si es necesario
// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require_once '../includes/config.php'; // Incluir el archivo de configuración

// ----------------------------------------------------
// INCLUSIONES DE UTILIDADES
// ----------------------------------------------------
require_once '../includes/functions.php'; // Asegúrate de que este path sea correcto para tu proyecto
require_once '../includes/db_connection.php'; // Incluir el archivo de conexión a la base de datos


try {
    // ----------------------------------------------------
    // PASO 2: Procesar el formulario solo si el método es POST
    // ----------------------------------------------------
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $errores    = []; // Array para almacenar errores
        $token      = htmlspecialchars(trim($_POST['token'] ?? "")); // Token de seguridad 
        $contraseña = trim($_POST['password'] ?? "");
        $confirmaContraseña = trim($_POST['confirm-password'] ?? "");
        $nombre_tabla = "password_resets"; // Nombre de la tabla para los tokens de restablecimiento de contraseña

        // Validar el token para prevenir que se envíe un formulario sin token debido a un ataque CSRF
        if (!isset($token) || empty($token)) {
            error_log("Token faltante en reset_password_handler.php");
            send_json_error("Token faltante. Por favor, solicita uno nuevo.", 400);
        }

        if (empty($contraseña) || empty($confirmaContraseña)) {
            $errores[] = "Todos los campos son obligatorios.";
        }

        // Validar que las contraseñas coincidan
        if ($contraseña !== $confirmaContraseña) {
            $errores[] = "Las contraseñas no coinciden.";
        }

        // Validar longitud de la contraseña, que tenga al menos 8 caracteres,
        // que contenga al menos un número, una letra mayúscula y un carácter especial
        $errores_contraseña = validatePassword($contraseña);
        $errores = array_merge($errores, $errores_contraseña);

        // Si hay errores, los mostramos y detenemos la ejecución
        if (!empty($errores)) {
            error_log("Errores de validación en reset_password_handler.php: " . implode(", ", $errores));
            send_json_error(implode(", ", $errores), 400);
        }
    
        // Encriptar la contraseña usando  password_hash solo si no hay errores previos
        if (empty($errores)) {
            $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
        }
        // ----------------------------------------------------
        // PASO 3: Obtener el ID del usuario, con el token que tiene relacionado, desde la base de datos
        // ----------------------------------------------------
        $user_id_from_db = get_user_current_id($conn, $token, $nombre_tabla);

        if ($user_id_from_db === 0) {
            error_log("Token no válido o expirado en reset_password_handler.php");
            send_json_error("Token no válido o expirado. Solicita uno nuevo o cancela.", 400);
        }

        // ----------------------------------------------------
        // PASO 4: Preparar y ejecutar la consulta SQL para actualizar la contraseña
        // ----------------------------------------------------
        $result_update = update_user_field($conn, $user_id_from_db, "password", $contraseña_hash);

        if ($result_update === false) {
            throw new Exception("Error al actualizar la contraseña para el usuario con ID: " . $user_id_from_db);
        }
        
        // ----------------------------------------------------
        // PASO 5: Borrar tokens antiguos para el mismo usuario
        // ----------------------------------------------------
        // El borrado puede fallar sin ser un error crítico, así que no detenemos la ejecución.
        delete_record_by_field($conn, $nombre_tabla, "user_id", $user_id_from_db);


        // ----------------------------------------------------
        // PASO 6: Redirigir al usuario al login con mensaje de éxito   
        // ----------------------------------------------------
        $mensaje_exito = "Contraseña actualizada correctamente. Ya puedes iniciar sesión con tu nueva contraseña.";
        
        send_json_response(200, [
            "status" => "success",
            "message" => $mensaje_exito
        ]);

    } else {
        // Si la solicitud no es POST, redirigir al formulario de restablecimiento de contraseña
        error_log("Solicitud no válida en reset_password_handler.php: método no es POST");
        send_json_error("Solicitud no válida. Por favor, utiliza el formulario de restablecimiento de contraseña.", 405);
    }
} catch (InvalidArgumentException $e) {
    // Manejo de errores de argumentos inválidos
    error_log("InvalidArgumentException in reset_password_handler.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine());
    send_json_error("Argumento inválido.", 400);

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    $error_message = "Database error in reset_password_handler.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() . 
                     " | Trace: " . $e->getTraceAsString();
    error_log($error_message);
    send_json_error("Error de base de datos.", 500);

} catch (Exception $e) {
    // Manejo de errores generales
    $error_message = "Unexpected error in reset_password_handler.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() .
                     " | Trace: " . $e->getTraceAsString();
    error_log($error_message);
    send_json_error("Error inesperado.", 500);

} finally {
    // Cerrar la conexión a la base de datos
    $conn = null;
}
?>