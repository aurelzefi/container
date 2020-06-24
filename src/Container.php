<?php

namespace Aurel\Container;

class Container
{
    protected $bindings = [];

    protected $instances = [];

    public function bind($abstract, $concrete = null, $singleton = false)
    {
        $this->bindings[$abstract] = compact('concrete', 'singleton');
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public function get($abstract, array $parameters = [])
    {
        //
    }
}