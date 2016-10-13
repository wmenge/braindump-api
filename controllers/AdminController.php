<?php namespace Braindump\Api\Controller\Admin;

require_once __DIR__ . '/BaseController.php';

require_once(__DIR__ . '/../model/NotebookFacade.php');
require_once(__DIR__ . '/../model/NoteFacade.php');
require_once(__DIR__ . '/../model/UserFacade.php');
require_once(__DIR__ . '/../model/FileFacade.php');

use Braindump\Api\Model\Notebook as Notebook;
use Braindump\Api\Model\Note as Note;
use Cartalyst\Sentry\Users\Paris\User as User;
use Braindump\Api\Model\File as File;

class AdminController extends \Braindump\Api\Controller\BaseController {
   
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
              'user'            => \Sentry::getUser(), ];
        } catch (\Exception $e) {
            $message = [ 'error' => $e->getMessage() ];
        }

        return $this->renderer->render($response, 'admin-page.php', [
            'flash'   => $message,
            'menu'    => $this->renderer->fetch('admin-menu-fragment.php', $menuData),
            'content' => $this->renderer->fetch('admin-fragment.php', $data)
        ]);

   }

}