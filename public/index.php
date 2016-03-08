<?php

define('PUBLIC_PATH', __DIR__);

//启动器
require PUBLIC_PATH.'/../bootstrap.php';

// 路由配置
require BASE_PATH.'/app/routes.php';