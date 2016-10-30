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

    public function putUser($request, $response) {

        $user = \Sentry::getUser();

        // Get input
        $input = json_decode($request->getBody());

        $password = htmlentities($request->getParam('password'));
        $password_confirm = htmlentities($request->getParam('password_confirm'));

        if (empty($password)) {
            return $response->withStatus(400)->write('No password supplied');
        }

        // Validate input
        if ($password != $password_confirm) {
            return $response->withStatus(400)->write('Passwords do not match');
        }

        // Only allow password change
        $user->password = $password;
        $user->save();
    }
    
}