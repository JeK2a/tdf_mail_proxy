<?php



if (strpos($_SERVER['REQUEST_URI'],'.jpg') !== false) {
    $url = $_SERVER['REQUEST_URI'];
    $url = str_replace('ololololo','tdfort', $url);
    $url = str_replace('/tdf-service-1.online_','/tdfort.ru_', $url);

    $img = file_get_contents('https://tdfort.ru/'.$url);
    header("Content-Type: image/jpeg");
    echo $img;
    exit();
}

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');

if (!empty($_POST)) {
//        ini_set("default_socket_timeout", 10);

    header('Content-Type: none');
    header('Content-Type: text/html; charset=utf-8');

    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 1);

    echo getmypid();

//    header('Connection: close');
//    header('Content-Length: ' . ob_get_length());
//    ob_end_flush();
//    ob_flush();
//    flush();
//    session_write_close();


    ob_start();

    require_once __DIR__ . '/mail/SendMail.php';
    require_once __DIR__ . '/mail/ProxySmtp.php';
    require_once __DIR__ . '/smtp/class.phpmailer.php';
    require_once __DIR__ . '/smtp/class.smtp.php';

    $smtp = new SendMail();

    $data = $_POST;

    foreach ($data as $key => $value) {
        $value_arr = json_decode($value, true);

        if (is_array( $value_arr)) {
            $data[$key] = $value_arr;
        }
    }

    $_POST['proxy'] = json_encode($data['proxy']);
    $_POST['from']  = json_encode($data['from']);

    $id = $data['id'];

    $mail_to = $data['to'];
    $subject = $data['subject'];
    $message = $data['body'];
    $proxy   = $data['proxy'];

    $smtp->setSMTPSetings($data['from']);

    $result = $smtp->sendMail($data);

    if (!($id > 0)) {
        echo 'error id - "' . $id . '"';
    }

    $tmp  = ob_get_clean();

    $tmp = urldecode($tmp);
//        $tmp  = urldecode(ob_get_clean());
//    $done = $result['success'] ? 1 : -1;
    $done = 1;

//    $url = 'https://my.tdfort.ru/launchers/mail_send.php?id=' . $id . '&logs=' . $tmp . '&done=' . $done;
    $url = 'https://my.tdfort.ru/launchers/mail_send.php?id=' . $id . '&logs=test&done=' . $done;

    echo file_get_contents($url);

//    echo $tmp;

    return;
}

echo '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="robots" content="index, follow" />
    <title>Forttd</title>
</head>
<body>';

$url_base64 = $_SERVER['REQUEST_URI'];

if (strlen($url_base64) > 1) {
    $url = base64_decode(substr($url_base64, 1, strlen($url_base64) - 1));
}

if (strpos($url, 'unsubscribe') !== false) {

    preg_match_all('/eml_token=([^&]*)/', $url, $matches, PREG_SET_ORDER, 0);

    echo file_get_contents('https://my.tdfort.ru/launchers/mail_unsubscribe.php?eml_token=' . ($matches[0][1] ?? 'dGRmb3J0XzA='));
} else {
    if (!empty($_GET)) {
        $url = $url_base64;
    }

    echo '
                please wait...
                <script>
                    window.location.replace(\'https://tdfort.ru/'. $url .'\');
                </script>        
            ';
}

echo '</body>
</html>';
