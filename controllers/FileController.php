<?php namespace Braindump\Api\Controller\File;

require_once __DIR__ . '/BaseController.php';
require_once(__DIR__ . '/../model/FileFacade.php');

// todo: refactor into Upload
use Braindump\Api\Model\File as File;

class FileController extends \Braindump\Api\Controller\BaseController {

    private $fileFacade;

    public function __construct($ci) {
        
        $this->fileFacade = new \Braindump\Api\Model\FileFacade();
        File::$config = $ci->get('settings')['braindump']['file_upload_config'];
        parent::__construct($ci);

    }
   
    public function getFile($request, $response, $args) {

        $file = $this->fileFacade->getFileForLogicalFilename($args['name']);

        if (!$file) {
            return $response->withStatus(404);
        }

        // todo: cacheing strategy with filehash
        $response = $response
            ->withHeader('Content-Type', $file->mime_type)
            ->withHeader('Content-Disposition', 'inline; filename="' . $file->logical_filename . '"')
            ->withHeader('Content-Length', $file->size);
    
        $file->read();

    }
    
    public function postFile($request, $response) {

        // This route does not use json as input, but rather a standard formbased file upload
        // where the file data is placed in the $_FILES variable

        // Todo:
        // - Move most boiler plate to facade

        if (isset($_FILES['file'])) {
            $fileData = $_FILES['file'];
        } else {
            return $response;
        }
        
        if ($fileData['error'] <> UPLOAD_ERR_OK) {
            return $response->withStatus(400)->withResponseBody('Invalid input');
        }

        $input = (object)[
            'logical_filename'  => $fileData['name'],
            'physical_filename' => uniqid(),
            'original_filename'  => $fileData['name'],
            'mime_type'         => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $fileData['tmp_name']),
            'hash'              => md5_file($fileData['tmp_name']),
            'size'              => filesize($fileData['tmp_name'])
        ];

        if (!File::isValid($input)) {
            return $response->withStatus(400)->withResponseBody('Invalid input');
        }

        // Check if file with given name exists
        $file = $this->fileFacade->getFileForLogicalFilename($input->logical_filename);
        
        // If the files are equal (share the same hash) then just return the existing file)
        if ($file) {
            // If the files are equal (share the same hash) then just return the existing file)
            $identicalFile = $fileFacade->getFileForFile($input);
            
            if ($identicalFile) {
                $app->response()->body('/api/files/' . $identicalFile->logical_filename);
                return;
            } else {
                // same name, different content, rename
                $input->logical_filename = $input->physical_filename;
            }
        }

        $file = File::create();

        $file->map($input);
       
        \ORM::get_db()->beginTransaction();

        $file->save();

        // Move tmp file to /data/uploads (outside of public html folder)
        // with a guid as filename
        // read path from config
        if (move_uploaded_file($_FILES['file']['tmp_name'], File::$config['upload_directory'] . $file->physical_filename)) {
            \ORM::get_db()->commit();
            return $response->getBody()->write('/api/files/' . $file->logical_filename);
        } else {
            \ORM::get_db()->rollback();
            return $response->withStatus(500)->withResponseBody('Error copying file');
        }

    }

}