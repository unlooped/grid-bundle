<?php

namespace Unlooped\GridBundle\Grid;

use Unlooped\GridBundle\Helper\GridHelper;

interface Grid
{
    public function configure(GridHelper $grid): void;

    public function getModel(): string;
}
