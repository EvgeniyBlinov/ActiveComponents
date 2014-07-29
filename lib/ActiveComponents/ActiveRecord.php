<?php
namespace ActiveComponents;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\Feature;

/**
 * ActiveRecord is zend light ORM class.
 */
abstract class ActiveRecord extends AbstractTableGateway
{
    protected $attributes = array();
    protected $nativeAttributes = array();

    protected $asArray = false;

    protected $table;
    protected $tablePrimaryKey;
    protected $scenario;
    protected $_select;
    protected $_criteria = array();



    /**
     * @return string
     */
    abstract public function getTableName();

    /**
     * @return array of default settings
     */
    public function getARDefaultSettings()
    {
        return array(
            'asArray'          => false,
            'scenario'         => false,
            'attributes'       => array(),
            'nativeAttributes' => array(),
            '_select'          => null,
            '_criteria'        => array(),
            'tablePrimaryKey'  => 'id',
        );
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $feature = new Feature\GlobalAdapterFeature();
        $this->adapter = $feature->getStaticAdapter();
        $this->table = $this->getTableName();
        $this->tablePrimaryKey = 'id';

        $this->initialize();
    }

    /**
     * __call is "magic" method for generating live attributes
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (preg_match('/(find|exec)/', $method)) {
            if (method_exists($this, $method)) {
                $answer = call_user_func_array(array($this,$method), $args);
                if (count($answer) == 1) {
                    if ($this->asArray) {
                        $result = array_pop($answer);
                    } else {
                        $this->scenario = 'update';
                        $result = $this->setAttributes(array_pop($answer), true);
                    }
                } else {
                    $result = $answer;
                }

                return $result;
            }
        }
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
     * Set scenario
     * @param string $scenario
     * @return ActiveRecord
     */
    public function setScenario($scenario)
    {
        $this->scenario = $scenario;
        return $this;
    }

    /**
     * Get scenario
     * @return string
     */
    public function getScenario()
    {
        if (!$this->scenario) {
            $primaryKey = $this->tablePrimaryKey;
            $this->scenario = ($this->$primaryKey !== null) ?  'update' : 'insert';
        }
        return $this->scenario;
    }

    /**
     * Get scenario
     * @return string
     */
    public function getScenarioByAttributes()
    {
        $nativeAttributes = array_filter($this->nativeAttributes);
        return empty($nativeAttributes) ? 'insert' : 'update';
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
            foreach ($arrayOfAttributes as $attribute => $value) {
                $this->setAttribute($attribute, $value);
            }
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

    /**
     * Fetch all records raw format (as object)
     * @return object
     */
    protected function findAll()
    {
        return $this->select()->toArray();
    }

    /**
     * Find record by primary key
     * @return object
     */
    protected function findByPk($Pk)
    {
        return $this->select(array($this->tablePrimaryKey => $Pk))->toArray();
    }

    /**
     * Find by attributes
     * @return  object
     */
    protected function findByAttributes($attributes)
    {
        return $this->select($attributes)->toArray();
    }

    /**
     * Exec raw
     * @return object
     */
    protected function findByCriteria()
    {
        $select = $this->getSelectByCriteria();
        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);
        return $resultSet->toArray();
    }

    /**
     * Set count criteria
     * @return ActiveRecord
     */
    public function setCountCriteria()
    {
        $criteria = $this->getCriteria();
        $criteria['columns'] = array(array(
            'count_records' => new \Zend\Db\Sql\Expression('COUNT(*)')
        ));
        $this->setCriteria($criteria, false);
        return $this;
    }

    /**
     * Count of recorder
     * @return integer
     */
    public function count()
    {
        $this->setCountCriteria();
        $result = $this->findByCriteria();
        $resultArray = array_pop($result);
        return (int) $resultArray['count_records'];
    }

    /**
     * Write model to database
     * @return boolean
     */
    public function write()
    {
        if ($this->getScenario() == 'insert') {
            $status = $this->insert($this->getAttributes());
            if ($status) {
                $this->nativeAttributes = $this->getAttributes();
                $this->scenario = 'update';
            }
        } else {
            //$attributesWithoutPk = array_diff_key($this->getAttributes(), array_flip(array($this->tablePrimaryKey)));
            if (($status = $this->update($this->getAttributes(), $this->nativeAttributes)) != false) {
                $this->nativeAttributes = $this->getAttributes();
                return $status;
            }
        }
        
        if ($status) {
            $id = $this->getInsertId();
            if (preg_match('/^\d+$/', $id) && $id != '0') {
                $this->setAttributes(compact('id'));
            }
        } else {
            $tablePrimaryKey = $this->tablePrimaryKey;
            $action = ($this->getScenario() == 'insert') ? 'created' : 'updated';
            $this->addError($this->$tablePrimaryKey, 'Record not ' . $action . '!');
        }
        
        return !$this->hasErrors();
    }

    /**
     * Get select by criteria
     * @param array $criteria
     * @return Select
     */
    public function getSelectByCriteria($criteria = null)
    {
        if ($criteria === null) {
            $criteria = $this->getCriteria();
        }
        $select = $this->getSql()->select();
        foreach ($criteria as $operation => $options) {
            if ($operation == 'join') {
                call_user_func_array(array($select,$operation), $options);
            } else {
                if (is_array($options)) {
                    foreach ($options as $option) {
                        if ($operation == 'multijoin') {
                            call_user_func_array(array($select, 'join'), $option);
                        } else {
                            $select->$operation($option);
                        }
                    }
                } else {
                    $select->$operation($options);
                }
            }
        }
        return $select;
    }


    /**
     * Get SQL string by criteria
     * @return string
     */
    public function getSQLByCriteria()
    {
        $select = $this->getSelectByCriteria($this->getCriteria());
        return $select->getSqlString($this->getAdapter()->platform);
    }

    /**
     * Get base criteria by attributes
     * @return array
     */
    public function getCriteria()
    {
        if (empty($this->_criteria)) {
            $criteria = array();
            foreach ($this->attributeNames() as $attribute) {
                if ($this->$attribute !== null) {
                    $criteria['where'][] = array($this->getTableName() . '.' . $attribute => $this->$attribute);
                }
            }
            $this->_criteria = $criteria ;
        }
        return $this->_criteria;
    }

    /**
     * Set criteria
     * @param array $criteria
     * @param boolean $merge
     * @return ActiveRecord
     */
    public function setCriteria($criteria, $merge = true)
    {
        if ($merge) {
            $this->_criteria = array_merge_recursive($this->getCriteria(), $criteria);
        } else {
            $this->_criteria = $criteria;
        }
        return $this;
    }

    /**
     * Get sub select
     * @param string $expression
     * @return Expression
     */
    public function getSubSelect($expression)
    {
        return new \Zend\Db\Sql\Expression("($expression)");
    }

    /**
     * @return string of id or zero
     */
    public function getInsertId()
    {
        return $this->adapter->getDriver()->getConnection()->getLastGeneratedValue();
    }
}
