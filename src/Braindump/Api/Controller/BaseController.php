<?php namespace Braindump\Api\Controller;

class BaseController {

    protected $ci;
    protected $renderer;
    
    // move to di container?
    protected $dbFacade;

    public function __construct(\Psr\Container\ContainerInterface $ci) {
        $this->ci = $ci;
        $this->renderer = $this->ci->get('renderer');
        
        $this->dbFacade = new \Braindump\Api\Lib\DatabaseFacade(
            \ORM::get_db(),
            (require( __DIR__ . '/../../../../migrations/migration-config.php')));
    }

    function outputJson($data, $response) {
        // JSON_NUMERIC_CHECK is needed as PDO will return strings
        // as default (even if DB schema defines numeric types).
        // http://stackoverflow.com/questions/11128823/how-to-properly-format-pdo-results-numeric-results-returned-as-string

        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

        $response->getBody()->write($payload);
        return $response
                  ->withHeader('Content-Type', 'application/json');


        //return $response->withJson($data, null, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
    }

}