<?php

namespace Aurel\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class Container
{
    /**
     * The bindings in the container.
     *
     * @var array
     */
    public $bindings = [];

    /**
     * The singleton bindings in the container.
     *
     * @var array
     */
    protected $singletons = [];

    /**
     * Register a binding in the container.
     *
     * @param  string  $abstract
     * @param  mixed  $concrete
     * @param  bool  $singleton
     * @return void
     */
    public function bind($abstract, $concrete = null, $singleton = false)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'singleton');
    }

    /**
     * Register a singleton binding in the container.
     *
     * @param  string  $abstract
     * @param  mixed  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Get a binding from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \ReflectionException
     */
    public function get($abstract, array $parameters = [])
    {
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($abstract, $concrete)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->get($concrete, $parameters);
        }

        if ($this->isSingleton($abstract)) {
            $this->singletons[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete binding for the given abstract.
     *
     * @param  string  $abstract
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if the binding is buildable.
     *
     * @param  string  $abstract
     * @param  mixed  $concrete
     * @return bool
     */
    protected function isBuildable($abstract, $concrete)
    {
        return $abstract === $concrete || $concrete instanceof Closure;
    }

    /**
     * Build the given concrete.
     *
     * @param  mixed  $concrete
     * @param  array  $parameters
     * @return mixed|object
     *
     * @throws \ReflectionException
     */
    public function build($concrete, $parameters)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve the given dependencies.
     *
     * @param  array  $dependencies
     * @param  array  $parameters
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function resolveDependencies(array $dependencies, $parameters)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];

                continue;
            }

            $class = $dependency->getClass()->name;

            $results[] = is_null($class) ? $this->resolvePrimitive($dependency) : $this->get($class);
        }

        return $results;
    }

    /**
     * Resolve the given primitive parameter.
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
    }

    /**
     * Determine if the binding is a singleton.
     *
     * @param  string  $abstract
     * @return bool
     */
    protected function isSingleton($abstract)
    {
        return isset($this->bindings[$abstract]['singleton']) && $this->bindings[$abstract]['singleton'] === true;
    }

    /**
     * Call a method from the container.
     *
     * @param  object|string $object
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \ReflectionException
     */
    public function call($object, $method, array $parameters = [])
    {
        if (is_string($object)) {
            $object = $this->get($object);
        }

        $reflector = new ReflectionMethod($object, $method);

        $dependencies = $reflector->getParameters();

        $instances = $this->resolveDependencies($dependencies, $parameters);

        return call_user_func_array([$object, $method], $instances);
    }
}
