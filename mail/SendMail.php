<?php


class SendMail
{
    private $mailer;

    function __construct()
    {
        echo 'ok';
        $this->mailer = new PHPMailer();
    }

    public function setSMTPSetings($smtp_config)
    {
        $this->mailer->SMTPSecure = $smtp_config['secure'];
        $this->mailer->Host       = $smtp_config['host'];
        $this->mailer->Port       = $smtp_config['port'];
        $this->mailer->Username   = $smtp_config['login'];
        $this->mailer->Password   = $smtp_config['password'];
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->Encoding   = "Base64";

        $this->mailer->setFrom($smtp_config['email'], $smtp_config['name_from']);
//        $this->mailer->IsHTML(true);
        $this->mailer->IsSMTP();
        $this->mailer->SMTPDebug = 0;
        $this->mailer->SMTPAuth  = true;

        return $smtp_config['email'];
    }

    public function sendMail($data)
    {
        $data_default = [
            'files'      => [],
            'params'     => [],
            'headers'    => [],
            'proxy'      => [],
            'save_to'    => -1,
            'pattern_id' => false
        ];

        foreach ($data_default as $key => $value) {
            if (!isset($data[$key])) {
                $data[$key] = $value;
            }
        }

        $mail_to = $data['to'];
        $subject = $data['subject'];
        $message = $data['body'];
        $files   = $data['files'];
        $params  = $data['params'];
        $headers = $data['headers'];
        $proxy   = $data['proxy'];

        $subject = $this->decodeEntities($subject);
        $message = $this->decodeEntities($message);

        $this->mailer->Subject = $subject;
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($mail_to);

//        set_time_limit(60);

        if (!empty($files['files'])) {
            $files = $files['files'];
            for ($i = 0; $i < count($files['name']); $i++) {
                $this->mailer->addAttachment($files['tmp_name'][$i], $files['name'][$i]);
            }
        }

        // Вставка в письмо изображений из макроса рассылки
        // вставка файлов в письмо
        preg_match_all('/\[img\s*=\s*([\'"]?)([^\'"\]]+)\1\]/', $message, $images);

        if (!empty($images)) {
            for ($i = 0; $i < count($images[0]); $i++) {
                $message = str_replace($images[0][$i], 'cid:image_' . $i, $message);
                $this->mailer->AddEmbeddedImage($file_name = "/var/www/admin/data/www/" . $images[2][$i], 'image_' . $i);
            }
        }

//        $this->mailer->msgHTML($message);
        $this->mailer->Body = $message;
        $this->mailer->isHTML(true);

//        if (!empty($pattern_id)) { // TODO Не работает на удаленке
//            $files_p = $this->getFilesFromPattern($pattern_id);

//            foreach ($files_p as $file) {
//                $this->mailer->addAttachment($file['file_locate'], $file['name']);
//            }
//        }

        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                switch ($key) {
                    default: $this->mailer->addCustomHeader($key, $value); break;
                }
            }
        }

//        echo '<pre>';
//        echo __FILE__.' - '.__LINE__."\n";
//        var_dump($proxy);
//        echo '</pre>';

        if (!empty($proxy)) {
            $this->mailer->useProxy($proxy);
        }

        $answer = [];

        for ($i = 0; $i < 1; $i++) {
            $answer['success'] = (int) $this->mailer->Send();
            $answer['errors'] = '';

            if (empty($answer['success'])) {
                $answer['errors'] = $this->mailer->ErrorInfo;

                echo '-' . $answer['errors'] . '-';
            }
        }

        return $answer;
    }

    /**
     * Преобразование кодов спецсимволов в спецсимволы (&#128204;\&amd;#128204; в 📌)
     *
     * @param $text_in
     * @return string
     */
    private function decodeEntities($text_in)
    {
        $text_out = preg_replace_callback(
            "/(&[amp;]*#[0-9]+;)/",
            function($m) {
                return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
            },
            $text_in
        );

        return $text_out;
    }

}