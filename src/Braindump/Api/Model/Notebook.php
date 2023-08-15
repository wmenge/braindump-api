<?php namespace Braindump\Api\Model;

use Braindump\Api\Lib\SortHelper as SortHelper;
use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;

class Notebook extends \Model
{
    protected static $_table = 'notebook';

    /***
     * Paris relation
     */
    public function notes()
    {
        return $this->has_many(Note::class);
    }

    /***
      * Paris filter method
      */
    public static function currentUserFilter($orm)
    {
        return $orm->where('user_id', Sentry::getUser()->id);
    }

    /***
      * Paris filter method
      */
    public static function sortFilter($orm, $sortString)
    {
        if (empty($sortString)) {
            return $orm;
        }

        return SortHelper::addSortExpression($orm, $sortString);
    }

    public static function isValid($data)
    {
        return
            is_object($data) &&
            property_exists($data, 'title') &&
            is_scalar($data->title) &&
            !empty($data->title);
    }

    public function map($data, $import = false)
    {
        // Explicitly map parameters, be paranoid of your input
        // https://phpbestpractices.org
        if (Notebook::isValid($data)) {
            $this->title = htmlentities($data->title, ENT_QUOTES, 'UTF-8');

            // In import scenario, try to get create and update times from data object
            if ($import && property_exists($data, 'created') && is_numeric($data->created)) {
                $this->created = filter_var($data->created, FILTER_SANITIZE_NUMBER_INT);
            }
            if ($import && property_exists($data, 'updated') && is_numeric($data->updated)) {
                $this->updated = filter_var($data->updated, FILTER_SANITIZE_NUMBER_INT);
            }
        }
    }

    public function save()
    {
        if ($this->created == null) {
            $this->created = time();
        }

        if ($this->user_id == null) {
            $this->user_id = Sentry::getUser()->id;
        }

        $this->updated = time();

        return parent::save();
    }

}
