<?php namespace Braindump\Api\Model;

require_once(__DIR__ . '/File.php');

class FileFacade
{
    public function getFileForLogicalFilename($fileName)
    {
        $file =  File::select('*')
            ->filter('currentUserFilter')
            ->where('logical_filename', $fileName)
            ->find_one();

        return $file;
    }

    public function getFileForFile($file)
    {
        $file =  File::select('*')
            ->filter('currentUserFilter')
            ->where('original_filename', $file->original_filename)
            ->where('hash', $file->hash)
            ->find_one();

        return $file;
    }

    public function getFileForPhysicalFilename($fileName)
    {
        $file = File::select('*')
            ->filter('currentUserFilter')
            ->where('physical_filename', $fileName)
            ->find_one();
    }

    // Generates a unique logical name for a file
    // (unique for user)
    //
    // Based on DB content a unique logical name
    // for the file will be generated.
    //
    // If the logical filename is not present in
    // the DB, then the given named is returned
    // as the unique name
    //
    // If the name is already present, returns
    // the name with a digit to make it unique
    //
    // Example: 'file.png' becomes 'file 2.png'
    // 
    // CURRENTLY NOT USED!!
    // TODO: Sorting results does not work as exptected
    // because extension (.png) is included in sort
    public function getUniqueName($fileName)
    {
        //print_r($fileName);

        $file = $this->getFileForLogicalFilename($fileName);

        if (!$file) {
            // Filename is unique, no action needed
            return $fileName;
        }

        // split in parts
        $parts = pathinfo($fileName);
        
        // Filename is not unique, build a regex to exclude last numeric part
        // 'filaname 3' becomes filename'
        $regexFilename = '/^(.*?) ?(\d+)?.' . $parts['extension'] . '$/';

        if (preg_match($regexFilename, $fileName, $matches)) {
            $sanitizedFilename = array_key_exists(1, $matches) ? $matches[1] : '';
        }

        // Filename is not unique, build a regex to be used in query
        $regexMatch = '/^' . $sanitizedFilename . '( \d+)*.' . $parts['extension'] . '$/';

        // add the REGEXP magic to the db
        // http://stackoverflow.com/questions/5071601/how-do-i-use-regex-in-a-sqlite-query
        $db = \ORM::get_db();
        $db->sqliteCreateFunction('regexp', function($r, $s) {
            return (preg_match($r, $s) === 1);
        });
        
        $existingFile = File::select('logical_filename')
            ->filter('currentUserFilter')
            ->where_raw('(`logical_filename` REGEXP ?)', $regexMatch)
            ->order_by_asc('logical_filename')
            ->find_array();

        //print_r($existingFile);
        //
        
        if (preg_match($regexFilename, $existingFile[0]['logical_filename'], $matches)) {
            $sanitizedFilename = $matches[1] ? $matches[1] : '';
            $index = array_key_exists(2, $matches) ? $matches[2] : 1;
            $index++;

            $result = $sanitizedFilename . ' ' . $index . '.' . $parts['extension'];
//            print_r($result);
            $obj = (object)['name' => $fileName, 'db' => $existingFile, 'result' => $result ];
            //print_r($obj);

            return $result;
        }
    }
}
