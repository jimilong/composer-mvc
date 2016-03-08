<?php

namespace App\Controllers;

use App\Models\Users;
use App\Common\View;

class HomeController extends BaseController
{
    public function home()
    {
        //$users = Users::find(1)->toArray();
        $test = env('TEMPLATE_PATH');
        $test = BASE_PATH.'１/'.$test.'/１3２';
        $tpl = new View();
        $tpl->assign('test', $test);
        $tpl->display('test');
        //var_dump($test);
    }
}