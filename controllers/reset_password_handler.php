<?php
// Script de prueba MÍNIMO

// NOTA: Se evita require_once para no incluir archivos posiblemente contaminados.

// 1. Limpiar buffer (protección contra output accidental del servidor)
if (ob_get_level()) {
    ob_clean(); 
}

// 2. Establecer encabezado JSON
header('Content-Type: application/json; charset=utf-8');

// 3. Imprimir JSON de prueba
echo json_encode(['status' => 'success', 'message' => 'TEST DE AISLAMIENTO: EL ARCHIVO BASE ESTA LIMPIO']);

// 4. Detener ejecución
exit;