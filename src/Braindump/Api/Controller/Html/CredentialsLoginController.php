<?php namespace Braindump\Api\Controller\Html;

use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;

class CredentialsLoginController extends \Braindump\Api\Controller\HtmlBaseController {
   
   public function getLogin($request, $response, $args) {
        $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
        $this->renderer->render($response, 'admin-page.php', [
            'content' => $this->renderer->fetch('login-fragment.php', [ 'referer' => $referer])
        ]);
   }

   public function postLogin($request, $response) {

        $body = $request->getParsedBody();
        $referer = (!empty($body['referer'])) ? $body['referer'] : '/';

        if (isset($body['referer'])) unset($body['referer']);

        // TODO: Catch auth exceptions and return general messages instead of leaking security details
        try {
            Sentry::authenticate($body);
        } catch (\Exception $e) {
            $this->renderer->render($response, 'admin-page.php', [
                'flash'   => [ 'error' => $e->getMessage() ],
                'content' => $this->renderer->fetch('login-fragment.php')
            ]);

            return;
        }

        return $response->withStatus(302)->withHeader('Location', $referer);
   }
      
   public function getLogout($request, $response) {

        try {
            Sentry::logout();
            session_destroy();
        } catch (\Exception $e) {
           $this->flash->addMessage('error', $e->getMessage());
        }

      return $response->withStatus(302)->withHeader('Location', '/');
   }
}