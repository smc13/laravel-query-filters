<?php

namespace Smcassar\LaravelQueryFilters\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Smcassar\LaravelQueryFilters\Exceptions\MissingModelException;
use Throwable;

class BaseQueryFilter
{
    use ForwardsCalls;

    protected Builder $query;

    protected Collection $requestData;

    /* The model to use for the base query */
    protected static ?string $model = null;

    protected array $defaults = [];

    protected array $skipFilters = [];

    protected array $sortableColumns = [];

    /**
     * Create a new query with filters and sorting applied
     * @param array|null $defaults
     * @return static
     * @throws Throwable
     */
    public static function newQuery()
    {
        throw_if(is_null(static::$model), MissingModelException::class);

        $query = call_user_func([static::$model, 'query']);
        return resolve(static::class, ['query' => $query])->apply();
    }

    public static function fromQuery(Builder $query)
    {
        return resolve(static::class, [$query]);
    }

    public function __construct(Builder $query = null, Request $request)
    {
        $this->query = $query;
        $this->requestData = Collection::make($request->all());
    }

    public function __call($name, $arguments)
    {
        return $this->forwardCallTo($this->query, $name, $arguments);
    }

    /**
     * Get the base query
     * @return null|Builder
     */
    public function query(): ?Builder
    {
        return $this->query;
    }

    /**
     * Set a new base query
     * @param Builder $query
     * @return $this
     */
    public function setQuery(Builder $query)
    {
        $this->query = $query;

        return $this;
    }

    public function requestData(array $data)
    {
        $this->requestData = Collection::make($data);

        return $this;
    }

    /**
     * Set the default parameters to be merged with the request data
     * @param array $defaults
     * @return $this
     */
    public function defaults(array $defaults)
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * Skip certain filters for the request
     * @param array $skip
     * @return $this
     */
    public function skip(array $skip)
    {
        $this->skipFilters = $skip;

        return $this;
    }

    /**
     * Apply query filters to the current query builder object
     * @return $this
     */
    public function apply()
    {
        $this->getFilters()
            ->mapWithKeys(fn ($value, $filter) => [$this->resolveFilterMethod($filter) => $value])
            ->filter(fn ($val, $filter) => method_exists($this, $filter) && !in_array($filter, $this->skipFilters))
            ->each(fn ($value, $filter) => call_user_func_array([$this, $filter], [$value]));

        return $this;
    }

    /**
     * Transform the column name for sorting
     * @param string $column
     * @return string
     */
    protected function transformColumnName(string $name): string
    {
        return Str::snake($name);
    }

    /**
     * Get the filters to be applied to the query
     * @return Collection
     */
    protected function getFilters()
    {
        return Collection::make($this->defaults)->merge($this->requestData);
    }

    /**
     * Resolve the method name to call for the parameter
     * @param string $name Name of the request param
     * @return string
     */
    protected function resolveFilterMethod(string $name): string
    {
        switch ($name) {
            case 'sort':
            case 'order':
                return 'sort';
            case 'with':
            case 'include':
                return 'with';
            default:
                return sprintf('filter%s', Str::studly(str_replace('.', ' ', $name)));
        }
    }

    protected function sort($value)
    {
        if (is_array($value)) {
            return $this->applyArraySort($value);
        }

        return $this->applyStringSort($value);
    }

    /**
     * Apply a sort to the query
     * @param string $column Name of the column
     * @param bool $desc Wether the column is descending
     * @return void
     */
    protected function applySort(string $column, bool $desc)
    {
        $sortMethod = sprintf('sort%s', Str::studly($column));
        $dir = $desc ? 'desc' : 'asc';
        if (method_exists($this, $sortMethod)) {
            return call_user_func_array([$this, $sortMethod], [$dir]);
        }

        $column = $this->transformColumnName($column);
        $column = $this->sortableColumns[$column] ?? $column;

        if (in_array($column, $this->sortableColumns)) {
            $this->query->orderBy($column, $dir);
        }
    }

    /**
     * Apply a list of sorts to the query
     * @param array $sorts
     * @return void
     */
    protected function applyArraySort(array $sorts)
    {
        foreach ($sorts as $sort) {
            if (isset($sort['column'])) {
                $desc = filter_var($sort['desc'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $this->applySort($sort['column'], $desc);
            }
        }
    }

    /**
     * Apply a string of sorts to the query
     * @param string $sorts
     * @return void
     */
    protected function applyStringSort(string $sorts)
    {
        $sorts = explode(',', $sorts);

        $items = Collection::make($sorts)
            ->map(function ($sort) {
                $parts = explode('|', $sort, 2);
                if ($parts[0] ?? false) {
                    return ['column' => $parts[0], 'desc' => strtolower($parts[1] ?? '') === 'desc'];
                }

                return false;
            })
            ->filter()
            ->toArray();

        return $this->applyArraySort($items);
    }

    /**
     * Add a list of relationships to eager load with the query
     * @param string|array $value
     * @return void
     */
    protected function with($value)
    {
        if (!is_array($value)) {
            $value = explode(',', $value);
        }

        foreach ($value as $relation) {
            $sub = explode('.', $relation);
            $main = array_shift($relation);

            $method = sprintf('with%s', Str::studly($main));
            if (method_exists($this, $method)) {
                call_user_func_array([$this, $method], [$sub]);
            }
        }
    }
}
