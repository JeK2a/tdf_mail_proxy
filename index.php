<?php

if (strpos($_SERVER['REQUEST_URI'],'.gif') !== false) {
    // Создание изображения
    $im = imagecreatetruecolor(1, 1);

    //imagefilledrectangle($im, 0, 0, 1, 1, 0xFFFFFF);
    imagefilledrectangle($im, 0, 0, 1, 1, 0xFFFFFF);

    header('Content-Type: image/gif');

    imagegif($im);
    imagedestroy($im);

    file_get_contents('https://my.tdfort.ru/launchers/mail_read.php?email=' . $_GET['email']);

    return;
}

if (strpos($_SERVER['REQUEST_URI'],'.jpg') !== false) {
    $url = $_SERVER['REQUEST_URI'];
    $url = str_replace('ololololo','tdfort', $url);
    $url = str_replace('/tdf-service-1.online_','/tdfort.ru_', $url);
    $url = str_replace('_tdf-service-1.online_','_tdfort.ru_', $url);

    $img = file_get_contents('https://tdfort.ru/'.$url);
    header("Content-Type: image/jpeg");
    echo $img;
    exit();
}

if (
    !empty($_POST) ||
    !empty($_GET['test'])
) {
//        ini_set("default_socket_timeout", 10);

    header('Content-Type: none');
    header('Content-Type: text/html; charset=utf-8');

    ob_start();

//        echo getmypid();

//        header('Connection: close');
//        header('Content-Length: ' . ob_get_length());
//        ob_end_flush();
//        ob_flush();
//        flush();
//        session_write_close();
//
//        ob_start();

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

    $id = $data['id'];

    $mail_to = $data['to'];
    $subject = $data['subject'];
    $message = $data['body'];
    $proxy   = $data['proxy'];

    $smtp->setSMTPSetings($data['from']);

    error_reporting(E_ALL ^ E_NOTICE);//
    ini_set('display_errors', 1);
    $result = $smtp->sendMail($data);

    if (!($id > 0)) {
        echo 'error id - "' . $id . '"';
    }

    $tmp  = ob_get_clean();
    $done = $result['success'] ? 1 : -1;
    $url  = 'https://my.tdfort.ru/launchers/mail_send.php?id=' . $id . '&logs=' . $tmp . '&done=' . $done;

    echo $tmp;

    return;
}

$url_base64 = $_SERVER['REQUEST_URI'];

if (strlen($url_base64) > 1) {
    $url = base64_decode(substr($url_base64, 1, strlen($url_base64) - 1));

    preg_match_all('/task_id=([^&]*)/', $url, $matches, PREG_SET_ORDER, 0);

    if (!empty($matches)) {
        file_get_contents('https://my.tdfort.ru/launchers/mail_link.php?task_id=' . ($matches[0][1] ?? '1'));
    }
}

echo '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="robots" content="index, follow" />
    <img height="1" width="1" src="https://my.tdfort.ru/launchers/mail_link.php?email=' . ($matches[0][1] ?? '1') . '">
    <title>Forttd</title>
</head>
<body>';

if (strpos($url, 'unsubscribe') !== false) {
    preg_match_all('/eml_token=([^&]*)/', $url, $matches, PREG_SET_ORDER, 0);
    echo file_get_contents('https://my.tdfort.ru/launchers/mail_unsubscribe.php?eml_token=' . ($matches[0][1] ?? 'dGRmb3J0XzA='));
} else {
    if (!empty($_GET)) {
        $url = $url_base64;
    }

    file_get_contents('https://my.tdfort.ru/launchers/mail_link.php?email=' . $_GET['email']);

    if (strpos($url, 'tdfort.ru/') === false) {
//        $url = 'https://tdfort.ru/'. $url;
        $url = 'https://tdfort.ru/';
    }

    echo '
                please wait...........
                <script>
                    window.location.replace(\'' . $url . '\');
                </script>
            ';
}

echo '</body>
</html>';
