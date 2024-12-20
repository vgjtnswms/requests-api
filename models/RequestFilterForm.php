<?php

namespace app\models;

use yii\base\Model;

class RequestFilterForm extends Model
{
    public $status;

    public $date_from;

    public $date_to;


    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            ['status', 'string'],
            ['status', 'in', 'range' => [Request::STATUS_ACTIVE, Request::STATUS_RESOLVED]],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d H:i:s'],
            ['date_to', 'compare', 'compareAttribute' => 'date_from', 'operator' => '>=', 'type' => 'date'],
        ];
    }

    public function getRequests()
    {
        $query = Request::find();
        if ($this->status) {
            $query->andWhere(['status' => $this->status]);
        }
        if ($this->date_from) {
            $query->andWhere(['>=', 'created_at', $this->date_from]);
        }
        if ($this->date_to) {
            $query->andWhere(['<=', 'created_at', $this->date_to]);
        }
        return $query->all();
    }
}