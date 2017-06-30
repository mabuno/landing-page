<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$client = new Client();

$json    = json_decode(file_get_contents('php://input'), true);
$chatId  = $json['message']['chat']['id'];
$message = $json['message']['text'];
$command = '';
$param   = '';

if ($message[0] === '/') {
    $command = substr(explode('@', $message)[0], 1);

    if (strpos($command, ' ')) {
        $command = explode(' ', $command)[0];
    }

    $param = trim(implode(' ', array_slice(explode(' ', $message), 1)));
}

if ($command === 'sovet') {
    if ($param !== '') {
        $url = 'http://fucking-great-advice.ru/api/random_by_tag/' . urlencode($param);
    } else {
        $url = 'http://fucking-great-advice.ru/api/random';
    }

    try {
        $result = $client->get($url);
    } catch (RequestException $e) {
        $result = false;
    }

    if ($result) {
        $advice = json_decode($result->getBody()->getContents(), true);

        if (is_array($advice) && array_key_exists('text', $advice)) {
            sendMessage($chatId, html_entity_decode($advice['text']));
        }
    }
}

function sendMessage($chatId, $text)
{
    global $client;

    try {
        return $client->post('https://api.telegram.org/bot' . getenv('API_KEY') . '/sendMessage', [
            'form_params' => [
                'chat_id'    => $chatId,
                'parse_mode' => 'Markdown',
                'text'       => $text
            ]
        ]);
    } catch (RequestException $e) {
        return false;
    }
}
