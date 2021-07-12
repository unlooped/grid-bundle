<?php

namespace Unlooped\GridBundle\ColumnType;

use Exception;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
            'label'       => null,
            'isSortable'  => true,
            'isMapped'    => true,
            'attr'        => [],
            'template'    => $this->template,
            'meta'        => [],
            'permissions' => [],
        ]);

        $resolver->setDefault('visible', function (Options $options): bool {
            $permissions = $options['permissions'];

            if (0 === \count($permissions)) {
                return true;
            }

            $userRoles = $this->tokenStorage->getToken()->getRoleNames();

            foreach ($userRoles as $role) {
                if (\in_array($role, $permissions, true)) {
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
    }

    public function getValue(string $field, object $object, array $options = [])
    {
        if ($options['isMapped']) {
            try {
                return $this->propertyAccessor->getValue($object, $field);
            } catch (Exception $e) {
                return null;
            }
        }

        return $options['label'] ?? $field;
    }
}
