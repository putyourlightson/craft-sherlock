<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\base;

use craft\base\Model;

/**
 * BaseModel
*/
abstract class BaseModel extends Model
{
    /**
     * Populates a new model instance with a given set of attributes.
     *
     * @param mixed $values
     *
     * @return Model
     */
    public static function populateModel($values): Model
    {
        $class = static::class;
        $model = new $class();

        $properties = array_keys($model->getAttributes());
        $model = new $class($values->toArray($properties));

        return $model;
    }

    /**
     * Mass-populates models based on an array of attribute arrays.
     *
     * @param array $data
     * @param string|null $indexBy
     *
     * @return array
     */
    public static function populateModels(array $data, $indexBy = null): array
    {
        $models = [];

        if (is_array($data)) {
            foreach ($data as $values) {
                $model = self::populateModel($values);

                if ($indexBy !== null)
                {
                    $models[$model->{$indexBy}] = $model;
                }
                else
                {
                    $models[] = $model;
                }
            }
        }

        return $models;
    }
}
