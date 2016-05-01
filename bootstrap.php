<?php

define('BASE_PATH', __DIR__);

// Autoload 自动载入
require BASE_PATH.'/vendor/autoload.php';

//env
try {
    (new Dotenv\Dotenv(__DIR__))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

$app = new \App\Common\Application();

$app->withEloquent(require BASE_PATH.'/config/database.php');

// Eloquent ORM

/*$capsule = new Capsule;

$capsule->addConnection(require BASE_PATH.'/config/database.php');

$capsule->bootEloquent();*/

// whoops 错误提示

$whoops = new \Whoops\Run;

$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);

$whoops->register();



// 自定义　异常处理

/*$level = env('DEBUG') ? (E_ALL ^ E_NOTICE ^ E_STRICT) : (E_ERROR | E_WARNING | E_PARSE);
error_reporting($level);

set_exception_handler(['App\Common\Exception', 'exceptionHandler']);

if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
    set_error_handler(['App\Common\Exception', 'errorHandler'], $level);
}
unset($level);

// 注册关闭执行函数
register_shutdown_function('App\Common\Exception::shutdown');*/

// 路由配置
require BASE_PATH.'/app/routes.php';

return $app;