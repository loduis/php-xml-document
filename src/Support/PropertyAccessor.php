<?php

namespace XML\Support;

use ReflectionClass;
use ReflectionMethod;
use UnexpectedValueException;

trait PropertyAccessor
{
    protected function propertyGetterAccessor($key)
    {
        $value = $this->data[$key] ?? null;
        if (!array_key_exists($key, $this->data)) {
            if ($setterClass = $this->getSetterType($key)) {
                $value = $this->data[$key] = $setterClass->newInstanceArgs();
            }
        }
        if ($getter = $this->getGetter($key)) {
            $value = $this->getDefaultValueIfNull($getter, $value);
            $value = $this->$getter($value);
        }

        return $value;
    }

    protected function propertySetterAccessor($key, $value)
    {
        $setter = $this->getSetter($key);
        if (property_exists($this, 'fillable')) {
            if (!($type = $this->resolvePropertyType($key))) {
                throw new UnexpectedValueException(
                    "The property $key is not defined in class: " . static::class . "."
                );
            }
            if (!$setter) {
                $value = $this->castValueFromPropertyType($type, $value);
            }
        }
        if ($setter) {
            $value = $this->castValueFromReturnType($setter, $value);
            $value = $this->$setter($value);
            $value = $this->castValueFromPropertyType($type ?? null, $value);
        }
        $this->data[$key] = $value;

        return $value;
    }

    private function getDefaultValueIfNull($method, $value)
    {
        if ($value === null) {
            $method = $this->reflectionMethod($method);
            if ($method->getNumberOfParameters() === 1) {
                $defaultParameter = $method->getParameters()[0];
                if ($defaultParameter->isOptional()) {
                    $value = $defaultParameter->getDefaultValue();
                }
            }
        }

        return $value;
    }

    private function castValueFromPropertyType($type, $value)
    {
        if ($type && !($value instanceof self) &&
            ($reflection = $this->reflectionType($type))
        ) {
            $value = $reflection->newInstance($value ?? []);
        }

        return $value;
    }

    private function getSetter($key)
    {
        return $this->methodExists('set', $key);
    }

    private function getGetter($key)
    {
        return $this->methodExists('get', $key);
    }

    private function methodExists($prefix, $key)
    {
        $method = $prefix . ucfirst($key);
        if (method_exists($this, $method)) {
            return $method;
        }
    }

    private function castValueFromReturnType($setter, $value)
    {
        if (!$value instanceof self) {
            $reflection = $this->reflectionMethod($setter);
            if ($returnClass = $this->reflectionReturnType($reflection)) {
                return $returnClass->newInstance($value);
            }
        }

        return $value;
    }

    private function reflectionReturnType($reflection)
    {
        if ($reflection->hasReturnType()) {
            $returnType = $reflection->getReturnType();
            if (!$returnType->isBuiltin()) {
                $returnClass = $this->reflectionClass((string) $returnType);
                if ($returnClass->isSubclassOf(self::class)) {
                    return $returnClass;
                }
            }
        }
    }

    private function reflectionParameter($reflection)
    {
        $parameters = $reflection->getParameters();
        $parameterClass = $parameters[0]->getClass();
        if ($parameterClass && $parameterClass->isSubclassOf(self::class)) {
            return $parameterClass;
        }
    }

    private function reflectionMethod($method)
    {
        return $this->reflectionClass()->getMethod($method);
    }

    private function reflectionClass($className = null)
    {
        static $reflections = [];

        $className = $className ?? static::class;

        return $reflections[$className] ?? (
            $reflections[$className] =
            new ReflectionClass($className)
        );
    }

    private function isBuildInType($type)
    {
        return in_array($type, [
            'bool',
            'string',
            'int',
            'float',
            'array',
            'mixed'
        ]);
    }

    private function reflectionType($type)
    {
        if (!$this->isBuildInType($type)) {
            $reflection = $this->reflectionClass($type);
            if ($reflection->isSubclassOf(self::class)) {
                return $reflection;
            }
        }
    }

    private function resolvePropertyType($key)
    {
        return $this->fillable[$key] ?? false;
    }

    private function getSetterType($key)
    {
        if (($setter = $this->getSetter($key))) {
            $reflection = $this->reflectionMethod($setter);
            $setterClass = $this->reflectionReturnType($reflection) ??
                        $this->reflectionParameter($reflection);
        } elseif ($type = $this->resolvePropertyType($key)) {
            $setterClass = $this->reflectionType($type);
        }

        return $setterClass ?? false;
    }

    protected function prepareFillableTypes()
    {
        if ($this->fillable ?? false) {
            list ($defaultNamespace,) = explode('\\', static::class, 2);
            foreach ($this->fillable as $key => $type) {
                if (!$this->isBuildInType($type)) {
                    if (strpos($type, '\\') === false) {
                        $type = '\\' . $type;
                    }
                    list ($currentNamespace, $className) = explode('\\', $type, 2);
                    $currentClass = $defaultNamespace . '\\' . $className;
                    if ($currentNamespace != $defaultNamespace &&
                        $currentClass !== $type && class_exists($currentClass)
                    ) {
                        $this->fillable[$key] = $currentClass;
                    }
                }
            }
            return true;
        }

        return false;
    }
}
