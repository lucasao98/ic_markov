<?php

namespace app\models;

use yii\base\Model;

class ValidationModel extends Model{
    public $vector;
    public $model;
    public $stock;
    public $states_number; 
    public $day;
    public $premin; 
    public $premax;
    public $interval;

    public function rules()
    {
        return [
            [['stock', 'vector', 'model', 'states_number', 'day', 'premin', 'premax', 'interval'], 'required']
        ];
    }
}