<?php 

namespace App\Common;

use App\Common\Response;

class Exception extends \Exception
{
    // 显示上下行数
    private static $padding = 6,
                   $status  = 500,
                   $trace   = true;

    /**
     * 实例化一个异常
     * 
     * @param  string  $message
     * @param  integer $code
     * @param  integer $status
     * @param  boolean $trace
     * @return void
     */
    public function __construct($message, $code = 0, $status = 500, $trace = true)
    {
        // status code
        self::$status = $status;
        self::$trace  = $trace;

        // parent
        parent::__construct($message, (int) $code);

        // Save the unmodified code
        $this->code = $code;
    }

    /**
     * 异常处理函数
     * 
     * @param  Exception $e
     * @return void
     */
    public static function exceptionHandler($e)
    {
        self::handler($e);

        exit(1);
    }

    /**
     * 错误处理函数
     * 
     * @param  intval $code
     * @param  string $error
     * @param  string $file
     * @param  intval $line
     * @return boolean
     */
    public static function errorHandler($code, $error, $file = null, $line = null)
    {
        if (error_reporting() & $code) {
            throw new \ErrorException($error, 11001, $code, $file, $line);
        }

        return true;
    }

    /**
     * 处理异常/错误并显示
     * 
     * @param  Exception $e
     * @return void
     */
    public static function handler($e)
    {
        ob_get_level() and ob_clean();

        /*try {
            Log::error(self::text($e));

            $isapi = isApi();
            Response::output(self::trace($e, $isapi), $isapi ? 'json' : 'html', self::$status);
        }
        catch(Exception $e) {
            Response::output(self::text($e), 'text', 500);
        }

        Response::send();*/
        Response::output(self::text($e), 'text', 500);
        Response::send();
        exit(1);
    }

    /**
     * 异常信息转为字符串
     * 
     * @param  Exception $e
     * @return string
     */
    public static function text($e)
    {
        /*return sprintf(
            '%s [ %s ]: %s ~ %s [ %d ]',
            get_class($e), 
            $e->getCode(), 
            strip_tags($e->getMessage()),
            $e->getFile(), 
            $e->getLine()
        );*/
        echo get_class($e);
        echo '['.$e->getCode().']';
        echo ':';
        echo strip_tags($e->getMessage());
        echo '</br>';
        echo $e->getFile();
        echo '['.$e->getLine().']';
    }

    /**
     * @param $e
     * @param bool $outJson
     *
     * @return array|mixed|string
     */
    public static function trace($e, $outJson = false)
    {
        /*$class   = get_class($e);
        $code    = $e->getCode();
        $message = $e->getMessage();
        $file    = $e->getFile();
        $line    = $e->getLine();

        $isCli = is_cli();
        if ($outJson or $isCli) {
            $data = [
                'status' => STATUS_ERR,
                'code'   => $code,
                'type'   => $class,
                'msg'    => DEBUG ? $message : '服务器开小差了,稍后再试吧.',
                'time'   => date('Y-m-d H:i:s')
            ];

            if ($isCli) {
                return $data;
            }

            return json_encode($data);
        }

        //  模板
        $tplpath  = APP_PATH. '/View/Exception/';
        $template = $tplpath. (DEBUG ? 'debug' : 'default'). '.html';
        $content  = DEBUG ? str_replace('{:br}', '<br>', htmlspecialchars($message)) : $code;

        $tokens = [
            '{error_code}'    => $code,
            '{error_type}'    => $class,
            '{error_message}' => $content,
            '{time}'          => strftime('%Y-%m-%d %H:%M:%S', time()),
            '{version}'       => 'Tiny/2.6'
        ];

        if (DEBUG) {
            $source = $trace = $include = '';

            if (self::$trace) {
                $lines = file($file);
                $range = [
                    'start' => max($line - self::$padding - 1, 0),
                    'end'   => min($line + self::$padding, count($lines))
                ];

                // 错误源码
                for ($i = $range['start']; $i < $range['end']; ++ $i) {
                    $source .= ($i === $line - 1) 
                        ?  "<div class=\"error\">". htmlspecialchars(sprintf("%04d: %s", $i + 1, str_replace("\t", '    ', $lines[$i]))). "</div>"
                        : htmlspecialchars(sprintf("%04d: %s", $i + 1, str_replace("\t", '    ', $lines[$i])));
                }
                $source = str_replace('{:br}', ' ', $source);

                // 已经加载的文件
                foreach (get_included_files() as $k => $f) {
                    $include .= sprintf("#%02d: %s\r\n", $k + 1, str_replace("\t", '    ', $f));
                }

                $file  = $file ? htmlspecialchars($file). ' ('. $line. ')' : '';
                $trace = htmlspecialchars($e->getTraceAsString());
            }
            else {
                $file = '';
            }

            // 合并
            $tokens = array_merge($tokens, [
                '{source_file}'   => $file,
                '{source_code}'   => $source,
                '{stack_trace}'   => $trace,
                '{include_files}' => $include,
                '{version}'       => $_SERVER['SERVER_SOFTWARE']. ' Tiny/2.6'
            ]);
        }

        $template = file_exists($template) ? file_get_contents($template) : '';

        // 替换变量
        $content  = strtr($template, $tokens);

        return $content;*/
    }

    public static function shutdown()
    {
        // 检查运行时是否有致命错误
        $error = error_get_last();
        if ($error !== null and ($error['type'] == 1 or $error['type'] == 4)) {
            ob_get_level() and ob_clean();

            self::handler(
                new \ErrorException(
                    $error['message'], 
                    $error['type'], 
                    0, 
                    $error['file'], 
                    $error['line']
                )
            );

            exit(1);
        }
    }
}
