<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use InvalidArgumentException;
use RuntimeException;

final class Container
{
    /** @var array<string, callable(self): mixed> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function bind(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = function (self $container) use ($id, $factory): mixed {
            if (!array_key_exists($id, $container->instances)) {
                $container->instances[$id] = $factory($container);
            }

            return $container->instances[$id];
        };

        unset($this->instances[$id]);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances)
            || array_key_exists($id, $this->bindings);
    }

    public function get(string $id): mixed
    {
        $resolved = null;

        if (array_key_exists($id, $this->instances)) {
            $resolved = $this->instances[$id];
        } elseif (array_key_exists($id, $this->bindings)) {
            $resolved = $this->bindings[$id]($this);
        } elseif (class_exists($id)) {
            $resolved = $this->build($id);
        } else {
            throw new InvalidArgumentException("No hay una dependencia registrada para: {$id}");
        }

        return $resolved;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public function build(string $className): object
    {
        $instance = null;
        $reflection = new \ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("La clase no se puede instanciar: {$className}");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            $instance = $reflection->newInstance();
        } else {
            $dependencies = [];

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $dependencies[] = $this->get($type->getName());
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException(
                        "No se puede resolver el parametro {$parameter->getName()} de {$className}"
                    );
                }
            }

            $instance = $reflection->newInstanceArgs($dependencies);
        }

        return $instance;
    }
}
