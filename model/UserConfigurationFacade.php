<?php namespace Braindump\Api\Model;

require_once(__DIR__ . '/UserConfigurationFacade.php');

class UserConfigurationFacade
{
    public function getConfiguration($fieldsOnly = true)
    {
        $fields = null;

        if ($fieldsOnly) {
            $fields = 'email_to_notebook';
        }

        return \Sentry::getUser()->configuration()->select('email_to_notebook')->find_one();
    }
}
