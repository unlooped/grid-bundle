<?php


namespace Unlooped\GridBundle\FilterType;


use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;

class FilterType
{
    protected $template = '@UnloopedGrid/column_types/text.html.twig';

    protected $field;
    protected $options;

    protected static $cnt = 0;

    public static function getAvailableOperators(): array
    {
        return FilterRow::getExprList();
    }

    public function __construct(string $field, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->field = $field;
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

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $i = self::$cnt++;

        $op = $filterRow->getExpressionOperator();
        $value = $filterRow->getExpressionValue();
        if ($value) {
            $qb->andWhere($qb->expr()->$op($filterRow->getField(), ':value_' . $i));
            $qb->setParameter('value_' . $i, $value);
        } else {
            $qb->andWhere($qb->expr()->$op($filterRow->getField()));
        }
    }
}
