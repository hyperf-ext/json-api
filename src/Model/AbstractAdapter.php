<?php

declare(strict_types=1);
/**
 * Copyright (c) 2020 Cloud Creativity Limited
 * Modifications copyright (c) 2021 Eric Zhu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This file has been modified to add support for Hyperf framework.
 */
namespace HyperfExt\JsonApi\Model;

use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations;
use Hyperf\Database\Model\Scope;
use Hyperf\Utils\Collection;
use HyperfExt\JsonApi\Adapter\AbstractResourceAdapter;
use HyperfExt\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use HyperfExt\JsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PageInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PagingStrategyInterface;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use HyperfExt\JsonApi\Document\ResourceObject;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

abstract class AbstractAdapter extends AbstractResourceAdapter
{
    use Concerns\DeserializesAttributes;
    use Concerns\FiltersModels;
    use Concerns\IncludesModels;
    use Concerns\SortsModels;

    protected Model $model;

    protected ?PagingStrategyInterface $paging = null;

    /**
     * The model key that is the primary key for the resource id.
     *
     * If empty, defaults to `Model::getRouteKeyName()`.
     */
    protected ?string $primaryKey = null;

    /**
     * The filter param for a find-many request.
     *
     * If null, defaults to the JSON API keyword `id`.
     */
    protected ?string $findManyFilter = null;

    /**
     * The default pagination to use if no page parameters have been provided.
     *
     * If your resource must always be paginated, use this to return the default
     * pagination variables... e.g. `['number' => 1]` for page 1.
     *
     * If this property is null or an empty array, then no pagination will be
     * used if no page parameters have been provided (i.e. every resource
     * will be returned).
     */
    protected ?array $defaultPagination = null;

    private array $scopes = [];

    public function __construct(Model $model, PagingStrategyInterface $paging = null)
    {
        $this->model = $model;
        $this->paging = $paging;
    }

    public function query(EncodingParametersInterface $parameters)
    {
        $parameters = $this->getQueryParameters($parameters);

        return $this->queryAllOrOne($this->newQuery(), $parameters);
    }

    /**
     * Query the resource when it appears in a to-many relation of a parent resource.
     *
     * For example, a request to `/posts/1/comments` will invoke this method on the
     * comments adapter.
     *
     * @param Builder|Relations\BelongsToMany|Relations\HasMany|Relations\HasManyThrough $relation
     * @return mixed
     * @todo default pagination causes a problem with polymorphic relations??
     */
    public function queryToMany($relation, EncodingParametersInterface $parameters)
    {
        $this->applyScopes(
            $query = $relation->newQuery()
        );

        return $this->queryAllOrOne(
            $query,
            $this->getQueryParameters($parameters)
        );
    }

    /**
     * Query the resource when it appears in a to-one relation of a parent resource.
     *
     * For example, a request to `/posts/1/author` will invoke this method on the
     * user adapter when the author relation returns a `users` resource.
     *
     * @param Builder|Relations\BelongsTo|Relations\HasOne $relation
     * @return mixed
     */
    public function queryToOne($relation, EncodingParametersInterface $parameters)
    {
        $this->applyScopes(
            $query = $relation->newQuery()
        );

        return $this->queryOne(
            $query,
            $this->getQueryParameters($parameters)
        );
    }

    public function read($record, EncodingParametersInterface $parameters)
    {
        $parameters = $this->getQueryParameters($parameters);

        if (! empty($parameters->getFilteringParameters())) {
            $record = $this->readWithFilters($record, $parameters);
        }

        if ($record) {
            $this->load($record, $parameters);
        }

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function update($record, array $document, EncodingParametersInterface $parameters)
    {
        $parameters = $this->getQueryParameters($parameters);

        /** @var Model $record */
        $record = parent::update($record, $document, $parameters);
        $this->load($record, $parameters);

        return $record;
    }

    public function exists(string $resourceId): bool
    {
        return $this->findQuery($resourceId)->exists();
    }

    public function find(string $resourceId)
    {
        return $this->findQuery($resourceId)->first();
    }

    public function findMany(iterable $resourceIds): iterable
    {
        return $this->findManyQuery($resourceIds)->get()->all();
    }

    /**
     * Add scopes.
     *
     * @param Scope ...$scopes
     * @return $this
     */
    public function addScopes(Scope ...$scopes): self
    {
        foreach ($scopes as $scope) {
            $this->scopes[get_class($scope)] = $scope;
        }

        return $this;
    }

    /**
     * Add a global scope using a closure.
     *
     * @return $this
     */
    public function addClosureScope(\Closure $scope, string $identifier = null): self
    {
        $identifier = $identifier ?: spl_object_hash($scope);

        $this->scopes[$identifier] = $scope;

        return $this;
    }

    /**
     * Apply the supplied filters to the builder instance.
     */
    abstract protected function filter(Builder $query, Collection $filters);

    /**
     * @param Builder $query
     */
    protected function applyScopes($query): void
    {
        /** @var Scope $scope */
        foreach ($this->scopes as $identifier => $scope) {
            $query->withGlobalScope($identifier, $scope);
        }
    }

    /**
     * Get a new query builder.
     *
     * Child classes can overload this method if they want to modify the query instance that
     * is used for every query the adapter does.
     *
     * @return Builder
     */
    protected function newQuery()
    {
        $this->applyScopes(
            $builder = $this->model->newQuery()
        );

        return $builder;
    }

    /**
     * @param $resourceId
     * @return Builder
     */
    protected function findQuery($resourceId)
    {
        return $this->newQuery()->where(
            $this->getQualifiedKeyName(),
            $this->databaseId($resourceId)
        );
    }

    /**
     * @return Builder
     */
    protected function findManyQuery(iterable $resourceIds)
    {
        return $this->newQuery()->whereIn(
            $this->getQualifiedKeyName(),
            $this->databaseIds($resourceIds)
        );
    }

    /**
     * Does the record match the supplied filters?
     *
     * @param Model $record
     * @return null|Model
     */
    protected function readWithFilters($record, EncodingParametersInterface $parameters)
    {
        $query = $this->newQuery()->whereKey($record->getKey());
        $this->applyFilters($query, collect($parameters->getFilteringParameters()));

        return $query->exists() ? $record : null;
    }

    /**
     * Apply filters to the provided query parameter.
     *
     * @param Builder $query
     */
    protected function applyFilters($query, Collection $filters)
    {
        /*
         * By default we support the `id` filter. If we use the filter,
         * we remove it so that it is not re-used by the `filter` method.
         */
        if ($this->isFindMany($filters)) {
            $this->filterByIds($query, $filters);
        }

        /* Hook for custom filters. */
        $this->filter($query, $filters);
    }

    protected function createRecord(ResourceObject $resource)
    {
        return $this->model->newInstance();
    }

    protected function destroy($record): bool
    {
        /* @var Model $record */
        return (bool) $record->delete();
    }

    /**
     * {@inheritdoc}
     */
    protected function fillRelationship(
        $record,
        $field,
        array $relationship,
        EncodingParametersInterface $parameters
    ) {
        $relation = $this->getRelated($field);

        if (! $this->requiresPrimaryRecordPersistence($relation)) {
            $relation->update($record, $relationship, $parameters);
        }
    }

    /**
     * Hydrate related models after the primary record has been persisted.
     *
     * @param Model $record
     */
    protected function fillRelated(
        $record,
        ResourceObject $resource,
        EncodingParametersInterface $parameters
    ) {
        $relationships = $resource->getRelationships();
        $changed = false;

        foreach ($relationships as $field => $value) {
            /* Skip any fields that are not fillable. */
            if ($this->isNotFillable($field, $record)) {
                continue;
            }

            /* Skip any fields that are not relations */
            if (! $this->isRelation($field)) {
                continue;
            }

            $relation = $this->getRelated($field);

            if ($this->requiresPrimaryRecordPersistence($relation)) {
                $relation->update($record, $value, $parameters);
                $changed = true;
            }
        }

        /* If there are changes, we need to refresh the model in-case the relationship has been cached. */
        if ($changed) {
            $record->refresh();
        }
    }

    /**
     * Does the relationship need to be hydrated after the primary record has been persisted?
     *
     * @return bool
     */
    protected function requiresPrimaryRecordPersistence(RelationshipAdapterInterface $relation)
    {
        return $relation instanceof HasManyAdapterInterface || $relation instanceof HasOne;
    }

    /**
     * {@inheritdoc}
     */
    protected function persist($record): ?AsynchronousProcess
    {
        $record->save();
        return null;
    }

    /**
     * @param $query
     */
    protected function filterByIds($query, Collection $filters)
    {
        $query->whereIn(
            $this->getQualifiedKeyName(),
            $this->extractIds($filters)
        );
    }

    /**
     * Return the result for a search one query.
     *
     * @param Builder $query
     * @return Model
     */
    protected function searchOne($query)
    {
        return $query->first();
    }

    /**
     * Return the result for query that is not paginated.
     *
     * @param Builder $query
     * @return mixed
     */
    protected function searchAll($query)
    {
        return $query->get();
    }

    /**
     * Is this a search for a singleton resource?
     *
     * @return bool
     */
    protected function isSearchOne(Collection $filters)
    {
        return false;
    }

    /**
     * Return the result for a paginated query.
     *
     * @param Builder $query
     * @return PageInterface
     */
    protected function paginate($query, EncodingParametersInterface $parameters)
    {
        if (! $this->paging) {
            throw new RuntimeException('Paging is not supported on adapter: ' . get_class($this));
        }

        /*
         * Set the key name on the strategy, so it knows what column is being used
         * for the resource's ID.
         *
         * @todo 2.0 add `withQualifiedKeyName` to the paging strategy interface.
         */
        if (method_exists($this->paging, 'withQualifiedKeyName')) {
            $this->paging->withQualifiedKeyName($this->getQualifiedKeyName());
        }

        return $this->paging->paginate($query, $parameters);
    }

    /**
     * Get the key that is used for the resource ID.
     *
     * @return string
     */
    protected function getKeyName()
    {
        return $this->primaryKey ?: $this->model->getRouteKeyName();
    }

    /**
     * @return string
     */
    protected function getQualifiedKeyName()
    {
        return $this->model->qualifyColumn($this->getKeyName());
    }

    /**
     * Get pagination parameters to use when the client has not provided paging parameters.
     *
     * @return array
     */
    protected function defaultPagination()
    {
        return (array) $this->defaultPagination;
    }

    /**
     * @param null|string $modelKey
     * @return BelongsTo
     */
    protected function belongsTo($modelKey = null)
    {
        return new BelongsTo($modelKey ?: $this->guessRelation());
    }

    /**
     * @param null|string $modelKey
     * @return HasOne
     */
    protected function hasOne($modelKey = null)
    {
        return new HasOne($modelKey ?: $this->guessRelation());
    }

    /**
     * @param null|string $modelKey
     * @return HasOneThrough
     */
    protected function hasOneThrough($modelKey = null)
    {
        return new HasOneThrough($modelKey ?: $this->guessRelation());
    }

    /**
     * @param null|string $modelKey
     * @return HasMany
     */
    protected function hasMany($modelKey = null)
    {
        return new HasMany($modelKey ?: $this->guessRelation());
    }

    /**
     * @param null|string $modelKey
     * @return HasManyThrough
     */
    protected function hasManyThrough($modelKey = null)
    {
        return new HasManyThrough($modelKey ?: $this->guessRelation());
    }

    /**
     * @param HasManyAdapterInterface ...$adapters
     * @return MorphHasMany
     */
    protected function morphMany(HasManyAdapterInterface ...$adapters)
    {
        return new MorphHasMany(...$adapters);
    }

    /**
     * @param \Closure $factory
     *                          a factory that creates a new Eloquent query builder
     * @return QueriesMany
     */
    protected function queriesMany(\Closure $factory)
    {
        return new QueriesMany($factory);
    }

    /**
     * @return QueriesOne
     */
    protected function queriesOne(\Closure $factory)
    {
        return new QueriesOne($factory);
    }

    /**
     * Default query execution used when querying records or relations.
     *
     * @param $query
     * @return mixed
     */
    protected function queryAllOrOne($query, EncodingParametersInterface $parameters)
    {
        $filters = collect($parameters->getFilteringParameters());

        if ($this->isSearchOne($filters)) {
            return $this->queryOne($query, $parameters);
        }

        return $this->queryAll($query, $parameters);
    }

    /**
     * @param $query
     * @return mixed|PageInterface
     */
    protected function queryAll($query, EncodingParametersInterface $parameters)
    {
        /* Apply eager loading */
        $this->with($query, $parameters);

        /* Filter */
        $this->applyFilters($query, collect($parameters->getFilteringParameters()));

        /* Sort */
        $this->sort($query, $parameters->getSortParameters());

        /** Paginate results if needed. */
        $pagination = collect($parameters->getPaginationParameters());

        return $pagination->isEmpty() ?
            $this->searchAll($query) :
            $this->paginate($query, $parameters);
    }

    /**
     * @param $query
     * @return Model
     */
    protected function queryOne($query, EncodingParametersInterface $parameters)
    {
        $parameters = $this->getQueryParameters($parameters);

        /* Apply eager loading */
        $this->with($query, $parameters);

        /* Filter */
        $this->applyFilters($query, collect($parameters->getFilteringParameters()));

        /* Sort */
        $this->sort($query, $parameters->getSortParameters());

        return $this->searchOne($query);
    }

    /**
     * Get JSON API parameters to use when constructing an Eloquent query.
     *
     * This method is used to push in any default parameter values that should
     * be used if the client has not provided any.
     *
     * @return EncodingParametersInterface
     */
    protected function getQueryParameters(EncodingParametersInterface $parameters)
    {
        return new EncodingParameters(
            $parameters->getIncludePaths(),
            $parameters->getFieldSets(),
            $parameters->getSortParameters() ?: $this->defaultSort(),
            $parameters->getPaginationParameters() ?: $this->defaultPagination(),
            $parameters->getFilteringParameters(),
            $parameters->getUnrecognizedParameters()
        );
    }

    /**
     * @return string
     */
    private function guessRelation()
    {
        [$one, $two, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $this->modelRelationForField($caller['function']);
    }
}
