<?php

namespace SmartThings;
use Illuminate\Support\ServiceProvider;

class smartThingsServiceProvider extends ServiceProvider {
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton('smartThingsAPI', SmartThings\SmartThingsAPI::class);
    }
}


?>