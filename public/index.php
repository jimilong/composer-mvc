<?php

define('PUBLIC_PATH', __DIR__);

//启动器
$app = require PUBLIC_PATH.'/../bootstrap.php';

$app->run();