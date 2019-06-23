<?php

namespace Unlooped\GridBundle\ColumnType;

interface ColumnTypeInterface {

    public function getValue($object);
    public function getOptions(): array;
    public function getTemplate(): string;

}
