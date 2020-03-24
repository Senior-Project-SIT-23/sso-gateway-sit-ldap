<?php

namespace App\Repositories;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind(
            'App\Repositories\StudentRepositoryInterface',
            'App\Repositories\StudentRepository'
        );
    }
}
