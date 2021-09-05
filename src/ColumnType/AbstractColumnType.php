<?php

namespace Unlooped\GridBundle\ColumnType;

use Exception;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use function count;
use function in_array;

abstract class AbstractColumnType implements ColumnTypeInterface
{
    protected $template = '@UnloopedGrid/column_types/text.html.twig';

    protected TokenStorageInterface $tokenStorage;

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(TokenStorageInterface $tokenStorage, PropertyAccessorInterface $propertyAccessor)
    {
        $this->tokenStorage     = $tokenStorage;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'               => null,
            'isSortable'          => true,
            'isMapped'            => true,
            'attr'                => [],
            'template'            => $this->template,
            'meta'                => [],
            'permissions'         => [],
            'resolve_collections' => false,
            'implode_separator'   => ', ',
        ]);

        $resolver->setDefault('visible', function (Options $options): bool {
            $permissions = $options['permissions'];

            if (0 === count($permissions)) {
                return true;
            }

            $userRoles = $this->tokenStorage->getToken()->getRoleNames();

            foreach ($userRoles as $role) {
                if (in_array($role, $permissions, true)) {
                    return true;
                }
            }

            return false;
        });

        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('isSortable', 'bool');
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('template', ['null', 'string']);
        $resolver->setAllowedTypes('meta', 'array');
        $resolver->setAllowedTypes('permissions', 'array');
        $resolver->setAllowedTypes('visible', 'bool');
        $resolver->setAllowedTypes('resolve_collections', 'bool');
        $resolver->setAllowedTypes('implode_separator', ['null', 'string']);
    }

    public function getValue(string $field, object $object, array $options = [])
    {
        if ($options['isMapped']) {
            try {
                if ($options['resolve_collections']) {
                    $imploded = $this->implodeCollections($field, $object, $options);
                    if (is_array($imploded)) {
                        return $this->flatten($imploded);
                    }

                    return $imploded;
                }

                return $this->propertyAccessor->getValue($object, $field);
            } catch (Exception $e) {
                return null;
            }
        }

        return $options['label'] ?? $field;
    }

    protected function implodeCollections(string $field, $object, array $options = [])
    {
        $fieldPaths = explode('.', $field);
        $fieldPath = array_shift($fieldPaths);

        $currentValue = $this->propertyAccessor->getValue($object, $fieldPath);

        if (count($fieldPaths) === 0) {
            return $currentValue;
        }

        if (is_iterable($currentValue)) {
            $res = [];
            foreach ($currentValue as $item) {
                $res[] = $this->implodeCollections(implode('.', $fieldPaths), $item, $options);
            }

            return $res;
        }

        return $this->implodeCollections(implode('.', $fieldPaths), $currentValue);
    }

    protected function flatten(array $array, array $return = []): array
    {
        foreach ($array as $xValue) {
            if(is_array($xValue)) {
                $return = $this->flatten($xValue, $return);
            } else if(isset($xValue)) {
                $return[] = $xValue;
            }
        }

        return $return;
    }
}
