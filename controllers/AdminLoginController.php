<?php namespace Braindump\Api\Controller\Admin;

require_once __DIR__ . '/AdminBaseController.php';

class AdminLoginController extends \Braindump\Api\Controller\AdminBaseController {
   
   public function getLogin($request, $response, $args) {

        $this->renderer->render($response, 'admin-page.php', [
            'content' => $this->renderer->fetch('login-fragment.php')
        ]);

   }

   public function postLogin($request, $response) {

        // TODO: Catch auth exceptions and return general messages instead of leaking security details
        try {
            \Sentry::authenticate($request->getParsedBody());
        } catch (\Exception $e) {
            $this->renderer->render($response, 'admin-page.php', [
                'flash'   => [ 'error' => $e->getMessage() ],
                'content' => $this->renderer->fetch('login-fragment.php')
            ]);

            return;
        }

        return $response->withStatus(302)->withHeader('Location', '/admin');
   }
      
   public function getLogout($request, $response) {

        try {
            \Sentry::logout();
            session_destroy();
        } catch (\Exception $e) {
           $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withStatus(302)->withHeader('Location', '/admin');
   }
}