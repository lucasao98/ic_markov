<?php

namespace app\models;

class Client
{
    private $actions;
    private $cash;

    public function __construct($actions, $cash)
    {
        $this->cash = $cash;
        $this->actions = $actions;
    }

    public function buyActions($price)
    {
        if ($this->cash >= $price) {
            $qtdBuy = floor($this->cash / $price);
            $this->cash -= ($price * $qtdBuy);
            $this->actions += $qtdBuy;
        }else{
            return;
        }
    }

    public function sellActions($price)
    {
        if ($this->actions > 0) {
            $this->cash += ($this->actions * $price);
            $this->actions = 0;
        }
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function getCash()
    {
        return $this->cash;
    }
}
