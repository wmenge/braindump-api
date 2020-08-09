<?php namespace Braindump\Api\Controller;

require_once __DIR__ . '/BaseController.php';

class HtmlBaseController extends BaseController {

    protected $flash;
    
    public function __construct(\Interop\Container\ContainerInterface $ci) {
		parent::__construct($ci);
        $this->flash = $ci->get('flash');
    }

}