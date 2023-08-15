<?php namespace Braindump\Api\Controller;

class HtmlBaseController extends BaseController {

    protected $flash;
    
    public function __construct(\Psr\Container\ContainerInterface $ci) {
		parent::__construct($ci);
        $this->flash = $ci->get('flash');
    }

}