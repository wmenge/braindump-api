<?php namespace Braindump\Api\Model;

use Braindump\Api\Model\Sentry\Paris\User;

class UserConfiguration extends \Model
{
    protected static $_table = 'user_configuration';
    protected static $notebookFacade;
    
    /***
     * Paris relation 
     */
    public function user()
    {
        return $this->belongs_to(User::class, 'user_id');
    }

    /***
     * Paris relation 
     */
    public function emailToNotebook()
    {
        return $this->user()->find_one()->notebooks()->where('id', $this->email_to_notebook);
    }

    public static function setNotebookFacade($notebookFacade)
    {
        UserConfiguration::$notebookFacade = $notebookFacade;
    }

    public static function isValid($data)
    {
        // Check minimum required fields
        return
            is_object($data) &&
            property_exists($data, 'email_to_notebook') &&
            property_exists($data, 'email') &&
            is_integer($data->email_to_notebook) &&
            // checks wether the notebook id given is from a notebook belonging to this user
            is_object(UserConfiguration::$notebookFacade->getNotebookForId($data->email_to_notebook));
    }

    public function map($data, $import = false)
    {
        // Explicitly map parameters, be paranoid of your input
        // check https://phpbestpractices.org
        // and http://stackoverflow.com/questions/129677
        if (UserConfiguration::isValid($data)) {
            if (property_exists($data, 'email')) {
                $this->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
            }
            if (property_exists($data, 'email_to_notebook')) {
                $this->email_to_notebook = filter_var($data->email_to_notebook, FILTER_SANITIZE_NUMBER_INT);
            }
        }
    }
}
