<?php

namespace Freyr\DP\Refactor\FeatureEnvy;

class Model
{
    private string $name;
    private string $email;


    public function do()
    {
        $this->name = 'sfadfs';
        $this->email ='afdskjdfgsdkf';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }



}
