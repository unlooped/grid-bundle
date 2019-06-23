<?php


namespace Unlooped\GridBundle\FilterType;


use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;

class FilterType
{
    protected $template = '@UnloopedGrid/column_types/text.html.twig';

    private $field;
    private $alias;
    private $options;

    public static function getAvailableOperators(): array
    {
        return FilterRow::getExprList();
    }

    public function __construct(string $field, array $options = [], string $alias = null)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->field = $field;
        $this->alias = $alias;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'      => null,
            'isSortable' => true,
            'isMapped'   => true,
            'attr'       => [],
            'options'    => [],
            'template'   => $this->template,
        ]);

        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('options', 'array');
        $resolver->setAllowedTypes('template', ['null', 'string']);
    }

}
