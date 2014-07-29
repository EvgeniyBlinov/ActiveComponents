<?php
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
