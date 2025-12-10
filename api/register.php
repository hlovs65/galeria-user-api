<?php
// api/register.php - Manejo del registro de usuarios con verificación de correo electrónico

ob_start(); // Iniciar el búfer de salida

// ----------------------------------------------------
// 1. Configuración Inicial y CORS (Necesario para React)
// ----------------------------------------------------
require_once __DIR__ . '/../config/cors_setup.php';

// ----------------------------------------------------
// PASO CLAVE: INCLUSIÓN DE COMPOSER Y CONFIGURACIÓN
// ----------------------------------------------------
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once "../includes/config.php"; //  Incluir la configuración (base de datos, JWT_SECRET_KEY, etc.)

// ----------------------------------------------------
// INCLUSIONES DE UTILIDADES
// ----------------------------------------------------
require_once '../includes/functions.php'; // Asegúrate de que este path sea correcto para tu proyecto
require_once '../includes/jwt_utils.php'; // Incluir las funciones de JWT
require_once '../includes/db_connection.php'; // Incluir el archivo de conexión a la base de datos
require_once '../includes/EmailVerificationHandler.php'; // Proceso para generar token y enviar correo
require_once '../includes/send_email.php'; // Incluir la función de envío de correo por PHPMAILER


try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // 2. Leer el cuerpo crudo de la solicitud
        $json_data = file_get_contents('php://input');

        // 3. Decodificar el JSON en un objeto o array asociativo de PHP
        // true para que sea un array asociativo
        $data = json_decode($json_data, true);

        // 4. Verificar si la decodificación fue exitosa y si los campos existen
        if ($data === null) {
            // Fallo en la decodificación JSON o cuerpo vacío
            send_json_error("Formato JSON inválido.", 400);
        }

        // ----------------------------------------------------
        // PROCESO DE REGISTRO DE USUARIO
        // ----------------------------------------------------

        // ----------------------------------------------------
        // 5. Recibir los datos del  formulario y que no estén vacíos
        // ----------------------------------------------------
        $errores    = []; // Array para almacenar errores
        $estado     = false; // Estado inactivo por defecto
        $correo_verificado = false; // Correo no verificado por defecto
        $usuario = (trim($data['username'] ?? ''));
        $contraseña = trim($data['password'] ?? '');
        $confirmaContraseña = trim($data['confirm-password'] ?? '');
        $email = (trim($data['email'] ?? ''));
        $nombre = (trim($data['nombre'] ?? ''));

        if (empty($data['username']) || empty($data['password']) || empty($data['confirm-password']) || empty($data['email']) || empty($data['nombre'])) {
            $errores[] = "Todos los campos son obligatorios.";
        }
        
        // 5.1 Validar que las contraseñas coincidan
        if ($contraseña !== $confirmaContraseña) {
            $errores[] = "Las contraseñas no coinciden.";
        }
        
        // 5.2 Validar el formato del email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del email es inválido.";
        }

        // 5.3 Validar la contraseña
        $errores_validacion = validatePassword($contraseña);

        if (!empty($errores_validacion)) {
            $errores = array_merge($errores, $errores_validacion);
        }

        // 5.4 Validar usuario y correo electrónico no este repetido
        $columna_to_select = 'username, email';
        $condition = ['username' => $usuario, 'email' => $email];
        
        $existing_user = get_user_data_by_conditions($conn, $columna_to_select, $condition);

        if ($existing_user['id'] > 0) {
            $errores[] = "El nombre de usuario o correo electrónico ya están en uso.";
        }

        // Si hay errores, los mostramos y detenemos la ejecución
        if (!empty($errores)) {
            // Cierra recursos si es necesario antes de la redirección
            send_json_error(implode(" ", $errores), 400);
        }

        // Encriptar la contraseña antes de almacenarla
        if (empty($errores)) {
            $contraseña = password_hash($contraseña, PASSWORD_DEFAULT);
        }

        // ----------------------------------------------------
        // 6. Listar los datos para insertar en la base de datos
        // ----------------------------------------------------
        $table_name = 'usuarios';
        $data_to_insert = [
            'username' => $usuario,
            'password' => $contraseña,
            'email' => $email,
            'nombre' => $nombre,
            'estado' => $estado,
            'correo_verificado' => $correo_verificado
        ];
        $insert_result = create_record($conn, $table_name, $data_to_insert);

        // ----------------------------------------------------
        // 7. Verificar la dirección de correo electrónico
        // ----------------------------------------------------

        // Obtener los datos del usuario recién insertado
        $insert_id = $insert_result['id']; 
            
        // Preparar los datos para la verificación de correo
        $name_link = BASE_URL_PARA_ENLACES_INTERNOS . "controllers/verify_email.php"; // Nombre del enlace para la verificación de correo con la URL del backend
        $name_table = "email_verifications"; // Nombre de la tabla para la verificación de correo
        $verification_subject = "Activar cuenta";
        $verification_message = "Hola,\n\nPara activar tu cuenta, haz clic en el siguiente enlace:\n\n{link}\n\nEl enlace expirará en 1 hora.";

        // Llamar a la función de verificación de correo
        email_verification($conn, $email, $insert_id, $name_link, $name_table, $verification_subject, $verification_message);


        // generar JWT Y enviar respuesta exitosa
        $role = 'user'; // Rol por defecto para nuevos usuarios
        $jwt = create_user_token($insert_id, $usuario, $email, $nombre, $role);
        $mensaje_exito = "¡Registro exitoso! Por favor verifica tu correo electrónico para activar tu cuenta.";
        $datos_usuario = [
            "token" => $jwt, // Incluir el token JWT en la respuesta
            'id' => $insert_id,
            'username' => $usuario, // Incluir el nombre de usuario
            'email' => $email, // Incluir el correo electrónico
            'nombre' => $nombre, // Incluir el nombre completo
            'role_name' => $role // Incluir el nombre del rol por defecto
        ];

        send_json_success($mensaje_exito, $datos_usuario, 201); // Enviar respuesta exitosa con datos del usuario y token JWT

    }else {
        // Método no permitido 
        send_json_error("Método no permitido. Usa POST para registrar un usuario.", 405);
    } 
} catch (InvalidArgumentException $e) {
    error_log("Error de argumento inválido: " . $e->getMessage());
    send_json_error("Error en los datos proporcionados. Por favor, verifica e inténtalo de nuevo.", 400);

} catch (PDOException $e) {
    $error_message = "Error de BD (PDO) en register: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() .
                     " | Trace: \n" . $e->getTraceAsString();
    error_log($error_message);
    send_json_error("Error de base de datos. Por favor, inténtalo más tarde.", 500);

} catch (Exception $e) {
    $error_message = "Error inseperado en register: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() .
                     " | Trace: \n" . $e->getTraceAsString();
    error_log($error_message);
    send_json_error("Ocurrio un error inesperado. Por favor, inténtalo más tarde.", 500);

} finally {
    // Cerrar la conexión a la base de datos si es necesario
    if (isset($conn)) {$conn = null;}
}