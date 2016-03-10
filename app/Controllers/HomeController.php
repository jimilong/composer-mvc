<?php

namespace App\Controllers;

use App\Models\Users;

class HomeController extends BaseController
{
    public function home()
    {
        $users = Users::find(3)->toArray();

        $this->assign('test', $users);
        $this->display('test');
        //var_dump($test);
    }
}