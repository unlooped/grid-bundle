<?php

namespace Unlooped\GridBundle\Struct;

class AggregateResultStruct
{
    private object $aggregateResults;

    public function __construct(object $aggregateResults)
    {
        $this->aggregateResults = $aggregateResults;
    }

    public function getAggregateResults(): object
    {
        return $this->aggregateResults;
    }

    public function getAggregateResultFor(string $alias)
    {
        return $this->aggregateResults->{$alias};
    }
}
