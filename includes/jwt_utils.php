<?php
// includes/jwt_utils.php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;


// Asegúrate de que este archivo solo se incluya
if (!defined('ABSPATH')) {
    // Si necesitas definir una constante global para el ABSPATH si estás fuera de un framework
    // o simplemente omite esta verificación si no usas un framework.
}


/**
 * Usada en login.php
 * Genera un JSON Web Token (JWT) basado en los datos del usuario.
 *
 * @param array $usuario Array asociativo con los datos del usuario (al menos 'id' y 'username').
 * @return string El token JWT codificado.
 */
function generate_jwt(array $usuario): string {
    
    // El tiempo de emisión y expiración
    $time = time();
    $expiration_time = $time + (3600 * 24); // Válido por 24 horas

    // El Payload (Datos que se codifican en el token)
    $payload = [
        'iat' => $time,                       
        'exp' => $expiration_time,
        'data' => [       
            'user_id' => $usuario['id'],
            'username' => $usuario['username'],
            'email' => $usuario['email'],
            'role' => $usuario['role_name'] // Asegúrate de que 'role_name' esté presente en el array $usuario
        ]
    ];

    // Generar el JWT en variable local
    $jwt_token = \Firebase\JWT\JWT::encode($payload, JWT_SECRET_KEY, 'HS256');

    // Generar y devolver el JWT, usando la clave secreta global definida en config.php
    return $jwt_token;
}

/**
 * Usada en register.php
 * Genera un JSON Web Token (JWT) para el usuario recién registrado.
 * * @param int $user_id ID del usuario.
 * @param string $username Nombre de usuario.
 * @param string $email Correo electrónico.
 * @param string $nombre Nombre completo.
 * @param string $role Nombre del rol.
 * @return string El token JWT generado.
 */
function create_user_token(int $user_id, string $username, string $email, string $nombre, string $role): string {
    // Tiempo de expiración y emisión (1 semana para este token de registro)
    $issuedAt   = time();
    $notBefore  = $issuedAt;  // Disponible inmediatamente
    $expire     = $notBefore + (60 * 60 * 24 * 7); // Expira en 1 semana
    $issuer     = BASE_URL; // Quién emite el token (tomado de config.php)


    $payload = [
        'iat'  => $issuedAt,        // Tiempo en que fue emitido
        'nbf'  => $notBefore,       // Tiempo antes del cual el token no debe ser aceptado
        'exp'  => $expire,          // Tiempo de expiración
        'iss'  => $issuer,          // Emisor
        'data' => [                 // Datos del usuario
            'id'       => $user_id,
            'username' => $username,
            'email'    => $email,
            'nombre'   => $nombre,
            'role'     => $role
        ]
    ];

    // Generar y devolver el JWT
    $jwt = \Firebase\JWT\JWT::encode($payload, JWT_SECRET_KEY, 'HS256');

    return $jwt;
}

/**
 * Usada en logout.php
 * 
 * Obtiene el ID del usuario desde el token JWT en la cabecera Authorization.
 *
 * @return int|false Retorna el ID del usuario si el token es válido, o false en caso de error.
 */
function get_user_id_from_token() {
    // 1. Obtener la cabecera Authorization y extraer el token
// Reemplaza getallheaders() por $_SERVER
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
    // Si el header no se llama 'HTTP_AUTHORIZATION' (algunos servidores lo omiten), 
    // usa la forma sin HTTP_
    if (!$authHeader) {
        $authHeader = $_SERVER['AUTHORIZATION'] ?? null;
    }

    if (!$authHeader && function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    }

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        error_log("JWT Error: Token no encontrado o formato incorrecto.");
        return false;
    }
    
    $jwt = $matches[1];

    // 2. Decodificar y validar el token (usa la clave y el algoritmo definido)
    // La decodificación también verifica automáticamente la expiración (exp) y 'not before' (nbf)
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));


    // 3. Retornar el ID del usuario desde el payload (asumiendo que está en $decoded->data->user_id)
    if (isset($decoded->data->user_id)) {
        return $decoded->data->user_id;
    }

    error_log("JWT Error: ID de usuario no encontrado en el payload del token.");
    return false;

}