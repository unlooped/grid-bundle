<?php

namespace Unlooped\GridBundle\Filter\Registry;

use Symfony\Component\Form\Exception\ExceptionInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Unlooped\GridBundle\FilterType\FilterType;

final class FilterRegistry
{
    /**
     * @var array<string, FilterType>
     */
    private array $types;

    /**
     * @param array<string, FilterType> $types
     */
    public function __construct(array $types = [])
    {
        $this->types = $types;
    }

    public function getType(string $name)
    {
        if (!class_exists($name)) {
            throw new InvalidArgumentException(\sprintf('Could not load type "%s": class does not exist.', $name));
        }

        if (!is_subclass_of($name, FilterType::class)) {
            throw new \InvalidArgumentException(\sprintf(
                'Could not load type "%s": class does not implement "%s".',
                $name,
                FilterType::class
            ));
        }

        return $this->types[$name] ?? new $name();
    }

    public function addType(string $type, FilterType $filter): void
    {
        $this->types[$type] = $filter;
    }

    public function hasType(string $name): bool
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getType($name);
        } catch (ExceptionInterface $e) {
            return false;
        }

        return true;
    }
}
