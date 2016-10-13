<?php namespace Braindump\Api\Controller;

require_once(__DIR__ . '/../lib/DatabaseFacade.php');

class BaseController {

   protected $ci;
   protected $renderer;
   protected $flash;

   // move to di container?
   protected $dbFacade;

   public function __construct(\Interop\Container\ContainerInterface $ci) {
        $this->ci = $ci;
        $this->renderer = $this->ci->get('renderer');
        $this->flash = $this->ci->get('flash');

        $this->dbFacade = new \Braindump\Api\Lib\DatabaseFacade(
            \ORM::get_db(),
            (require( __DIR__ . '/../migrations/migration-config.php')));

   }

}