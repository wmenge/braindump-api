<?php namespace Braindump\Api\Controller\Admin;

use Braindump\Api\Model\Notebook as Notebook;
use Braindump\Api\Model\Note as Note;
use Braindump\Api\Model\Sentry\Paris\User as User;
use Braindump\Api\Model\File as File;
use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;

class AdminController extends \Braindump\Api\Controller\HtmlBaseController {
   
   public function getRoot($request, $response, $args) {

        $message = [];
        $menuData = [];
        $data = [];

        $data = [
              'currentVersion'  => $this->dbFacade->getCurrentVersion(),
              'highestVersion'  => $this->dbFacade->getHighestVersion(),
              'migrationNeeded' => $this->dbFacade->isMigrationNeeded() ];

        try {
            $menuData = [
              'notebookCount'   => Notebook::count(),
              'noteCount'       => Note::count(),
              'userCount'       => User::count(),
              'fileCount'       => File::count(),
              'user'            => Sentry::getUser(),
              'clientUrl'       => $this->ci->get('settings')['braindump']['client_cors_domain'],
              'canAccessClient' => Sentry::getUser()->inGroup(Sentry::findGroupByName('Users'))
            ];
              
        } catch (\Exception $e) {
            //$message = [ 'error' => $e->getMessage() ];
        }

        return $this->renderer->render($response, 'admin-page.php', [
            //'flash'   => $message,
            'menu'    => $this->renderer->fetch('admin-menu-fragment.php', $menuData),
            'content' => $this->renderer->fetch('admin-fragment.php', $data)
        ]);

   }

}