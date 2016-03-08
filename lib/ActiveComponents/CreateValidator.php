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

class CreateValidator
{
    protected static $instance;
    protected static $validators;

    private $_errors = array();
    private $_model;

    private function __construct(){ /* ... @return CreateValidator */ }
    private function __clone()    { /* ... @return CreateValidator */ }
    private function __wakeup()   { /* ... @return CreateValidator */ }

    /**
     * @return CreateValidator is singleton
     */
    public static function getInstance()
    {
        if ( !isset(self::$instance) ) {
            $class = __CLASS__;
            self::$instance = new $class();
        }
        return self::$instance;
    }

    /**
     * @return array of errors
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Clear $this->_errors
     */
    public function clearErrors()
    {
        $this->_errors = array();
    }

    /**
     * @return boolean if CreateValidator has any errors
     */
    public function hasErrors()
    {
        return (boolean) count($this->getErrors());
    }

    /**
     * Add error
     * @param string $attribute name of model attribute
     * @param string $message error message
     * @return void
     */
    public function addError($attribute, $message)
    {
        $this->_errors[] = array($attribute => $message);
    }

    /**
     * Validate
     * @param array $rule
     * @return CreateValidator
     */
    public function validate($model, $rule)
    {
        $this->clearErrors();
        $this->_model = $model;

        $attributes = explode(',', str_replace(' ', '', $rule[0]));
        $validatorName = $rule[1];

        $validatorParams = array_slice($rule, 2);

        foreach ($attributes as $attribute) {
            $this->createValidator($attribute, $validatorName, $validatorParams);
        }

        return $this;
    }

    /**
     * Create current validator by validator tag
     * @param string $attribute name of model attribute
     * @param string $validatorName validator tag
     * @param string $validatorParams validator params
     * @return boolean
     */
    public function createValidator($attribute, $validatorName, $validatorParams)
    {
        $attributeValue = $this->_model->$attribute;
        switch ($validatorName) {
            case 'match':
                if (empty($attributeValue) && !empty($validatorParams['allowEmpty'])) {
                    return true;
                }
                $this->MatchValidator($attribute, $attributeValue, $validatorParams);
                return !$this->hasErrors();
            case 'required':
                $this->RequiredValidator($attribute, $attributeValue, $validatorParams);
                return !$this->hasErrors();
            case 'email':
                if (empty($attributeValue) && !empty($validatorParams['allowEmpty'])) {
                    return true;
                }
                if (filter_var($attributeValue, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addError($attribute, 'Invalid email address!');
                }
                return !$this->hasErrors();
            case 'url':
                if (empty($attributeValue) && !empty($validatorParams['allowEmpty'])) {
                    return true;
                }
                if (filter_var($attributeValue, FILTER_VALIDATE_URL) === false) {
                    $this->addError($attribute, 'Invalid url!');
                }
                return !$this->hasErrors();
            default :
                if (method_exists($this->_model, $validatorName)) {
                    if (($result = call_user_func_array(array($this->_model, $validatorName), array($attribute, $validatorParams))) === false) {
                        return $result;
                    }
                }
                return !$this->_model->hasErrors();
        }
    }

    /**
     * MatchValidator
     * validate model by preg_match
     * @param string $attribute name of model attribute
     * @param string $attributeValue value of model attribute
     * @param array $validatorParams
     * @return boolean
     */
    public function MatchValidator($attribute, $attributeValue, array $validatorParams = array())
    {
        if (empty($attributeValue) && !empty($validatorParams['allowEmpty'])) {
            return true;
        }
        if (!preg_match($validatorParams['pattern'], $attributeValue)) {
            $this->addError(
                $attribute,
                !empty($validatorParams['message']) ?
                    $validatorParams['message'] :
                    "Field %s is invalid!"
            );
        }

        return !count($this->getErrors());
    }

    /**
     * RequiredValidator
     * validate model by preg_match
     * @param string $attribute name of model attribute
     * @param string $attributeValue value of model attribute
     * @param array $validatorParams
     * @return boolean
     */
    public function RequiredValidator($attribute, $attributeValue, array $validatorParams = array())
    {
        $pattern = '/\S+/';
        if (!preg_match($pattern, $attributeValue)) {
            $this->addError(
                $attribute,
                !empty($validatorParams['message']) ?
                    $validatorParams['message'] :
                    "Field %s is required!"
            );
        }

        return !count($this->getErrors());
    }
}
