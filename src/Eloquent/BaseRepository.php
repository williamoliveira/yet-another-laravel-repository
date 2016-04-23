<?php namespace Williamoliveira\Repository\Eloquent;

use Williamoliveira\Repository\Contracts\BaseRepository as BaseRepositoryInterface;
use Williamoliveira\Repository\Exceptions\NotFoundRepositoryException;
use Williamoliveira\Repository\Exceptions\UpdateFailedRepositoryException;
use Williamoliveira\Repository\Exceptions\StoreFailedRepositoryException;
use Williamoliveira\Repository\Exceptions\RepositoryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Williamoliveira\Repository\Contracts\Criteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ArrayAccess;
use Closure;

abstract class BaseRepository implements BaseRepositoryInterface
{

    /**
     * Query builder instance
     *
     * @var Builder
     */
    protected $query;

    /**
     * Default page size for pagination
     *
     * @var int
     */
    protected $defaultPageSize = 15;

    /**
     * The model's class name
     *
     * @var string
     */
    protected $modelClass;


    /**
     * Apply a query
     *
     * @param Criteria|Closure $criteria
     * @return mixed
     * @throws \Exception
     */
    public function applyCriteria($criteria)
    {
        $query = $this->getQuery();

        if($criteria instanceof Criteria){
            $criteria->apply($query);

            return $this;
        }

        if($criteria instanceof Closure){
            $criteria($query);

            return $this;
        }

        throw new RepositoryException("Must be an instance of " . Criteria::class . " or \\Closure");
    }

    /**
     * Find all models
     *
     * @param array $columns
     * @param bool $paginated
     * @param null $perPage
     * @return mixed
     */
    public function getMany($columns = ['*'], $paginated = false, $perPage = null)
    {
        $results = $paginated
            ? $this->getManyPaginated($perPage, $columns)
            : $this->getQuery()->get($columns);

        return $this->returnResults($results);
    }

    /**
     * Find all models but paginated
     * 
     * @param null $perPage
     * @param array $columns
     * @return mixed
     */
    public function getManyPaginated($perPage = null, $columns = ['*'])
    {
        $query = $this->getQuery();
        $results = $query->paginate($perPage, $columns);

        return $this->returnResults($results);
    }

    /**
     * Find a single model by it's id
     *
     * @param $id
     * @param array $columns
     * @return mixed
     * @throws NotFoundRepositoryException
     */
    public function getById($id, $columns = ['*'])
    {
        try {
            $results = $this->getQuery()->findOrFail($id, $columns);
        }
        catch(ModelNotFoundException $e){
            throw new NotFoundRepositoryException($e);
        }

        return $this->returnResults($results);
    }

    /**
     * Find many models given an array of ids
     *
     * @param array $ids
     * @param array $columns
     * @return mixed
     */
    public function getManyByIds(array $ids, $columns = ['*'])
    {
        $results = $this->getQuery()->findMany($ids, $columns);

        return $this->returnResults($results);
    }

    /**
     * Create and persist a model given an array of attributes
     * 
     * @param array $attributes
     * @return mixed
     * @throws \Exception
     */
    public function create(array $attributes = [])
    {
        $model = $this->newModel();
        $model->fill($attributes);

        $this->save($model);

        return $model;
    }

    /**
     * Persist a model
     *
     * @param Model $model
     * @return mixed
     * @throws StoreFailedRepositoryException
     */
    public function save($model)
    {
        if(!$model->save()){
            throw new StoreFailedRepositoryException();
        }

        return $model;
    }

    /**
     * Persist many models
     *
     * @param ArrayAccess $models
     * @return Collection
     * @throws StoreFailedRepositoryException
     */
    public function saveMany(ArrayAccess $models)
    {
        //transform to collection
        if(!$models instanceof Collection){
            $models = collect($models);
        }

        foreach ($models as $model) {
            $this->save($model);
        }

        return $models;
    }

    /**
     * Update a model given it's id and an array of attributes
     *
     * @param $id
     * @param array $newAttributes
     * @return mixed
     * @throws UpdateFailedRepositoryException
     */
    public function updateById($id, array $newAttributes)
    {
        $model = $this->getById($id);
        $results = $model->update($newAttributes);

        if(!$results){
            throw new UpdateFailedRepositoryException();
        }

        return $model;
    }

    /**
     * Update a model given a model and an array of new attributes
     *
     * @param Model $model
     * @param array $newAttributes
     * @return mixed
     * @throws UpdateFailedRepositoryException
     */
    public function update(Model $model, array $newAttributes)
    {
        $results = $model->update($newAttributes);

        if(!$results){
            throw new UpdateFailedRepositoryException();
        }

        return $model;
    }

    /**
     * Delete a given model
     *
     * @param $model
     * @return bool|null
     * @throws \Exception
     */
    public function delete(Model $model)
    {
        return $model->delete();
    }

    /**
     * Delete many models
     *
     * @param ArrayAccess $models
     * @return array
     */
    public function deleteMany(ArrayAccess $models)
    {
        $results = [];

        foreach ($models as $model) {
            $results[] = $this->delete($model);
        }

        return $this->returnResults($results);
    }

    /**
     * Delete a model by it's id (or ids)
     *
     * @param $id
     * @return int
     * @throws \Exception
     */
    public function deleteById($id)
    {
        return $this->newModel()->destroy($id);
    }

    /**
     * Make a new empty model
     *
     * @return Model
     * @throws \Exception
     */
    public function newModel()
    {
        $model = app()->make($this->modelClass);

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->modelClass} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $model;
    }

    /**
     * Paginate a given query
     *
     * @param int $page
     * @param int $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate($page = 1, $perPage = null, $columns = ['*'])
    {
        $perPage = $perPage ?: $this->defaultPageSize;

        $query = $this->getQuery();

        $total = $query->getQuery()->getCountForPagination($columns);
        $query->getQuery()->forPage($page, $perPage);
        $results = $query->get($columns);

        $results = new LengthAwarePaginator($results, $total, $perPage, $page);

        return $this->returnResults($results);
    }

    /**
     * @param $results
     * @return mixed
     */
    private function returnResults($results)
    {
        $this->destroyQuery();
        
        return $this->parseResults($results);
    }

    /**
     * You can override this to parse the results before returning
     *
     * @param $results
     * @return mixed
     */
    protected function parseResults($results)
    {
        return $this->returnResults($results);
    }

    /**
     * Make a new query
     *
     * @return Builder|QueryBuilder
     * @throws \Exception
     */
    protected function newQuery()
    {
        return $this->newModel()->newQuery();
    }

    /**
     * Set null to the instantiated query object
     */
    protected function destroyQuery()
    {
        $this->query = null;
    }

    /**
     * Get the current query
     *
     * @return Builder|QueryBuilder
     */
    protected function getQuery()
    {
        if(!$this->query){
            $this->query = $this->newQuery();
        }

        return $this->query;
    }
}