<?php

namespace App\Controllers;

use App\Common\View;

class BaseController
{
    //protected $view = null;

    public function __construct()
    {
        //$this->view = View::getInstance();
    }

    /*protected function display($__template, $__output = true, $__status = 200)
    {
        $this->view->display($__template, $__output, $__status);
    }

    protected function assign($key = '', $val = null)
    {
        $this->view->assign($key, $val);
    }*/
}