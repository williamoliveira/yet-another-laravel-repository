<?php

namespace Williamoliveira\Repository\Contracts;

use ArrayAccess;
use Illuminate\Database\Eloquent\Model;
use Williamoliveira\Repository\Exceptions\UpdateFailedRepositoryException;

interface BaseRepository
{

    /**
     * Apply a query to the query
     *
     * @param Query|\Closure $query
     * @return mixed
     */
    public function applyQuery($query);

    /**
     * Make a new empty model
     *
     * @return Model
     * @throws \Exception
     */
    public function newModel();

    /**
     * Find all models
     *
     * @param array $columns
     * @param bool $paginated
     * @param null $perPage
     * @return mixed
     */
    public function getMany($columns = ['*'], $paginated = false, $perPage = null);

    /**
     * Find all models but paginated
     *
     * @param null $perPage
     * @param array $columns
     * @return mixed
     */
    public function getManyPaginated($perPage = null, $columns = ['*']);

    /**
     * Find a single model by it's id
     *
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public function getById($id, $columns = ['*']);

    /**
     * Find many models given an array of ids
     *
     * @param array $ids
     * @param array $columns
     * @return mixed
     */
    public function getManyByIds(array $ids, $columns = ['*']);

    /**
     * Create and persist a model given an array of attributes
     *
     * @param array $attributes
     * @return mixed
     * @throws \Exception
     */
    public function create(array $attributes = []);

    /**
     * Persist a model
     *
     * @param $model
     * @return mixed
     */
    public function save($model);

    /**
     * Persist many models
     *
     * @param ArrayAccess $models
     * @return array
     */
    public function saveMany(ArrayAccess $models);

    /**
     * Update a model given a model and an array of new attributes
     *
     * @param Model $model
     * @param array $newAttributes
     * @return mixed
     * @throws UpdateFailedRepositoryException
     */
    public function update(Model $model, array $newAttributes);

    /**
     * Update a model given it's id and an array of attributes
     *
     * @param $id
     * @param array $newAttributes
     * @return mixed
     * @throws UpdateFailedRepositoryException
     */
    public function updateById($id, array $newAttributes);

    /**
     * Delete a given model
     *
     * @param Model $model
     * @return bool|null
     * @throws \Exception
     */
    public function delete(Model $model);

    /**
     * Delete many models
     *
     * @param ArrayAccess $models
     * @return array
     */
    public function deleteMany(ArrayAccess $models);

    /**
     * Delete a model by it's id
     *
     * @param $id
     * @return int
     * @throws \Exception
     */
    public function deleteById($id);


}