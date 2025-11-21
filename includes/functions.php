<?php
// functions.php

// ----------------------------------------------------
// Función de utilidad para enviar respuestas JSON y establecer el código de estado HTTP
// ----------------------------------------------------
function send_json_response($http_code, $data) {
    // Establece el código de estado HTTP (ej: 200, 400, 500)
    http_response_code($http_code);
    
    // Indica que la respuesta es JSON
    header('Content-Type: application/json');
    
    // Codifica y envía los datos
    echo json_encode($data);
    
    exit; // Termina la ejecución del script
}

// ----------------------------------------------------
// Lógica para enviar errores en formato JSON
// ----------------------------------------------------
function send_json_error(string $message, int $http_code = 401) {
    // 1. Limpiar cualquier salida que se haya generado ANTES de esta llamada (el famoso <br>)
    if (ob_get_level()) {
        ob_clean(); // Limpia (descarta) el contenido actual del buffer
    }

    // Establece el código de estado HTTP
    http_response_code($http_code); 
    // Imprime el mensaje de error en JSON
    echo json_encode(['status' => 'error', 'message' => $message]);

    exit;
}

/**
 * Detiene la ejecución y envía una respuesta de éxito en formato JSON.
 *
 * @param string $message Mensaje de éxito a devolver.
 * @param array $data Datos adicionales a incluir (ej. 'token' y 'username').
 * @param int $http_code Código de estado HTTP (por defecto 200 OK).
 */
function send_json_success(string $message, array $data = [], int $http_code = 200) {
    // 1. Limpiar cualquier salida que se haya generado ANTES de esta llamada (el famoso <br>)
    if (ob_get_level()) {
        ob_clean(); // Limpia (descarta) el contenido actual del buffer
    }

    // 2. Establece el código de estado HTTP
    http_response_code($http_code); 
    
    // 3. Construye el array de respuesta base
    $response = [
        'status' => 'success',
        'message' => $message,
    ];
    
    // 4. Fusiona los datos adicionales (como el token)
    $response = array_merge($response, $data);
    
    // 5. Imprime la respuesta JSON
    echo json_encode($response);

    // 6. Detiene el script
    exit;
}

// Puedes añadir esta función a tu archivo 'functions.php' o a otro archivo de utilidades de base de datos.

/**
 * Actualiza el estatus de un usuario en la base de datos a 0 (desconectado).
 * Actualiza el campo especificado de un usuario en la base de datos: correo_verificado o estado.
 *
 * @param PDO $conn La conexión a la base de datos.
 * @param int $userId El ID del usuario cuyo estatus se va a modificar.
 * @param string $columnName El campo que se va a actualizar (correo_verificado o estado).
 * @param bool $newValue El nuevo valor del campo (false o true).
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 * @throws InvalidArgumentException Si los parámetros de entrada son inválidos.
 */
function update_user_field($conn, $userId, $columnName, $newValue) {
    // Asegurarse de que la conexión sea válida
    if (!$conn || !$userId || !$columnName) {
        error_log("update_user_field: Conexión inválida o ID o nombre de columna no proporcionado.");
        throw new InvalidArgumentException("Conexión inválida o ID o nombre de columna no proporcionado.");
    }
    
    $allowed_columns = ['correo_verificado', 'estado'];
    if (!in_array($columnName, $allowed_columns)) {
        error_log("update_user_field: Columna no permitida.");
        throw new InvalidArgumentException("Columna no permitida.");
    }
    

        // Preparar la consulta 
        $sql_query = "UPDATE usuarios SET \"" . $columnName . "\" = :newValue WHERE id = :userId";
        $stmt = $conn->prepare($sql_query);

        /*-----------------------------------------------------------*/
        /*-- Bindear los parámetros para la actualización de usuario */
        /*-----------------------------------------------------------*/
        // 1. Bindear el valor nuevo de estado como booleano
        $stmt->bindValue(':newValue', $newValue, PDO::PARAM_BOOL);

        // 2. Bindear el ID del usuario como entero
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);

        // Ejecutar la consulta. 
        $stmt->execute();

        // Verificar si se actualizó algún registro
        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            error_log("update_user_field: No se actualizó ningún registro. Verifique el ID del usuario.");
            return false;
        }   
}

/**
 * Obtiene el estatus actual de un usuario desde la base de datos.
 *
 * @param PDO $conn La conexión a la base de datos.
 * @param string $columnToSelect El campo/s a seleccionar (ej: 'username, email' o '*').
 * @param array $conditions Un array asociativo con las condiciones de búsqueda (ej: ['id' => 1, 'email' => 'ejemplo@correo.com']).
 * @return array | ['id' => 0] Un array con los datos del usuario si se encuentra, o indicador con valor 0 si no existe.
 * @throws InvalidArgumentException Si los parámetros de entrada son inválidos.
 */
function get_user_data_by_conditions(PDO $conn, string $columnToSelect, array $conditions): ?array {

    // 1.- Validar parametro de entrada
    if (!$conn || empty($columnToSelect) || empty($conditions)) {
        error_log("get_user_data_by_conditions: Conexión inválida o parámetros invalidos.");
        throw new InvalidArgumentException("Conexión inválida o parámetros invalidos.");
    }

    /**
    * Construir la clausula WHERE dinámicamente
    * Validar que los campos de búsqueda y selección sean válidos
    */
    $where_clauses = [];
    $allowed_columns = ['id', 'username', 'password', 'email', 'nombre', 'fecha_registro', 'estado', 'correo_verificado', 'role_name'];

    // 2.1 Validar que la columna de busqueda sea válida
    foreach ($conditions as $column => $value) {
        if (!in_array($column, $allowed_columns)) {
            error_log("get_user_data_by_conditions: Columnas no permitidas para busqueda.");
            throw new InvalidArgumentException("Columnas no permitidas para busqueda: " . $column);
        }
        $where_clauses[] = "\"{$column}\" = :{$column}"; // PostgreSQL requiere: Usar comillas dobles para nombres de columnas
    }

    $where_string = "WHERE " . implode(' OR ', $where_clauses);

    // 2.2 Validar que la columna de selección sea válida
    $select_columns = '*'; // Por defecto seleccionar todas las columnas
    if ($columnToSelect !== '*') {
        $select_array = [];
        $select_columns = explode(',', $columnToSelect); // Dividir la cadena de nombres de columnas en un array

        // Iterar sobre las columnas seleccionadas que existan en la lista permitida
        foreach ($select_columns as $col) {
            $clean_col = trim($col);
            if (!in_array($clean_col, $allowed_columns)) {
                error_log("get_user_data_by_conditions: Columnas no permitidas para selección.");
                throw new InvalidArgumentException("Columnas no permitidas para selección: " . $clean_col);
            }
            $select_array[] = "\"{$clean_col}\""; // PostgreSQL requiere: Usar comillas dobles para nombres de columnas
        }
        $select_columns = implode(", ", $select_array);
    }

    // 3. Preparar la consulta SQL
    $sql_query = "SELECT " . $select_columns . " FROM usuarios " . $where_string . " LIMIT 1";

        $stmt = $conn->prepare($sql_query);

        // 4.- Bindear los parámetros dinámicamente
        foreach ($conditions as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        // 5.- Ejecutar la consulta
        $stmt->execute();

        // 6.- Obtener el resultado
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user_data ?: ['id' => 0]; // Retornar indicador con valor 0 si no se recupera ningún usuario
}   

/**
 * Registra un nuevo registro en una tabla de la base de datos de forma dinámica y segura.
 *
 * @param PDO $conn La conexión a la base de datos.
 * @param string $tableName El nombre de la tabla donde se insertará (ej: 'usuarios').
 * @param array $data Un array asociativo con los valores de los campos (ej: ['id' => 1, 'email' => 'ejemplo@correo.com']).
 * @return array returna un array con el ID del usuario insertado (ej: ['id' => 1]) si se realiza con éxito.
 * @throws InvalidArgumentException Si los parámetros de entrada son inválidos.
 */
function create_record(PDO $conn, string $tableName, array $data): ?array {
    $tableName = trim($tableName);
    // 1.- Validar parametro de entrada
    if (!$conn || empty($tableName) || empty($data)) {
        error_log("create_record: Conexión inválida o parámetros invalidos.");
        throw new InvalidArgumentException("Conexión inválida o parámetros invalidos.");
    }

    /**
    * 2. Construir la clausula Values dinámicamente
    * Validar que los campos de registro y sus valores sean válidos
    */
    $values_clauses = [];
    $columns_clauses = [];
    $allowed_columns = ['id', 'username', 'password', 'email', 'nombre', 'estado', 'correo_verificado', 'role_name'];

    // 2.1 Validar que la columna de busqueda sea válida
    foreach ($data as $column => $value) {
        if (!in_array($column, $allowed_columns)) {
            error_log("create_record: Columnas no permitidas para busqueda." . $column);
            throw new InvalidArgumentException("Columnas no permitidas para busqueda: " . $column);
        }
        $columns_clauses[] = "\"{$column}\""; // PostgreSQL requiere: Usar comillas dobles para nombres de columnas
        $values_clauses[] = ":{$column}"; // Usar placeholders para los valores
    }
    // Construir la cadena de valores para la consulta SQL
    $values_string = "VALUES " . "(" . implode(', ', $values_clauses) . ")";

    // Construir la cadena de columnas para la consulta SQL
    $columns_string = implode(", ", $columns_clauses);

    // 3. Preparar la consulta SQL
    $sql_query = "INSERT INTO " . $tableName . " (" . $columns_string . ") " . $values_string . " RETURNING \"id\"";

        $stmt = $conn->prepare($sql_query);

        // 4.- Bindear los parámetros dinámicamente
        foreach ($data as $column => $value) {
            if ($column === 'estado' || $column === 'correo_verificado') {
                // Bindeo como booleano
                $stmt->bindValue(':' . $column, $value, PDO::PARAM_BOOL);
            } else if ($column === 'username' || $column === 'password' || $column === 'email' || $column === 'nombre' || $column === 'role_name') {
                // Bindeo como string
                $stmt->bindValue(':' . $column, $value, PDO::PARAM_STR);
            } else {
                // Bindeo por defecto
                $stmt->bindValue(':' . $column, $value, PDO::PARAM_STR);
            }
        }

        // 5.- Ejecutar la consulta
        $stmt->execute();

        // 6.- Obtener el resultado
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user_data ?: ['id' => 0]; // Retornar array prueba de fallos si no se recupera id del usuario insertado
}

/**
 * Obtiene la fecha de expiración y token de un usuario desde la base de datos.
 *
 * @param PDO $conn La conexión a la base de datos.
 * @param string $token del usuario cuyo estatus se desea consultar.
 * @param string $nombre_tabla El nombre de la tabla donde se almacenan los tokens.
 * @return int Un entero con 'user_id' si 'token' se encuentra, o 0 si el token no existe.
 * @throws InvalidArgumentException Si los parámetros de entrada son inválidos.
 */
function get_user_current_id($conn, $token, $nombre_tabla): int {
    // Asegurarse de que la conexión sea válida
    $allowed_tables = ['email_verifications', 'password_resets'];

    if (!$conn || !$token  || empty($nombre_tabla) || !in_array($nombre_tabla, $allowed_tables)) {
        error_log("get_user_current_id: Conexión inválida o parámetros invalidos.");
        throw new InvalidArgumentException("Conexión inválida o parámetros invalidos.");
    }

    // Preparar la consulta
    $sql_query = "SELECT id, user_id, expires_at FROM " . $nombre_tabla . " WHERE \"token\" = :token AND \"expires_at\" > NOW()";
    
    // Usar una consulta preparada para evitar inyecciones SQL
    $stmt = $conn->prepare($sql_query);

    // Vincular el parámetro
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);

    // Ejecutar la consulta
    $stmt->execute();

    // Obtener el resultado
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si se encontró el token
    return (int) ($result['user_id'] ?? 0); // Retornar 0 si no se recupera ningún token
}

/**
 * Elimina un registro de una tabla basándose en un token o id.
 *
 * @param PDO $conn La conexión a la base de datos.
 * @param string $nombre_tabla El nombre de la tabla donde se encuentra el registro.
 * @param string $columnToSearch El nombre de la columna por la que se busca. (Ejemplo: token, id)
 * @param mixed $searchValue El valor a buscar en la tabla. (Ejemplo: "string", int)
 * @return bool True si la eliminación fue exitosa, false si el token no se encontró.
 * @throws InvalidArgumentException Si los parámetros de entrada son inválidos.
 */
function delete_record_by_field($conn, $nombre_tabla, $columnToSearch, $searchValue): bool {
    // Asegurarse de que la conexión sea válida
    $allowed_tables = ['usuarios', 'email_verifications', 'password_resets'];
    if (!$conn || !$searchValue || empty($nombre_tabla) || empty($columnToSearch) || !in_array($nombre_tabla, $allowed_tables)) {
        error_log("delete_record_by_field: Conexión inválida o parámetros invalidos.");
        throw new InvalidArgumentException("Conexión inválida o parámetros invalidos.");
    }

    // Preparar la consulta
    $sql_query = "DELETE FROM " . $nombre_tabla . " WHERE " . "\"{$columnToSearch}\"" . " = :searchValue";

    // Usar una consulta preparada para evitar inyecciones SQL
    $stmt = $conn->prepare($sql_query);

    // Vincular el parámetro
    $stmt->bindValue(':searchValue', $searchValue, is_int($searchValue) ? PDO::PARAM_INT : PDO::PARAM_STR);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        return true; // Eliminación exitosa
    } else {
        return false; // Fallo en la eliminación (token no encontrado)
    }
}



/**
 * Función para validar la fortaleza de una contraseña.
 *
 * @param string $contraseña La contraseña a validar.
 * @param array $errores Array para almacenar los mensajes de error.
 *              El array estará vacío si no hay errores.
 */
function validatePassword($contraseña) {

    $errores = [];

    // Validar la longitud de la contraseña
    if (strlen($contraseña) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres.";
    }

    // Validar que la contraseña contenga al menos un número
    if (!preg_match('/[0-9]/', $contraseña)) {
        $errores[] = "La contraseña debe contener al menos un número.";
    }

    // Validar que la contraseña contenga al menos una letra mayúscula
    if (!preg_match('/[A-Z]/', $contraseña)) {
        $errores[] = "La contraseña debe contener al menos una letra mayúscula.";
    }

    // Validar que la contraseña contenga al menos un carácter especial
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $contraseña)) {
        $errores[] = "La contraseña debe contener al menos un carácter especial.";
    }

    return $errores;
}