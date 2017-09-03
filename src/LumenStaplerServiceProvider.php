<?php

namespace Expstudio\LumenStapler;

use Expstudio\LumenStapler\IlluminateConfig;
use Codesleeve\Stapler\Stapler;
use Expstudio\LumenStapler\Commands\FastenCommand;
use Expstudio\LumenStapler\Providers\ServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Config;

class LumenStaplerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    private function config_path($path = '')
    {
        return app()->getConfigurationPath(rtrim($path, ".php"));
    }

    private function public_path($path=null)
    {
            return rtrim(app()->basePath('public/'.$path), '/');
    }
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $packageRoot = dirname(__DIR__);

        // config
        $this->publishes([
            $packageRoot.'/src/config/filesystem.php' => $this->config_path('lumen-stapler/filesystem.php'),
            $packageRoot.'/src/config/s3.php' => $this->config_path('lumen-stapler/s3.php'),
            $packageRoot.'/src/config/stapler.php' => $this->config_path('lumen-stapler/stapler.php'),
            $packageRoot.'/src/config/bindings.php' => $this->config_path('lumen-stapler/bindings.php'),
        ]);

        $this->mergeConfigFrom($packageRoot.'/src/config/filesystem.php', 'lumen-stapler.filesystem');
        $this->mergeConfigFrom($packageRoot.'/src/config/s3.php', 'lumen-stapler.s3');
        $this->mergeConfigFrom($packageRoot.'/src/config/stapler.php', 'lumen-stapler.stapler');
        $this->mergeConfigFrom($packageRoot.'/src/config/bindings.php', 'lumen-stapler.bindings');

        // views
        $this->loadViewsFrom($packageRoot.'/src/views', 'lumen-stapler');

        $this->bootstrapStapler();
    }

    /**
     * Bootstrap up the stapler package:
     * - Boot stapler.
     * - Set the config driver.
     * - Set public_path config using lumen's public_path() method (if necessary).
     * - Set base_path config using lumen's base_path() method (if necessary).
     */
    protected function bootstrapStapler()
    {
        Stapler::boot();

        $config = new IlluminateConfig(Config::getFacadeRoot(), 'lumen-stapler', '.');
        Stapler::setConfigInstance($config);

        if (!$config->get('stapler.public_path')) {
            $config->set('stapler.public_path', realpath($this->public_path()));
        }

        if (!$config->get('stapler.base_path')) {
            $config->set('stapler.base_path', realpath(app()->basePath('')));
        }
    }

    /**
     * Register the stapler fasten command with the container.
     */
    protected function registerStaplerFastenCommand()
    {
        $this->app->bind('stapler.fasten', function ($app) {
            $migrationsFolderPath = base_path().'/database/migrations';

            return new FastenCommand($app['view'], $app['files'], $migrationsFolderPath);
        });
    }
}
