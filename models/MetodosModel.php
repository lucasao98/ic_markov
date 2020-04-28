<?php
namespace app\models;

use yii\base\Model;

class MetodosModel extends Model
{
    
    public $metodo;
    
    public function rules(){
        
        return [
            ['metodo', 'required']
        ];
        
    }

    public function attributeLabels()
    {
        return [
            'metodo' => 'Escolha o método de previsão:'
        ];
    }
    
}

