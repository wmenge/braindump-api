<?php namespace Braindump\Api\Controller;

require_once __DIR__ . '/../vendor/autoload.php';

// todo: refactor into Upload
use Braindump\Api\Model\File as File;
use Cartalyst\Sentry\Users\UserNotFoundException;

class Oauth2Controller extends \Braindump\Api\Controller\BaseController {

    private $configurations;

    public function __construct($ci) {
        $this->configurations = $ci->get('settings')['braindump']['oauth2'];
        parent::__construct($ci);
    }

    private function getProvider($name) {
        switch ($name) {
            case "github":
                return $this->getGitHubProvider($this->configurations[$name]);
                break;
            case "google":
                return $this->getGoogleProvider($this->configurations[$name]);
                break;
            default:
                exit('Oh dear...');
                break;
        }
    }

    private function getGitHubProvider($configuration) {
        return new \League\OAuth2\Client\Provider\Github($configuration);
    }

    private function getGoogleProvider($configuration) {
        return new \League\OAuth2\Client\Provider\Google($configuration);
    }   

    public function login($request, $response, $args) {
        $providerName = $args['provider'];
        $referer = $request->getParam('referer');
        if (empty($referer)) $referer = '/';
        
        // TODO: If logging in with different provider, first log out
        if (isset($_SESSION['access_token'])) {
            $token = unserialize($_SESSION['access_token']);
            $this->loginWithAccessToken($providerName, $token, $referer);
        } else {
            // If we don't have an authorization code then get one
            $provider = $this->getProvider($providerName);
            $authUrl = $provider->getAuthorizationUrl();
            $_SESSION['loginreferer'] = $referer;
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: '.$authUrl);
            exit;
        }
    }

    public function callback($request, $response, $args) {

        $providerName = $args['provider'];

        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            exit('Invalid state');
        } else {
            $provider = $this->getProvider($providerName);

            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            $_SESSION['access_token'] = serialize($token);
            $referer = $_SESSION['loginreferer'];
            unset($_SESSION['loginreferer']);

            $this->loginWithAccessToken($providerName, $token, $referer);
        }
    }

    private function loginWithAccessToken($providerName, $accessToken, $referer) {
        // Optional: Now you have a token you can look up a users profile data
        try {
            $provider = $this->getProvider($providerName);
            // We got an access token, let's now get the user's details
            $resourseOwner = $provider->getResourceOwner($accessToken);
            $login = sprintf('%s:%s', $providerName, $resourseOwner->getId());
            
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
                //$user->addGroup(\Sentry::findGroupByName('Administrators'));
            }

            // We have an access token and can login safely without a password
             \Sentry::login($user);

        } catch (Exception $e) {

            // Failed to get user details
            exit('Oh dear...');
        }

        header('Location: ' . $referer);
        exit;
    }

}