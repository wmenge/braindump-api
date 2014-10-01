<?php

namespace Braindump\Api\Model;

class UserHelper
{
    public function isValid($data)
    {
        return
            is_array($data) &&
            array_key_exists($data, 'email') && is_string($data['email']) && !empty($data['email']) &&
            array_key_exists($data, 'first_name') && is_string($data['first_name']) && !empty($data['first_name']) &&
            array_key_exists($data, 'last_name') && is_string($data['last_name']) && !empty($data['last_name']);
    }

}
