<?php

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

$_POST['proxy'] = json_encode($data['proxy']);
$_POST['from']  = json_encode($data['from']);


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
//        $tmp  = urldecode(ob_get_clean());
$done = $result['success'] ? 1 : -1;

$url = 'https://my.tdfort.ru/launchers/mail_send.php?id=' . $id . '&logs=' . $tmp . '&done=' . $done;

//        file_get_contents($url);

echo $tmp;

return;
