<?php
/**
 * ActiveComponents is light ORM for ZF2.
 * Copyright (c) 2014 Evgeniy Blinov (http://blinov.in.ua/)
 * 
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * @link       https://github.com/EvgeniyBlinov/ActiveComponents for the canonical source repository
 * @author     Evgeniy Blinov <evgeniy_blinov@mail.ru>
 * @copyright  Copyright (c) 2014 Evgeniy Blinov
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace ActiveComponents;

abstract class AbstractComponent
{
    /**
     * @var array of component errors
     */
    protected $errors = array();
    
    /**
     * Get model errors
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set model errors as array
     * @param array $errors
     * @return void
     */
    public function setErrors($errors)
    {
        $this->errors = array_merge($this->errors, $errors);
    }

    /**
     * Is model has any errors
     * @return boolean
     */
    public function hasErrors()
    {
        return count($this->getErrors());
    }

    /**
     * Add error to model
     * @param string $attribute
     * @param string $errorMsg
     * @return void
     */
    public function addError($attribute, $errorMsg)
    {
        $this->errors[] = array($attribute => $errorMsg);
    }
}
