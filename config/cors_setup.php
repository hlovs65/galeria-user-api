<?php
// Usamos el operador @ para suprimir errores si la cabecera ya se envió o falla. 
// Define la URL de tu frontend de Vercel
// Lista de orígenes permitidos
$allowed_origins = [
    'https://galeria-app-frontend.vercel.app', // Producción (Vercel)
    'http://localhost',                        // Desarrollo (Localhost base)
    'http://localhost:5173',                   // Desarrollo (si usas React/Vite en un puerto)
    'http://127.0.0.1'                         // Alternativa para Localhost
];

// Obtener el origen de la solicitud actual
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Verificar si el origen solicitado está en nuestra lista de permitidos
if (in_array($origin, $allowed_origins)) {
    // Si el origen está permitido, se usa ese origen
    $allowed_origin = $origin;
} else {
    // Si el origen no está en la lista (e.g., para peticiones directas), 
    // puedes establecer un valor predeterminado seguro o el de producción
    $allowed_origin = 'https://galeria-app-frontend.vercel.app';
}

// Establecer cabeceras CORS
@header("Access-Control-Allow-Origin: $allowed_origin");
@header("Access-Control-Allow-Credentials: true"); // Permitir el envío de cookies y credenciales
@header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Permitir métodos POST y OPTIONS
@header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Permitir encabezados específicos
@header("Content-Type: application/json; charset=UTF-8");

// Manejar la solicitud OPTIONS (preflight request)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    // Devolvemos la limpieza del buffer. Esto elimina cualquier HTML de advertencia
    // que se haya generado antes de las cabeceras CORS.
    if (ob_get_level()) { ob_end_clean(); }
    exit();
}
?>