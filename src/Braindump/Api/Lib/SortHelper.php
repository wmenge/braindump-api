<?php namespace Braindump\Api\Lib;

class SortHelper
{

    /***
     * Facade function to parse a sort querystring expression.
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
    public static function parseSortExpression($sortString)
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
    public static function addSortExpression($query, $sortString)
    {
        $sortList = SortHelper::parseSortExpression($sortString);
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
