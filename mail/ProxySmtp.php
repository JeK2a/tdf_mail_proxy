<?php

ini_set('max_execution_time', '90');
set_time_limit(40);

class ProxySmtp
{
    // PROXY CONNECT
    private $proxy;
    private $proxy_ip;
    private $proxy_port;
    private $proxy_login;
    private $proxy_password;
    private $proxy_type;

    // CURL SERVICE
    private $curl_requests = [];
    private $curl_connection;
    private $curl_file;
    private $curl_file_header;
    private $curl_file_log;
    private $curl_is_debag;
    private $curl_type;

    // SMTP CONNECT
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_password;
    private $smtp_timeout;
//    private $smtp_char_set;
//    private $smtp_secure;

    private $file_log_name = '';

    // EMAIL HEAD
    private $smtp_from;
    private $smtp_to = [];

    private $i = 0;
    private $db;

    private $curl_is_logs;

    public function __construct($smtp_params = [], $proxy_params = [], $curl_is_debag = 0)
    {
        $this->curl_is_debag = $curl_is_debag;

        $this->curl_is_debag = false;
        $this->curl_is_logs  = true;

        $this->smtp_to = $smtp_params['to'];

//        $this->curl_requests  = [];

//        $patch_proxy = '/var/www/admin/data/www/my/public_html/tmp/mailing/logs/';
        $patch_proxy = '';

        if ($this->curl_is_logs) {
            $date = date("y.m.d H:i:s");
            $this->file              = $patch_proxy . 'file '.$date.'.txt';
            $this->file_headers_name = $patch_proxy . 'headers '.$date.'.txt';
            $this->file_log_name     = $patch_proxy . 'log ' . $date . '.txt';

            $this->curl_file        = fopen($this->file, 'w+');
            $this->curl_file_header = fopen($this->file_headers_name, 'w');
            $this->curl_file_log    = fopen($this->file_log_name, 'w');
        }
    }

    public function getLine()
    {
//        $out = curl_getinfo($this->curl_connection)['http_code'];

        echo 'getLine<br>';

        $out = 250;

        return $out;
    }

    public function getNextQuery()
    {
        $resp = current($this->curl_requests);
        next($this->curl_requests);

        return $resp;
    }

    // QUERY
    public function curl_telnet($query)
    {
        $this->curl_requests[] = $query;

        if ($query == ".\r\n") {
            if (++$this->i > 1) {
//                die('END');
                return -1;
            }

            return $this->goQuery() ? -1 : -2; // -1 send and all ok  -2 error
        }

        return strlen($query);
    }

    public function goQuery()
    {
        if ($this->curl_is_debag) {
            echo '<pre>';
            print_r($this->curl_requests);
            echo '</pre>';
        }

        $this->setSmtpParams($_POST['smtp']);

        // Переработка тела сообщения один раз
        foreach ($this->curl_requests as $key => $value) {

//            if (stripos($value, 'PHPMailer')) {
//                unset($this->curl_requests[$key]);
//            }

            if (stripos($value, 'essage-ID:')) {
//                $this->curl_requests[$key] = str_replace('my.tdfort.ru', $this->getRandomString(rand(7, 20)), $value);
                $this->curl_requests[$key] = str_replace('my.tdfort.ru', explode('@', $this->smtp_user)[1], $value);
            }

//            if (stripos($value, 'X-Priority' !== false)) {
//                unset($this->curl_requests[$key]);
//            }

            if ($value == "DATA\r\n") {
                $start = $key + 1;
            }

            if ($value == ".\r\n") {
                $this->curl_requests = array_slice($this->curl_requests, $start, $key - $start);
            }
        }

        if ($this->curl_is_debag) {
            print_r($this->curl_requests);
        }

        $i = 1;

        do {
            // PROXY PARAMS
//            $proxy = $this->getRandomProxy();
            $proxy = json_decode($_POST['proxy'], true);

            if (empty($proxy)) {
//                die('Нет активных proxy');

                echo   'Нет активных proxy';

                return 'Нет активных proxy';
            }

            $this->proxy_type     = $proxy['type'];
            $this->proxy_ip       = $proxy['ip'];
            $this->proxy_port     = $proxy['port'];
            $this->proxy_login    = $proxy['user']     ?? '';
            $this->proxy_password = $proxy['password'] ?? '';
            $this->proxy          = $this->proxy_ip . ":" . $this->proxy_port;

            // PROXY

            $secure = 'smtp' . (empty($this->smtp_secure) ? '' : 's');
//            $secure = 'smtp';
            $curl = curl_init($secure . "://" . $this->smtp_host . ":" . $this->smtp_port);

            switch ($this->proxy_type) {
                case 'http'  :
                case 'HTTP'  : $this->proxy_type = CURLPROXY_HTTP;   break;
                case 'https' :
                case 'HTTPS' : $this->proxy_type = 2;                break;
                case 'socks4':
                case 'SOCKS4': $this->proxy_type = CURLPROXY_SOCKS4; break;
                case 'socks5':
                case 'SOCKS5': $this->proxy_type = CURLPROXY_SOCKS5; break;
                default      : $this->proxy_type = CURLPROXY_SOCKS5; break;
            }


            curl_setopt($curl, CURLOPT_PROXYTYPE, $this->proxy_type);
//            curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            curl_setopt($curl, CURLOPT_PROXY, $this->proxy_ip . ':' . $this->proxy_port);
//            curl_setopt($curl, CURLOPT_PROXY, 'proxy.torguard.org:1090');
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy_login . ':' . $this->proxy_password);
            curl_setopt($curl, CURLOPT_URL, $secure . "://" . $this->smtp_host . ":" . $this->smtp_port . "/" . explode('@', $this->smtp_user)[1]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

            curl_setopt($curl, CURLOPT_UPLOAD, 1);

            // LOG
            if ($this->curl_is_logs) {
                curl_setopt_array($curl, [
//                    CURLOPT_WRITEHEADER => $this->curl_file_header,
                    CURLOPT_VERBOSE     => True,
                    CURLOPT_STDERR      => $this->curl_file_log,
                    CURLOPT_FILE        => $this->curl_file,
                    CURLOPT_WRITEHEADER => $this->curl_file_header
                ]);
            }

            if (!empty($this->proxy_login) && !empty($this->proxy_password)) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy_login . ':' . $this->proxy_password);
            }
//
//        curl_setopt($this->curl_connection, CURLOPT_PROXYPORT, $this->proxy_port);
//        curl_setopt($this->curl_connection, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
//
//        curl_setopt($curl, CURLOPT_TIMEOUT,        $this->smtp_timeout);
//        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->smtp_timeout);

            // BODY
            curl_setopt($curl, CURLOPT_MAIL_FROM, "<" . $this->smtp_from . ">");
            curl_setopt($curl, CURLOPT_MAIL_RCPT, $this->smtp_to);
            curl_setopt($curl, CURLOPT_USERNAME, $this->smtp_user);
            curl_setopt($curl, CURLOPT_PASSWORD, $this->smtp_password);
            curl_setopt($curl, CURLOPT_USE_SSL, CURLUSESSL_ALL);
            curl_setopt($curl, CURLOPT_READFUNCTION, [$this, 'getNextQuery']);

            $time = time();
//            session_write_close(); //todo blea eto chetkaya tema. hren ugadaesh zachem ono nado

            curl_exec($curl);

            echo 'curl time: ' . (time() - $time);

            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            $log = $i . " - " . $code . " to " . $this->smtp_to[0] . " with proxy " . $this->proxy_ip . ' from ' . $this->smtp_user;

//            if ($code != 250) {
//                $this->setSmtpParams($_POST['smtp'] );
//            }
//        } while ($code != 250 && $i++ <= 3);
        } while (false);

        $log_file = '';

        if ($this->curl_is_logs) {
            fclose($this->curl_file_log);
            fclose($this->curl_file_header);
            fclose($this->curl_file);


            $log_file = file_get_contents($this->file_log_name);

//            unlink($this->file_log_name);
//            unlink($this->file_headers_name);
        }

        $mail_to = $this->smtp_to[0];

        switch ($code) {
            case 250: $answer = ' sent proxy'; $color  = 'green'; break;
            default:  $answer = ' error';      $color  = 'red';   break;
        }

        $log .= '<span style="color:' . $color . '">' . $answer . " (to " . $mail_to . " with proxy " . $this->proxy_ip . ' from ' . $this->smtp_user . ') </span>';

        if (isset($_POST['task_id'])) {
            $task_id = $_POST['task_id'];
        }

        echo ' Proxy ';
        echo $log;

        return true;
    }

//    private function getRandomProxy()
//    {
//        $this->db = new MySQLDatabase(self::$mysql_host, self::$mysql_user, self::$mysql_password, self::$mysql_db);
//
//        $query = '
//                SELECT
//                   `id`,
//                   `provider`,
//                   `type`,
//                   `ip`,
//                   `port`,
//                   `user`,
//                   `password`
//                FROM `' . self::$table_api_email_proxy . '`
//                WHERE
//                    `enabled` = 1
//                ORDER BY RAND()
//                LIMIT 1;';
//
//        return $this->db->query($query)[0] ?? false;
//    }

//    public function getSMTPConfigs($smtp_config_id = false)
//    {
//        $this->db = new MySQLDatabase(self::$mysql_host, self::$mysql_user, self::$mysql_password, self::$mysql_db);
//
//        $query = "
//            SELECT
//              `emails`.`id`,
//              `settings`.`host`,
//              `settings`.`port`,
//              `emails`.`email` AS `login`,
//              `emails`.`name_from`,
//              `emails`.`email`,
//              `emails`.`password`,
//              `settings`.`charset`,
//              `settings`.`secure`
//            FROM `" . self::$table_my_users_emails . "` AS `emails`
//            INNER JOIN " . self::$mysql_emails_settings . " AS `settings`
//                ON
//                     `emails`.`email_provider` = `settings`.`provider` AND
//                     `settings`.`type` = \"smtp\"
//             ";
//        if (empty($smtp_config_id)) {
//            $query .= '
//                ORDER BY `id` DESC;';
//        } elseif ($smtp_config_id == 1000) {
//            $query .= "
//                WHERE
//                    `use_for_mailing` = 1 AND
//                    `count_fail` < 5
//                ORDER BY RAND()
//                LIMIT 1;";
//        } else {
//            $query .= "
//                WHERE `id` = '" . $smtp_config_id . "'
//                LIMIT 1;";
//        }
//
//        $result = $this->db->query($query);
//
//        if (empty($result) || empty($result[0])) {
//            $result = false;
//        } elseif (count($result) == 1 && !empty($smtp_config_id)) {
//            $result = $result[0];
//        }
//
//        return $result;
//    }

    private function setSmtpParams($smtp_id)
    {
//        $smtp_params = $this->getSMTPConfigs($smtp_id);
        $smtp_params = json_decode($_POST['from'], true);

        if (empty($smtp_params)) {
            if ($this->curl_is_logs) {
                fclose($this->curl_file_log);
//                fclose($this->curl_file_header);
//                fclose($this->curl_file);
            }

            echo   'Нет почты для отправки';

            return 'Нет почты для отправки';
        }

        $this->smtp_host     = $smtp_params['host'];
        $this->smtp_port     = $smtp_params['port'];
        $this->smtp_user     = $smtp_params['email'];
        $this->smtp_password = $smtp_params['password'];
        $this->smtp_from     = $smtp_params['email'];    // TODO ?
//            $this->smtp_timeout  = $smtp_params['timeout'];
        $this->smtp_char_set = $smtp_params['charset'];
        $this->smtp_secure   = $smtp_params['secure'];

        return $smtp_params;
    }

    public static function getError($code, $text)
    {
        $regular = '/< ' . $code . '[-]*([^\n]+)/i';
        $error = '';

        preg_match_all($regular, $text, $matches, PREG_SET_ORDER, 0);

        foreach ($matches as $match) {
            $error .= $match[1];
        }

        $error = str_replace("\n", '', $error);

        return $error;
    }

}