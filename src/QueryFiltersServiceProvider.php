<?php

namespace SMCassar\LaravelQueryFilters;

use Illuminate\Support\ServiceProvider;
use SMCassar\LaravelQueryFilters\Console\MakeFilterCommand;

class QueryFiltersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([MakeFilterCommand::class]);
        }
    }
}
