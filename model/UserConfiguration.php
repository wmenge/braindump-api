<?php namespace Braindump\Api\Model;

class UserConfiguration extends \Model
{
    protected static $_table = 'user_configuration';
    
    /***
     * Paris relation 
     */
    public function user()
    {
        return $this->belongs_to('\Cartalyst\Sentry\Users\Paris\User');
    }

    /***
     * Paris relation 
     */
    public function notebooks()
    {
        return user()->find_one()->notebooks();
    }

    public static function isValid($data)
    {
        // Check minimum required fields
        return
            is_object($data) &&
            property_exists($data, 'email_to_notebook') &&
            is_integer($data->email_to_notebook);// &&
            //is_object($this->notebooks->find_one($email_to_notebook));
            // checks wether the notebook id given is from a notebook belonging to this user
    }

    public function map($data, $import = false)
    {
        // Explicitly map parameters, be paranoid of your input
        // check https://phpbestpractices.org
        // and http://stackoverflow.com/questions/129677
        if (UserConfiguration::isValid($data)) {
            $this->email_to_notebook = filter_var($data->email_to_notebook, FILTER_SANITIZE_NUMBER_INT);
        }
    }
}
