<?php
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
                $this->MatchValidator($attribute, $attributeValue, $validatorParams['pattern']);
                return !$this->hasErrors();
            case 'required':
                $this->RequiredValidator($attribute, $attributeValue);
                return !$this->hasErrors();
            case 'email':
                $validator = new \Zend\Validator\EmailAddress(array('isValid' => $attributeValue,'useDomainCheck'=>false));
                if (count($validator->getMessages())) {
                    $this->addError($attribute, 'Invalid email address!');
                }
                return !$this->hasErrors();
            case 'url':
                $validator = new \Zend\Validator\Uri(array('isValid' => $attributeValue));
                if (count($validator->getMessages())) {
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
     * @param string $pattern
     * @return boolean
     */
    public function MatchValidator($attribute, $attributeValue, $pattern)
    {
        if (!preg_match($pattern, $attributeValue)) {
            $this->addError($attribute, "Field %s is invalid!");
        }

        return !count($this->getErrors());
    }

    /**
     * RequiredValidator
     * validate model by preg_match
     * @param string $attribute name of model attribute
     * @param string $attributeValue value of model attribute
     * @return boolean
     */
    public function RequiredValidator($attribute, $attributeValue)
    {
        $pattern = '/\S+/';
        if (!preg_match($pattern, $attributeValue)) {
            $this->addError($attribute, "Field %s is required!");
        }

        return !count($this->getErrors());
    }
}
