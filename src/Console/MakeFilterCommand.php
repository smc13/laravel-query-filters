<?php

namespace Smcassar\LaravelQueryFilters\Console;

use Illuminate\Console\GeneratorCommand;

class MakeFilterCommand extends GeneratorCommand
{
    protected $signature = 'make:query-filter {name}';

    protected $description = 'Create a new query filter class';

    protected $type = 'Query filter';

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Filters';
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/query_filter.stub';
    }
}
