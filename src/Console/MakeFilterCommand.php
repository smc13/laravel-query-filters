<?php

namespace Smcassar\LaravelQueryFilters\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class MakeFilterCommand extends GeneratorCommand
{
    protected $name = 'make:filter';

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

    protected function buildClass($name)
    {
        $namespaceModel = $this->option('model')
                        ? $this->qualifyModel($this->option('model'))
                        : $this->qualifyModel($this->guessModelName($name));

        $model = class_basename($namespaceModel);

        if (Str::startsWith($namespaceModel, 'App\\Models')) {
            $namespace = Str::beforeLast('App\\Http\\Filters\\' . Str::after($namespaceModel, 'App\\Models\\'), '\\');
        } else {
            $namespace = 'App\\Http\\Filters';
        }

        $replace = [
            '{{ filterNamespace }}' => $namespace,
            'NamespacedDummyModel'  => $namespaceModel,
            '{{ namespacedModel }}' => $namespaceModel,
            '{{namespacedModel}}'   => $namespaceModel,
            'DummyModel'            => $model,
            '{{ model }}'           => $model,
            '{{model}}'             => $model,
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    protected function guessModelName($name)
    {
        if (Str::endsWith($name, 'QueryFilter')) {
            $name = substr($name, 0, -11);
        }

        $modelName = $this->qualifyModel(class_basename($name));

        if (class_exists($modelName)) {
            return $modelName;
        }

        if (is_dir(app_path('Models/'))) {
            return 'App\Models\Model';
        }

        return 'App\Model';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
        ];
    }
}
