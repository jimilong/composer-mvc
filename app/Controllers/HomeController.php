<?php

namespace App\Controllers;

use App\Models\Users;
use App\Common\View;

class HomeController extends BaseController
{
    public function home()
    {
        $users = Users::find(3)->toArray();
        $tpl = new View();
        $tpl->assign('test', $users);
        $tpl->display('new.test');
        //var_dump($test);
    }
}