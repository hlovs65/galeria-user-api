<?php
// Define la URL de tu frontend de Vercel
$allowed_origin = 'https://galeria-app-frontend.vercel.app'; 

// Establecer cabeceras CORS
header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Credentials: true"); // Permitir el envío de cookies y credenciales
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Permitir métodos POST y OPTIONS
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Permitir encabezados específicos
header("Content-Type: application/json; charset=UTF-8");

// Manejar la solicitud OPTIONS (preflight request)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    if (ob_get_level()) { ob_end_clean(); } // Limpiar el búfer de salida si es necesario
    exit();
}
?>