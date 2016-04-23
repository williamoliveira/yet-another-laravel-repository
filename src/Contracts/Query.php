<?php

namespace Williamoliveira\Repository\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Query
{
    /**
     * @param Builder $query
     * @return mixed
     */
    function apply(Builder &$query);
}