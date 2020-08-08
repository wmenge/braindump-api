<?php

namespace Braindump\Api\Model;

class UserFacade
{
    public function isValid($data)
    {
        return
            is_array($data) &&
            array_key_exists($data, 'login') && is_string($data['login']) && !empty($data['login']) &&
            array_key_exists($data, 'name') && is_string($data['name']) && !empty($data['name']);
    }

}
