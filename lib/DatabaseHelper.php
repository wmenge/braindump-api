<?php

namespace Braindump\Api\Lib;

class DatabaseHelper
{
    // Todo: Replace with phinx: http://docs.phinx.org/en/latest/index.html
    public function createDatabase(\PDO $db, $scripts)
    {
        $results = array();

        $db->beginTransaction();

        // For initial setup, just run all scripts
        // TODO: Migration scenarios
        // TODO: Integration tests for each consecutive migration scenario

        try {
        
            foreach ($scripts as $version => $script) {
                // rethrow file not found warning to an exception
                $sql = @file_get_contents($script);
                if ($sql === false) {
                    throw new \Exception("File not found", 1);
                }
                $db->exec($sql);
            }
            $db->commit();
        
        } catch (\PDOException $e) {
            $db->rollback();
        }

        return $results;
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
        $expressions = array();
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

    /***
     *
     * Adds sort expression to given $query object
     *
     */
    public function addSortExpression($query, $sortString)
    {
        $sortList = $this->parseSortExpression($sortString);
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
