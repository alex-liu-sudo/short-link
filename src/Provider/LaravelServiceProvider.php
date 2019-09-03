<?php

namespace Alex\ShortLink\Provider;

use Alex\ShortLink\Client;
use Illuminate\Support\ServiceProvider;

class LaravelServiceProvider extends ServiceProvider
{
    /**
     * 是否延迟加载.
     *
     * @var bool
     */
//    protected $defer = false;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('short.link', function($app) {
            return new Client($app->config['short-link']);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../Config/short-link.php' => config_path('short-link.php')
        ], 'config');
    }
}
