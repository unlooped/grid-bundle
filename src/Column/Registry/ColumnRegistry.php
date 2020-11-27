<?php

namespace Unlooped\GridBundle\Column\Registry;

use Exception;
use InvalidArgumentException;
use Unlooped\GridBundle\ColumnType\ColumnTypeInterface;

final class ColumnRegistry
{
    /**
     * @var array<string, ColumnTypeInterface>
     */
    private array $types;

    /**
     * @param array<string, ColumnTypeInterface> $types
     */
    public function __construct(array $types = [])
    {
        $this->types = $types;
    }

    public function getType(string $name)
    {
        if (!class_exists($name)) {
            throw new InvalidArgumentException(sprintf('Could not load type "%s": class does not exist.', $name));
        }

        if (!is_subclass_of($name, ColumnTypeInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                'Could not load type "%s": class does not implement "%s".',
                $name,
                ColumnTypeInterface::class
            ));
        }

        return $this->types[$name] ?? new $name();
    }

    public function addType(string $type, ColumnTypeInterface $column): void
    {
        $this->types[$type] = $column;
    }

    public function hasType(string $name): bool
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getType($name);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
