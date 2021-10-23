<?php

namespace Unlooped\GridBundle\ColumnType;

class PercentColumn extends NumberColumn
{
    protected $template = '@UnloopedGrid/column_types/percent.html.twig';
}
