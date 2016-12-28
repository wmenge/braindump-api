<?php namespace Braindump\Api\Model;

class File extends \Model
{
    protected static $_table = 'file';

    public static $config = null;

    /***
     * Expensive query by searching references of the file in notes
     */
    public function notes()
    {
//        return $this->has_many('Braindump\Api\Model\Note');
    }

    /***
      * Paris filter method
      */
    public static function currentUserFilter($orm)
    {
        return $orm->where('user_id', \Sentry::getUser()->id);
    }

    public static function isValid($data)
    {
        return
            is_object($data) &&

            property_exists($data, 'logical_filename') &&
            is_string($data->logical_filename) &&
            !empty($data->logical_filename) &&

            property_exists($data, 'physical_filename') &&
            is_string($data->physical_filename) &&
            !empty($data->physical_filename) &&

             property_exists($data, 'original_filename') &&
            is_string($data->physical_filename) &&
            !empty($data->physical_filename) &&

            //property_exists($data, 'mime_type') &&
            //array_key_exists($data->mime_type, File::$config['mime_types']) && // Get whitelist from config

            property_exists($data, 'hash') &&
            is_string($data->hash) &&
            !empty($data->hash) &&

            property_exists($data, 'size') &&
            is_integer($data->size) &&
            $data->size > 0; // Max size already limited by upload_max_filesize
    }

    public function map($data, $import = false)
    {
        // Explicitly map parameters, be paranoid of your input
        // https://phpbestpractices.org
        if (File::isValid($data)) {
            $this->logical_filename = htmlentities($data->logical_filename, ENT_QUOTES, 'UTF-8');
            $this->physical_filename = htmlentities($data->physical_filename, ENT_QUOTES, 'UTF-8');
            $this->original_filename = htmlentities($data->original_filename, ENT_QUOTES, 'UTF-8');
            $this->mime_type = htmlentities($data->mime_type, ENT_QUOTES, 'UTF-8');
            $this->hash = htmlentities($data->hash, ENT_QUOTES, 'UTF-8');
            $this->size = filter_var($data->size, FILTER_SANITIZE_NUMBER_INT);
        }
    }

    public function save()
    {
        if ($this->user_id == null) {
            $this->user_id = \Sentry::getUser()->id;
        }

        return parent::save();
    }

    public function read()
    {
        readfile(File::$config['upload_directory'] . $this->physical_filename);
    }

    public function getContents()
    {
        return file_get_contents(File::$config['upload_directory'] . $this->physical_filename);
    }
}
