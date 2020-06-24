<?php

namespace Aurel\Container;

use Closure;
use Exception;
use ReflectionClass;

class Container
{
    public $bindings = [];

    protected $singletons = [];

    public function bind($abstract, $concrete = null, $singleton = false)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'singleton');
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public function get($abstract, array $parameters = [])
    {
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->get($concrete, $parameters);
        }

        if ($this->isSingleton($abstract)) {
            $this->singletons[$abstract] = $object;
        }

        return $object;
    }

    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    public function build($concrete, $parameters)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (! $reflector->isInstantiable()) {
            throw new Exception();
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {

        }

        return $results;
    }

    protected function isSingleton($abstract)
    {
        return $this->bindings[$abstract]['singleton'];
    }
}