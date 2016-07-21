<?php

define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

function apiRequestWebhook($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  header("Content-Type: application/json");
  echo json_encode($parameters);
  return true;
}

function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successfull: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function processMessage($message) {
  // process incoming message
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  if (isset($message['text'])) {
    // incoming text message
    $text = $message['text'];
    $result = dicionario($text);
    if($result == NULL){
                        apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => 'Creio que eu não possa te ajudar com essa palavra =('));
                } else {
                        apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $result));
                }
  } else {
    apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
  }
}

function dicionario($word){
        $url = "http://dicionario-aberto.net/search-json/";
        $req = $url.$word;

        try {
                $file = file_get_contents($req);
        } catch (Exception $e) {
                echo "Get error";
        }

        $json = json_decode($file);
        return strip_tags($json->entry->sense[0]->def);
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
  // receive wrong update, must not happen
  exit;
}

if (isset($update["message"])) {
  processMessage($update["message"]);
}
?>
