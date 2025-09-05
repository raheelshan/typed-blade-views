<?php

namespace RaheelShan\TypedView;

use App\Support\TypedViewFactory;
use Illuminate\Support\ServiceProvider;

class TypedViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend('view', function ($factory, $app) {
            return new TypedViewFactory(
                $app['view.engine.resolver'],
                $app['view.finder'],
                $app['events']
            );
        });
    }
}
