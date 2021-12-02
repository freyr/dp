<?php

namespace Freyr\DP\Refactor\FeatureEnvy;

class Service
{

    public function __construct(private Model $model)
    {
    }

    public function do()
    {
        $this->model = 'sfadfs';
        $mail = $this->model->getEmail();
        $this->model->setEmail('afdskjdfgsdkf');
    }
}
