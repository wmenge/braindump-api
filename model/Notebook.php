<?php namespace Braindump\Api\Model;

require_once(__DIR__ . '/../lib/SortHelper.php');

use Braindump\Api\Lib\SortHelper as SortHelper;

class Notebook extends \Model
{
    protected static $_table = 'notebook';

    /***
     * Paris relation
     */
    public function notes()
    {
        return $this->has_many('Braindump\Api\Model\Note');
    }

    /***
      * Paris filter method
      */
    public static function currentUser($orm)
    {
        return $orm->where('user_id', \Sentry::getUser()->id);
    }

    /***
      * Paris filter method
      */
    public static function sort($orm, $sortString)
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
            is_string($data->title) &&
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
        if (!property_exists($this, 'created') || $this->created == null) {
            $this->created = time();
        }

        if (!property_exists($this, 'user_id') || $note->user_id == null) {
            $this->user_id = \Sentry::getUser()->id;
        }

        $this->updated = time();

        return parent::save();
    }

}
