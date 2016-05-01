<?php

namespace App\Common;

use NoahBuscher\Macaw\Macaw;

class Application extends Container
{
    /**
     * The base path of the application installation.
     *
     * @var string
     */
    protected $basePath;

    public function __construct()
    {
        //$this->basePath = $basePath;

        $this->bootstrapContainer();
        $this->registerEloquentBindings();
    }

    /**
     * Bootstrap the application container.
     *
     * @return void
     */
    protected function bootstrapContainer()
    {
        //$this->setShared('app', $this->basePath);
        //$this->make('app');
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerEloquentBindings()
    {
        $this->setShared('db', 'Illuminate\Database\Capsule\Manager');
    }

    /**
     * Load the Eloquent library for the application.
     *
     * @return void
     */
    public function withEloquent($config)
    {
        $db = $this->make('db');

        $db->addConnection($config);
        $db->bootEloquent();
    }

    /**
     * Register a route with the application.
     *
     * @param  string  $uri
     * @param  mixed  $action
     * @return $this
     */
    public function get($uri, $action)
    {
        Macaw::get($uri, $action);
    }

    /**
     * Register a route with the application.
     *
     * @param  string  $uri
     * @param  mixed  $action
     * @return $this
     */
    public function post($uri, $action)
    {
        Macaw::post($uri, $action);
    }

    protected function dispatch()
    {
        Macaw::$error_callback = function() {
            throw new \Exception("路由无匹配项 404 Not Found");
        };

        Macaw::dispatch();
    }

    /**
     * Run the application and send the response.
     *
     * @param
     * @return void
     */
    public function run()
    {
        $this->dispatch();
    }
}