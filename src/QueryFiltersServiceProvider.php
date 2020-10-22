<?php

namespace Smcassar\LaravelQueryFilters;

use Illuminate\Support\ServiceProvider;
use Smcassar\LaravelQueryFilters\Console\MakeFilterCommand;

class QueryFiltersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([MakeFilterCommand::class]);
        }
    }
}
