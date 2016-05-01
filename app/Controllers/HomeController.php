<?php

namespace App\Controllers;

use App\Common\View;
use App\Models\Users;
use App\Common\Container;

class HomeController extends BaseController
{
    public function home()
    {
        $users = Users::find(1)->toArray();

        /*$container = new Container();
        $container->set('view', 'App\Common\View');

        $view = $container->get('view');
        $view->assign('test', $users);
        $view->display('test');*/
        return view('new.test', ['test' => $users]);
       // return view('test');
        //var_dump($test);
    }
}