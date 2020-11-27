<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface FilterType
{
    public function configureOptions(OptionsResolver $resolver): void;
}
