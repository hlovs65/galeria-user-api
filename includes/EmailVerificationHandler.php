<?php

// EmailVerificationHandler.php - El controlador para enviar correos electrónicos
/**
 * Obtiene la fecha de expiración y token de un usuario desde la base de datos.
 *
 * @param mysqli $conn La conexión a la base de datos.
 * @param string $email registrado por el usuario.
 * @param int $user_id ID del usuario.
 * @param string $name_link Nombre del enlace para la verificación.
 * @param string $name_table Nombre de la tabla para la verificación.
 * @param string $subject Asunto del correo electrónico.
 * @param string $message Cuerpo del correo electrónico.
 * @return bool Retorna true si el proceso fue exitoso, false en caso contrario.
 */
function email_verification($conn, $email, $user_id, $name_link, $name_table, $subject, $message) {
    // PASO 1: Validar los parámetros de entrada
    $allowed_tables = ['email_verifications', 'password_resets'];
    if (!$conn || !$email || empty($user_id) || empty($name_link) || empty($name_table) || empty($subject) || empty($message) || !in_array($name_table, $allowed_tables)) {
        error_log("Error en la conexión o datos no válidos: " . $conn->error);
        return false;
    }

    // PASO 2: Generar un token único y enviar el correo de verificación
        //2.1.- Generar token unico y seguro
        $token = bin2hex(random_bytes(32));

        //2.2.- Guardar el token en una tabla dedicada para verificar correo
        $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour")); //Expira en 1 hora

        //2.3.- Borrar tokens antiguos para el mismo usuario
        try {
            $stmt = $conn->prepare("DELETE FROM `" . $name_table . "` WHERE user_id = ?");
            if (!$stmt) {
                error_log("Error al preparar la consulta de eliminación: " . $conn->error);
                return false;
            }
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta de eliminación: " . $stmt->error);
                return false;
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Error al eliminar tokens antiguos: " . $e->getMessage());
            return false;
        }

        //3.4.- Guardar el nuevo token
        try {
            $stmt = $conn->prepare("INSERT INTO `" . $name_table . "` (user_id, token, expires_at) VALUES (?, ?, ?)");
            if (!$stmt) {
                error_log("Error al preparar la consulta de inserción: " . $conn->error);
                return false;
            }

            $stmt->bind_param("iss", $user_id, $token, $expires_at);
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta de inserción: " . $stmt->error);
                return false;
            }
            $stmt->close();

        } catch (mysqli_sql_exception $e) {
            error_log("Error al preparar la consulta de inserción: " . $e->getMessage());
            return false;
        }


        //2.5.- Enviar el correo electronico
        $link_url = BASE_URL . $name_link . "?token=" . urlencode($token); // Asegúrate de usar tu URL real

        $message = str_replace("{link}", $link_url, $message);

        if (!send_mail($email, $subject, $message)) {
            error_log("Error al enviar el correo de verificación: " . $email);
            return false;
        }


    return true; // Retornar verdadero si todo salió bien
}
?>

