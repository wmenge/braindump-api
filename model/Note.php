<?php namespace Braindump\Api\Model;

require_once(__DIR__ . '/../lib/SortHelper.php');

use Braindump\Api\Lib\SortHelper as SortHelper;

class Note extends \Model
{
    const TYPE_TEXT = 'Text';
    const TYPE_HTML = 'HTML';

    protected static $_table = 'note';

    /***
     * Paris relation
     */
    public function notebook()
    {
        return $this->belongs_to('Braindump\Api\Model\Notebook');
    }

    /***
      * Paris filter method
      */
    public static function currentUserFilter($orm)
    {
        return $orm->where('user_id', \Sentry::getUser()->id);
    }

    /***
      * Paris filter method
      */
    public static function contentFilter($orm, $queryString)
    {
        if (empty($queryString)) {
            return $orm;
        }

        return $orm->where_raw(
            '(`title` LIKE ? OR `content` LIKE ?)',
            array(sprintf('%%%s%%', $queryString), sprintf('%%%s%%', $queryString))
        );
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
        // Check minimum required fields
        return
            is_object($data) &&
            property_exists($data, 'title') &&
            is_string($data->title) &&
            !empty($data->title) &&
            property_exists($data, 'type') &&
            in_array($data->type, [Note::TYPE_TEXT, Note::TYPE_HTML]);

        // if url is supplied, check content
    //      ($inpuxtData->content_url == null || !(filter_var($inputData->content_url, FILTER_VALIDATE_URL) === false);
    }

    public function map($notebook, $data, $import = false)
    {
        // Explicitly map parameters, be paranoid of your input
        // check https://phpbestpractices.org
        // and http://stackoverflow.com/questions/129677
        if (Note::isValid($data)) {
            if (!empty($notebook)) {
                $this->notebook_id = $notebook->id;
            }
            if (property_exists($data, 'title')) {
                $this->title = htmlentities($data->title, ENT_QUOTES, 'UTF-8');
            }

            if (property_exists($data, 'url')) {
                $this->url = htmlentities($data->url, ENT_QUOTES, 'UTF-8');
            }

            if (property_exists($data, 'type')) {
                $this->type = htmlentities($data->type, ENT_QUOTES, 'UTF-8');
            }

            if (property_exists($data, 'content')) {
                if ($this->type == Note::TYPE_HTML) {
                    // check http://dev.evernote.com/doc/articles/enml.php for evenrote html format
                    // TODO Check which tags to allow/disallow

                    $config = \HTMLPurifier_Config::createDefault();
                    // Allow base64 image data
                    $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'data' => true]);
                    $purifier = new \HTMLPurifier($config);

                    $this->content = $purifier->purify($data->content);
                } elseif ($this->type == Note::TYPE_TEXT) {
                    $this->content = htmlentities($data->content, ENT_QUOTES, 'UTF-8');
                } else {
                    // Shouldn't happen
                }
            }

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
            $this->user_id = \Sentry::getUser()->id;
        }

        $this->updated = time();

        return parent::save();
    }
}
