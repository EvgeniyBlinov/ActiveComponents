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

/**
 * ActiveRecordModel
 */
abstract class ActiveFormModel extends \ActiveComponents\AbstractModel
{
    /**
     * @var array $_models
     */
    private static $_models=array();            // class name => model

    /**
     * @return array of default options
     */
    public function defaults()
    {
        return array();
    }

    /**
     * @return boolean
     */
    abstract public function onSubmit();

    /**
     * Save model
     * @return boolean if model has been saved
     */
    public function submit()
    {
        if ($this->beforeValidate() && $this->validate()) {
            return $this->onSubmit();
        }

        return !$this->hasErrors();
    }

    /**
     * Returns the static model of the specified AR class.
     * The model returned is a static instance of the AR class.
     * It is provided for invoking class-level methods (something similar to static class methods.)
     *
     * EVERY derived AR class must override this method as follows,
     * <pre>
     * public static function model($className=__CLASS__)
     * {
     *     return parent::model($className);
     * }
     * </pre>
     *
     * @param string $className active record class name.
     * @return ActiveRecordModel active record model instance.
     */
    public static function model($options = null, $className = __CLASS__)
    {
        if (isset(self::$_models[$className])) {
            $model = self::$_models[$className];
            $model->setCriteria(array(), false);
            $clearAttributes = array_map(function($attribute){ return array($attribute => null); }, $model->attributeNames());
            $defaultAttributes = (null !== $model->defaults()) ? array_merge($clearAttributes, $model->defaults()) : $clearAttributes;
        } else {
            $model = self::$_models[$className] = new $className(null);
            $defaultAttributes = (null !== $model->defaults()) ? $model->defaults() : array();
        }
        $model->clearInstance();
        $model->attributes = $defaultAttributes;
        if (null !== $options) {
            foreach ($options as $key => $value) {
                $model->$key = $value;
            }
        }
        return $model;
    }

    /**
     * @return array of default settings
     */
    public function getAFDefaultSettings()
    {
        return array(
            'errors' => array(),
        );
    }

    /**
     * Clear AR model
     * @return void
     */
    public function clearInstance()
    {
        foreach ($this->getAFDefaultSettings() as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    /**
     * Get translated errors
     * @param Translator $translator
     * @return array of model error
     */
    public function getTranslatedErrors(\Zend\Mvc\I18n\Translator $translator)
    {
        return array_map(function($error)use($translator){
                $key = key($error);
                return array($key => sprintf($translator->translate($error[$key]), $key));
        }, $this->getErrors());
    }

    /**
     * Get array copy for form processing
     * @return array of attributes
     */
    public function getArrayCopy()
    {
        return $this->attributes;
    }
}
