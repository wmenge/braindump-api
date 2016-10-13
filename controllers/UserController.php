<?php namespace Braindump\Api\Controller\User;

require_once __DIR__ . '/BaseController.php';

require_once(__DIR__ . '/../lib/SentryFacade.php');

class UserController extends \Braindump\Api\Controller\BaseController {
   
    public function getuser($request, $response) {

        $user = \Sentry::getUser();

        $userArray = [
            'id'         => $user->id,
            'email'      => $user->email,
            'activated'  => $user->activated,
            'last_login' => $user->last_login,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name
        ];

        return outputJson($userArray, $response);
    }
    
}