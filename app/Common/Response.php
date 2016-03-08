<?php

namespace App\Common;

class Response
{
    // HTTP 状态码和信息
    private static $message = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    ];

    /**
     * 需要输出的数据
     * 
     * @var array
     */
    protected static $output = [];

    /**
     * 输出
     * 
     * @param  string  $content
     * @param  string  $type
     * @param  integer $code
     * @return void
     */
    public static function output($content, $type = 'html', $code = 200)
    {
        self::$output[] = [$content, $type, $code];
    }

    /**
     * 输出数据
     * 
     * @return void
     */
    public static function send()
    {
        $code = 200;
        $type = 'html';
        if ( ! empty(self::$output)) {
            list(, $type, $code) = end(self::$output);
        }

        switch (strtolower($type)) {
            case 'text':
                $mimes = 'text/plain';
                break;
            case 'xml':
                $mimes = 'text/xml';
                break;
            case 'json':
                $mimes = 'application/json';
                break;
            default:
                $mimes = 'text/html';
                break;
        }

        if ( ! headers_sent()) {
            if ($code != 200) {
                self::cacheHeader(0);
                self::statusHeader($code);
            }

            header('Content-Type:'. $mimes. '; charset=uft-8');
            header('X-Powered-By:Tiny/2.6');
        }

        if ( ! empty(self::$output)) {
            foreach (self::$output as $v) {
                if ( ! empty($v[0])) {
                    echo $v[0];
                }
            }
        }

        ob_flush();
    }

    /**
     * 设置HTTP状态码
     * 
     * @param  integer $code
     * @param  string  $text
     * @return void
     */
    public static function statusHeader($code = 200, $text = '')
    {
        if ($code == '' or ! is_numeric($code)) {
            throw new \InvalidArgumentException('状态码必须是数字', 10201);
        }

        if (isset(self::$message[$code]) and $text == '') {
            $text = self::$message[$code];
        }

        if ($text == '') {
            throw new \Exception('未知的状态码：'. $code, 10201);
        }

        if (php_sapi_name() == 'cli' or defined('STDIN')) {
            header("Status: {$code} {$text}", true);
        }
        else {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : false;
            $protocol = ($protocol == 'HTTP/1.0') ? 'HTTP/1.0' : 'HTTP/1.1';

            header($protocol. " {$code} {$text}", true, $code);
        }
    }

    /**
     * 设置HTTP缓存
     * 
     * @param  integer $time
     * @return void
     */
    public static function cacheHeader($time = 86400)
    {
        if ($time > 0) {
            header('Cache-Control: max-age='. $time);
            header('Last-Modified: '. date( 'D, d M Y H:i:s \G\M\T' ));
            header('Expires: '. date('D, d M Y H:i:s \G\M\T', time() + $time));
            header('Pragma: cache');
        }
        else {
            header('Cache-Control: private, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header("Expires: 0");
        }
    }

    /**
     * HTTP跳转
     * 
     * @param  string  $url
     * @param  boolean $script
     * @param  string  $message
     * @param  intval  $status
     * @return void
     */
    public static function redirect($url = 'HTTP_REFERER', $message = null, $script = false, $status = 302)
    {
        if (strtoupper($url) == 'HTTP_REFERER') {
            $url = $_SERVER['HTTP_REFERER'];
        }

        if ( ! headers_sent() and ! $script and ! $message) {
            self::statusHeader($status);
            header('Location: '. $url);
            exit(0);
        }

        $content = '<!doctype html><html><head><meta charset="utf-8" /><script type="text/javascript">';
        if ($message != null) {
            $content .= 'alert("'. $message. '");';
        }
        $content .= 'window.location="'. $url. '";</script></head><body></body></html>';

        self::output($content, 'html', $status);
        self::send();
        exit(0);
    }

    /**
     * 返回前一页
     * 
     * @return void
     */
    public static function goBack($message = null)
    {
        self::redirect('HTTP_REFERER', $message);
    }
}
