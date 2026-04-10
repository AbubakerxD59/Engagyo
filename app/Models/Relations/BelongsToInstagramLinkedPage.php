<?php

namespace App\Models\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsTo Page by (page_id + user_id). Eager-safe — avoids whereColumn to a missing parent table.
 */
class BelongsToInstagramLinkedPage extends BelongsTo
{
    public function addConstraints()
    {
        parent::addConstraints();

        if (static::$constraints) {
            $table = $this->related->getTable();
            $this->query->where($table.'.user_id', $this->child->user_id);
        }
    }

    public function addEagerConstraints(array $models)
    {
        $table = $this->related->getTable();

        $this->query->where(function ($q) use ($models, $table) {
            $added = false;
            foreach ($models as $model) {
                if ($model->page_id === null || $model->page_id === '') {
                    continue;
                }
                $added = true;
                $q->orWhere(function ($q2) use ($model, $table) {
                    $q2->where($table.'.page_id', $model->page_id)
                        ->where($table.'.user_id', $model->user_id);
                });
            }
            if (! $added) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$this->compositeKey($result->page_id, $result->user_id)] = $result;
        }

        foreach ($models as $model) {
            $key = $this->compositeKey($model->page_id, $model->user_id);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }

        return $models;
    }

    private function compositeKey($pageId, $userId): string
    {
        return (string) $pageId.'|'.(string) $userId;
    }
}
