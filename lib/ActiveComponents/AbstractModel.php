<?php
namespace ActiveComponents; 

abstract class AbstractModel extends AbstractComponent
{
    protected $attributes = array();
    protected $nativeAttributes = array();

    /**
     * Rules is abstract function
     */
    abstract public function rules();

    /**
     * List of attributes
     */
    abstract public function attributeNames();

    /**
     * @return array of default options
     */
    public function defaults()
    {
        return array();
    }

    /**
     * Get model attribute
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            if (isset($this->attributes[$name])) {
                return $this->attributes[$name];
            }
        }
    }

    /**
     * Set model attribute
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            return $this->$name = $value;
        } else {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Format data before validation
     * @return boolean
     */
    public function beforeValidate($rules = array())
    {
        return true;
    }

    /**
     * Validate function
     * @param array $rules
     * @return bolean if model is valid
     */
    public function validate($rules = array())
    {
        if (!$this->beforeValidate($rules)) {
            return false;
        }
        
        $modelRules = count($rules) ? $rules : $this->rules();


        
        foreach ($modelRules as $rule) {
            $errors = \ActiveComponents\CreateValidator::getInstance()->validate($this, $rule)->getErrors();
            if (count($errors)) {
                $this->setErrors($errors);
            }
        }

        return !$this->hasErrors();
    }

    /**
     * Set model attributes from array
     * @param array $attributes
     * @param boolean $fromDataBase if attributes from database
     * @return ActiveRecord
     */
    public function setAttributes($attributes, $fromDataBase = false)
    {
        $arrayOfAttributes = (array) $attributes;
        if ($fromDataBase) {
            $this->attributes = $attributes;
            $this->nativeAttributes = $arrayOfAttributes;
        } else {
            foreach ($arrayOfAttributes as $key => $value) {
                if (in_array($key, $this->attributeNames())) {
                    if ($arrayOfAttributes[$key] === null) {
                        throw new \Exception('Attribute ' . $key . ' in model ' . get_class($this) . ' is NULL');
                    }
                    $this->setAttribute($key, $value);
                }
            }
        }

        return $this;
    }
    
    /**
     * Set attribute
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = is_string($value) ? trim($value) : $value;
    }

    /**
     * @return array of model attributes
     */
    public function getAttributes()
    {
        return array_filter($this->attributes, function($attribute){ return ($attribute !== null); });
    }

}
