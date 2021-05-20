<?php

namespace App\Helpers;

use Illuminate\Container\Container;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

trait CollectionHelper
{
    public static function paginate(Collection $results, $pageSize = 15)
    {
        $pageSize = $pageSize ? $pageSize : 15;
        $page = Paginator::resolveCurrentPage('page');

        $total = $results->count();

        return self::paginator($results->forPage($page, $pageSize), $total, $pageSize, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

    }

    protected static function paginator($items, $total, $perPage, $currentPage, $options)
    {
        return Container::getInstance()->makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }

    public static function getSorting($table, $defaultColumn, $sort = null, $order = null)
    {
        $columns = Schema::getColumnListing($table);
        $orderBy = $order && in_array($order, $columns) ? $order : $defaultColumn;
        $sortBy = $sort && in_array($sort, ['asc', 'desc']) ? $sort : 'asc';

        return compact('orderBy', 'sortBy');
    }
}
