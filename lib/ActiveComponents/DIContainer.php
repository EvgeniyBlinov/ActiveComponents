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

class DIContainer
{
    /**
     * @var array
     **/
    protected $values = array();

    function __set($id, $value)
    {
        $this->values[$id] = $value;
    }

    function __get($id)
    {
        if (!isset($this->values[$id])) {
            throw new \InvalidArgumentException(sprintf('Value "%s" is not defined.', $id));
        }

        return is_callable($this->values[$id]) ?
            $this->values[$id]($this) :
            $this->values[$id];
    }

    function __call($method, $arguments = array())
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $arguments);
        }
        if (isset($this->values[$method]) && is_callable($this->values[$method])) {
            return call_user_func_array(
                $this->values[$method],
                array_merge(array($this), $arguments)
            );
        }

        throw new \BadMethodCallException(sprintf('Method "%s" of DIContainer does not exist', $method));
    }

    /**
     * Create singleton
     *
     * @param Callable $callable
     * @return Callable
     * @author Evgeniy Blinov <evgeniy_blinov@mail.ru>
     **/
    function asShared($callable)
    {
        return function ($c) use ($callable)
        {
            static $object;

            if (is_null($object)) {
                $object = $callable($c);
            }

            return $object;
        };
    }
}
