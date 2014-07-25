<?php

namespace Braindump\Api;

class DatabaseHelper
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function createDatabase()
    {
        $db = ORM::get_db();

        // Fetch initial SQL script and subsequent migration scripts
        $scripts = $this->app->braindumpConfig['databases_setup_scripts'];

        // For initial setup, just run all scripts
        // TODO: Migration scenarios
        foreach ($scripts as $version => $script) {
            echo sprintf('Execute script for version %s<br />', $version);
            $sql = file_get_contents($script);
            $db->exec($sql);
        }

        echo 'Setup performed';
    }

    /***
	 * Helper function to parse a sort querystring expression.
	 *
	 * Converts: "-title,type"
	 *
	 * to:
	 * [
	 *     { field: "title", order: SORT_DESC },
	 *     { field: "type", order: SORT_ASC }
	 * ]
	 *
	 * Does not check if fieldnames actually exist
	 */
    private function parseSortExpression($sortString)
    {
        $tokens = mb_split(',', $sortString);

        foreach ($tokens as $token) {

            $trimmedToken = trim($token);
            $firstChar = mb_substr($trimmedToken, 0, 1);

            $expression = new \stdClass();

            if ($firstChar == '-') {
                $expression->field = trim(mb_substr($trimmedToken, 1));
                $expression->order = SORT_DESC;
            } else {
                $expression->field = $trimmedToken;
                $expression->order = SORT_ASC;
            }

            if (!empty($expression->field)) {
                $expressions[] = $expression;
            }
        }

        return $expressions;
    }

    public function addSortExpression($query)
    {
        $string = $this->app->request()->get('sort');
        $sortList = $this->parseSortExpression($string);
        if (empty($sortList)) {
            return $query;
        }

        foreach ($sortList as $expression) {
            switch ($expression->order) {
                case SORT_ASC:
                    $query = $query->order_by_asc($expression->field);
                    break;
                default:
                    $query = $query->order_by_desc($expression->field);
                    break;
            }
        }

        return $query;
    }
}
