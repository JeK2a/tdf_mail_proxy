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

    $data = [
        'id' => '1089363',
        'from' => [
//            'name' => 'Worker E.',
//            'id' => 248,
//            'email' => 'subscribe-1@tdf-service-1.online',
//            'login' => 'subscribe-1@tdf-service-1.online',
//            'password' => 'Td000000',
//            'use_for_mailing' => '1',
//            'name_from' => 'ТД Форт',
//            'email_provider' => 'spaceweb.ru',
//            'move_to' => '2006',
//            'secure' => 'ssl',
//            'host' => 'smtp.spaceweb.ru',
//            'port' => '465',
//            'charset' => 'utf-8',

            'name' => 'Максимов Е.',
            'id' => 55,
            'email' => 'me@tdfort.ru',
            'login' => 'me@tdfort.ru',
            'password' => 'Td292989',
            'use_for_mailing' => '0',
            'name_from' => '',
            'email_provider' => 'yandex.ru',
            'move_to' => '0',
            'secure' => 'ssl',
            'host' => 'smtp.yandex.ru',
            'port' => '465',
            'charset' => 'utf-8'
        ],

        'smtp_id'   => '248',
        'use_proxy' => '157',
        'to'        => 'jek2ka@gmail.com',
        'subject'   => 'Test',


        'proxy' =>
            [
                'id' => '157',
                'provider' => 'torguard',
                'type' => 'SOCKS5',
                'ip' => 'proxy.torguard.org',
//                'ip' => '194.59.250.226',
                'port' => '1090',
                'user' => 'bnd7z3c8282mamc',
                'password' => 'bM3gJE4CkkGjaLh'
            ],
        'body' => 'test
    test
    test'

    ];

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
