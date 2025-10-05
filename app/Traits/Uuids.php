<?php

namespace App\Traits;

use Exception;
use Webpatser\Uuid\Uuid;

trait Uuids
{
    /**
     * Boot function for the trait.
     * @throws Exception
     */
    protected static function bootUuids(): void
    {
        static::creating(function ($model) {
            // Generate a UUID only if the key is not already set
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Uuid::generate()->string;
            }
        });
    }
}
