<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\Html;

class Request extends ActiveRecord
{
    const STATUS_ACTIVE = 'Active';
    const STATUS_RESOLVED = 'Resolved';

    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return 'requests';
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['name', 'email', 'message'], 'required'],
            ['email', 'email'],
            ['name', 'string', 'min' => 1, 'max' => 255],
            ['name', 'filter', 'filter' => function ($value) { return Html::encode($value); }],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_RESOLVED]],
            ['comment', 'required', 'when' => function ($model) { return $model->status === self::STATUS_RESOLVED; }],
            ['comment', 'filter', 'filter' => function ($value) { return Html::encode($value); }, 'when' => function ($model) { return $model->status === self::STATUS_RESOLVED; }],
        ];
    }
}