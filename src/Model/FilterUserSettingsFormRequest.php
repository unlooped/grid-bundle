<?php

namespace Unlooped\GridBundle\Model;

use Symfony\Component\Form\FormInterface;
use Unlooped\GridBundle\Entity\FilterUserSettings;

class FilterUserSettingsFormRequest
{
    private FormInterface $columnForm;
    private FilterUserSettings $filterUserSettings;

    public function __construct(FormInterface $columnForm, FilterUserSettings $filterUserSettings)
    {
        $this->columnForm         = $columnForm;
        $this->filterUserSettings = $filterUserSettings;
    }

    public function getForm(): FormInterface
    {
        return $this->columnForm;
    }

    public function getFilterUserSettings(): FilterUserSettings
    {
        return $this->filterUserSettings;
    }
}
