<?php
session_start();

header('Content-Type: application/json');

$telefono = $_SESSION['celular'] ?? null;

if ($telefono === null) {
    echo json_encode(['ready' => false]);
    exit;
}

$archivo = __DIR__ . '/codigos.json';
if (!is_file($archivo)) {
    echo json_encode(['ready' => false]);
    exit;
}

$json = file_get_contents($archivo);
$datos = json_decode($json, true);
if (!is_array($datos) || !isset($datos[$telefono]) || !is_array($datos[$telefono])) {
    echo json_encode(['ready' => false]);
    exit;
}

$estado = $datos[$telefono]['estado'] ?? null;

if ($estado === 'listo') {
    // Limpiar estado para no repetir la redirección
    $datos[$telefono]['estado'] = null;
    file_put_contents($archivo, json_encode($datos));
    echo json_encode(['ready' => true]);
} else {
    echo json_encode(['ready' => false]);
}
