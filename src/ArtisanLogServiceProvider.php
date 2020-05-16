<?php

namespace Sofa\ArtisanLog;

use Illuminate\Support\ServiceProvider;

class ArtisanLogServiceProvider extends ServiceProvider
{
    public function register()
    {
        $config = $this->app['config']->get('artisan_log');

        $channel = isset($config['log_channel']) && $config['log_channel'] !== 'default'
            ? $config['log_channel']
            : $this->app['config']->get('logging.default');

        $logger = isset($config['custom_logger'])
            ? $this->app->make($config['custom_logger'])
            : $this->app['log']->channel($channel);

        $this->app->bind(LogArtisan::class, fn () => new LogArtisan(
            $logger,
            $config['log_level'] ?? LogArtisan::DEFAULT_LEVEL,
            $config['formats'] ?? [],
            $config['ignored'] ?? [],
        ));
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/artisan_log.php' => $this->app->configPath('artisan_log.php'),
        ]);

        LogArtisan::subscribe();
    }
}
