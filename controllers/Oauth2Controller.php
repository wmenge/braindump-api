<?php namespace Braindump\Api\Controller;

//require_once __DIR__ . '/BaseController.php';
//require_once(__DIR__ . '/../model/FileFacade.php');
require_once __DIR__ . '/../vendor/autoload.php';

// todo: refactor into Upload
use Braindump\Api\Model\File as File;
use Cartalyst\Sentry\Users\UserNotFoundException;

class Oauth2Controller extends \Braindump\Api\Controller\BaseController {

    private $provider;

    public function __construct(\Interop\Container\ContainerInterface $ci) {
        $this->provider = $this->getGitHubProvider();
        parent::__construct($ci);
    }

    // TODO: Proper dependency injections
    private function getGitHubProvider() {
        return new \League\OAuth2\Client\Provider\Github([
            'clientId'          => '771d2819203a7ea0b664',
            'clientSecret'      => 'ef6ea43a050a90d03d2e1b5e5d15037606432c17',
            'redirectUri'       => 'http://localhost:8080/oauth2/callback',
        ]);
    }
   
    public function login($request, $response, $args) {

        if (isset($_SESSION['access_token'])) {
            $this->loginWithAccessToken(unserialize($_SESSION['access_token']));
        } else {
            // If we don't have an authorization code then get one
            $authUrl = $this->provider->getAuthorizationUrl();
            $_SESSION['oauth2state'] = $this->provider->getState();
            header('Location: '.$authUrl);
            exit;
        }
    }


    public function callback($request, $response, $args) {
        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            exit('Invalid state');
        } else {
            // Try to get an access token (using the authorization code grant)
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            $_SESSION['access_token'] = serialize($token);

            $this->loginWithAccessToken($token);
        }
    }

    private function loginWithAccessToken($accessToken) {
        // Optional: Now you have a token you can look up a users profile data
        try {
            // We got an access token, let's now get the user's details
            $resourseOwner = $this->provider->getResourceOwner($accessToken);
            $login = sprintf('github.com:%s', $resourseOwner->getId());
            
            $user = null;

            try {
                $user = \Sentry::findUserByLogin($login);
            } catch (UserNotFoundException $e) {
                $user = \Sentry::createUser([
                    'login'     => $login,
                    'name'      => $resourseOwner->getName(),
                    'activated' => true
                ]);
                $user->addGroup(\Sentry::findGroupByName('Users'));
                $user->addGroup(\Sentry::findGroupByName('Administrators'));
            }

            // We have an access token and can login safely without a password
             \Sentry::login($user);

        } catch (Exception $e) {

            // Failed to get user details
            exit('Oh dear...');
        }

        header('Location: /');
        exit;
    }

}