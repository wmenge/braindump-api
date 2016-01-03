<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../model/FileFacade.php');

// todo: refactor into Upload
use Braindump\Api\Model\File as File;

$fileFacade = new \Braindump\Api\Model\FileFacade();

File::$config = $app->braindumpConfig['file_upload_config'];

$app->group('/api', 'Braindump\Api\Admin\Middleware\apiAuthenticate', function () use ($app, $fileFacade) {

    $app->get('/files/:name', function($name) use ($app, $fileFacade) {

        $file = $fileFacade->getFileForLogicalFilename($name);

        if (!$file) {
            return $app->notFound();
        }

        $app->response->headers->set('Content-Type', $file->mime_type);
        $app->response->headers->set('Content-Disposition', 'inline; filename="' . $file->logical_filename . '"');

        // todo: cacheing strategy with filehash
        $app->response->headers->set('Content-Length', $file->size);
        $file->read();
    });

    $app->post('/files(/)', function () use ($app, $fileFacade) {

        // This route does not use json as input, but rather a standard formbased file upload
        // where the file data is placed in the $_FILES variable

        // Todo:
        // - Move most boiler plate to facade
        
        $fileData = $_FILES['file'];

        if ($fileData['error'] <> UPLOAD_ERR_OK) {
            $app->halt(400, 'Invalid input');
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
            $app->halt(400, 'Invalid input');
        }

        // Check if file with given name exists
        $file = $fileFacade->getFileForLogicalFilename($input->logical_filename);
        
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
            $app->response()->body('/api/files/' . $file->logical_filename);
        } else {
            \ORM::get_db()->rollback();
            $app->halt(500, 'Error copying file');
        }

    });

});
