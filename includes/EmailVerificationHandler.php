<?php

// EmailVerificationHandler.php - El controlador para enviar correos electrónicos
/**
 * Obtiene la fecha de expiración y token de un usuario desde la base de datos.
 *
 * @param PDO    $conn La conexión a la base de datos.
 * @param string $email registrado por el usuario.
 * @param int    $user_id ID del usuario.
 * @param string $name_link Nombre del enlace para la verificación.
 * @param string $name_table Nombre de la tabla para la verificación.
 * @param string $subject Asunto del correo electrónico.
 * @param string $message Cuerpo del correo electrónico.
 * @return bool  Retorna true si el proceso fue exitoso, false en caso contrario.
 */

require_once 'EmailSenderHandler.php'; // Incluye la función de bajo nivel

function email_verification($conn, $email, $user_id, $name_link, $name_table, $subject, $message) {

        // =======================================================
        // PASO 1: Validar los parámetros de entrada
        // =======================================================

        $allowed_tables = ['email_verifications', 'password_resets'];
        if (!$conn || !$email || empty($user_id) || empty($name_link) || empty($name_table) || empty($subject) || empty($message) || !in_array($name_table, $allowed_tables)) {
            throw new InvalidArgumentException("Parámetros de entrada no válidos para email_verification.");
        }

        // =======================================================
        // PASO 2: Generar un token único y fecha de expiracion
        // =======================================================

        //2.1.- Generar token unico y seguro
        $token = bin2hex(random_bytes(32));

        //2.2.- Calcular la fecha de expiración (1 hora desde ahora)
        $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour")); //Expira en 1 hora

        // =======================================================
        // PASO 3: Transacción de Base de Datos
        // =======================================================
        try {
            $conn->beginTransaction(); // Iniciar la transacción

            $sql_delete = "DELETE FROM " . $name_table . " WHERE user_id = :user_id";
            $stmt_delete = $conn->prepare($sql_delete);

            $stmt_delete->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_delete->execute();
    
        //3.4.- Guardar el nuevo token

            $sql_insert = "INSERT INTO " . $name_table . " (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
            $stmt_insert = $conn->prepare($sql_insert);
    
            $stmt_insert->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_insert->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt_insert->bindValue(':expires_at', $expires_at, PDO::PARAM_STR);
            $stmt_insert->execute();

            $conn->commit(); // Confirmar la transacción

        } catch (PDOException $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack(); // Revertir la transacción en caso de error
            }

            // Propagacion: Re-lanzar PDOException para manejo superior con http status code 500
            throw $e;
        }

        // =======================================================
        // PASO 4: Envío del Correo Electrónico
        // =======================================================

        //4.1.- Llamar a email_sender para enviar el correo
       
        email_sender($email, $token, $name_link, $subject, $message);
        
        return true; // Retornar verdadero si todo salió bien
}