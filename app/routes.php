<?php

use NoahBuscher\Macaw\Macaw;
use Whoops\Exception;

Macaw::get('fuck', function() {
    echo "成功！";
});

Macaw::get('home', 'App\Controllers\HomeController@home');

Macaw::get('(:all)', function($fu) {
    echo '未匹配到路由<br>'.$fu;
});

Macaw::$error_callback = function() {
    throw new \Exception("路由无匹配项 404 Not Found");
};

Macaw::dispatch();
exit;

/*Macaw::get('(:all)', function($fu) {
    echo '未匹配到路由<br>'.$fu;
});*/

/*Macaw::$error_callback = function() {
    throw new \Exception("路由无匹配项 404 Not Found");
};*/

/*try {
    if (file_exists('111.text')) {
        readfile('111.text');
    } else {
        throw new \Exception('111.text not found');
    }

   // Macaw::dispatch();
} catch (\Exception $e) {
    echo $e->getMessage();
}*/

