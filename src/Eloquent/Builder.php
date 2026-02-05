<?php

namespace HughCube\Laravel\OTS\Eloquent;

use HughCube\Laravel\OTS\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @property QueryBuilder $query
 *
 * @method QueryBuilder getQuery()
 * @method Model getModel()
 */
class Builder extends EloquentBuilder
{
    /**
     * Use search index for query.
     *
     * @param string $indexName
     *
     * @return $this
     */
    public function useSearchIndex($indexName)
    {
        $this->query->useSearchIndex($indexName);

        return $this;
    }

    /**
     * Set primary keys for query.
     *
     * @param array $keys
     *
     * @return $this
     */
    public function primaryKey(array $keys)
    {
        $this->query->primaryKey($keys);

        return $this;
    }

    /**
     * Enable async mode.
     *
     * @param bool $async
     *
     * @return $this
     */
    public function async($async = true)
    {
        $this->query->async($async);

        return $this;
    }

    /**
     * Set range query direction to forward.
     *
     * @return $this
     */
    public function forward()
    {
        $this->query->forward();

        return $this;
    }

    /**
     * Set range query direction to backward.
     *
     * @return $this
     */
    public function backward()
    {
        $this->query->backward();

        return $this;
    }

    /**
     * Set range query start key.
     *
     * @param array $key
     *
     * @return $this
     */
    public function startKey(array $key)
    {
        $this->query->startKey($key);

        return $this;
    }

    /**
     * Set range query end key.
     *
     * @param array $key
     *
     * @return $this
     */
    public function endKey(array $key)
    {
        $this->query->endKey($key);

        return $this;
    }

    /**
     * Expect row to exist.
     *
     * @return $this
     */
    public function expectExist()
    {
        $this->query->expectExist();

        return $this;
    }

    /**
     * Expect row not to exist.
     *
     * @return $this
     */
    public function expectNotExist()
    {
        $this->query->expectNotExist();

        return $this;
    }

    /**
     * Add a term search query.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function searchTerm($column, $value)
    {
        $this->query->searchTerm($column, $value);

        return $this;
    }

    /**
     * Add a terms search query (IN).
     *
     * @param string $column
     * @param array $values
     *
     * @return $this
     */
    public function searchTerms($column, array $values)
    {
        $this->query->searchTerms($column, $values);

        return $this;
    }

    /**
     * Add a match search query.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function searchMatch($column, $value)
    {
        $this->query->searchMatch($column, $value);

        return $this;
    }

    /**
     * Add a match phrase search query.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function searchMatchPhrase($column, $value)
    {
        $this->query->searchMatchPhrase($column, $value);

        return $this;
    }

    /**
     * Add a prefix search query.
     *
     * @param string $column
     * @param string $prefix
     *
     * @return $this
     */
    public function searchPrefix($column, $prefix)
    {
        $this->query->searchPrefix($column, $prefix);

        return $this;
    }

    /**
     * Add a range search query.
     *
     * @param string $column
     * @param mixed $from
     * @param mixed $to
     * @param bool $includeFrom
     * @param bool $includeTo
     *
     * @return $this
     */
    public function searchRange($column, $from = null, $to = null, $includeFrom = true, $includeTo = false)
    {
        $this->query->searchRange($column, $from, $to, $includeFrom, $includeTo);

        return $this;
    }

    /**
     * Add a wildcard search query.
     *
     * @param string $column
     * @param string $pattern
     *
     * @return $this
     */
    public function searchWildcard($column, $pattern)
    {
        $this->query->searchWildcard($column, $pattern);

        return $this;
    }

    /**
     * Add a match all search query.
     *
     * @return $this
     */
    public function searchMatchAll()
    {
        $this->query->searchMatchAll();

        return $this;
    }

    /**
     * Execute a search query using search index.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchQuery()
    {
        $results = $this->query->searchQuery();

        return $this->hydrateModels($results->all());
    }

    /**
     * Get rows by range.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRange()
    {
        $results = $this->query->getRange();

        return $this->hydrateModels($results->all());
    }

    /**
     * Execute SQL query.
     *
     * @param string $query
     *
     * @return array
     */
    public function sql($query)
    {
        return $this->query->sql($query);
    }

    /**
     * Async SQL query.
     *
     * @param string $query
     *
     * @return \HughCube\Laravel\OTS\OTS\Handlers\RequestContext
     */
    public function asyncSql($query)
    {
        return $this->query->asyncSql($query);
    }

    /**
     * Execute SQL query built from builder state.
     *
     * @return array
     */
    public function sqlGet()
    {
        return $this->query->sqlGet();
    }

    /**
     * Hydrate models from raw arrays.
     *
     * @param array $items
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hydrateModels(array $items)
    {
        $instance = $this->newModelInstance();

        return $instance->newCollection(array_map(function ($item) use ($instance) {
            $model = $instance->newInstance([], true);
            $model->setRawAttributes((array) $item, true);

            return $model;
        }, $items));
    }
}
