<?php

class FileDropbox {
    
    const CONFIG_FILE_DROPBOX_STYLE_URL = 'files.file_dropbox_style_url';
    const DEFAULT_FILE_DROPBOX_STYLE_URL = '/modules/minicore-files/static/file-dropbox.css';
    
    const CONFIG_FILE_DROPBOX_MAX_SIZE = 'files.file_dropbox_max_size';
    const DEFAULT_FILE_DROPBOX_MAX_SIZE = 4*1024*1024;
    
    /** @var Framework */
    protected $framework;
    
    /** @var Config */
    protected $config;
    
    /** @var View */
    protected $view;
    
    /** @var Response */
    protected $response;
    
    /** @var Request */
    protected $request;
    
    /** @var Files */
    protected $files;
    
    protected $allowedTypes = [];
    protected $disallowedTypes = [];
    protected $uploadRoute;
    protected $uploadRouteParams;
    protected $removeRoute;
    protected $removeRouteParams;
    protected $name;
    
    public function __construct(Framework $framework) {
        $this->framework = $framework;
        $this->config = $framework->get('config');
        $this->view = $framework->get('view');
        $this->userSession = $framework->get('userSession');
        $this->request = $framework->get('request');
        $this->response = $framework->get('response');
        $this->files = $framework->get('files');
    }
    
    public function setName($name) {
        $this->name = $name;        
    }
    
    public function setUploadRoute($value, array $params=[]) {
        $this->uploadRoute = $value;
        $this->uploadRouteParams = $params;
    }
    
    public function setRemoveRoute($value, array $params=[]) {
        $this->removeRoute = $value;
        $this->removeRouteParams = $params;
    }
    
    public function setAllowedTypes(array $types) {
        $this->allowedTypes = $types;
    }
    
    public function setDisallowedTypes(array $types) {
        $this->disallowedTypes = $types;
    }
    
    public function fetch($id, array $files) {
        $styleUrl = $this->config->get(self::CONFIG_FILE_DROPBOX_STYLE_URL, self::DEFAULT_FILE_DROPBOX_STYLE_URL);
        $maxSize = $this->config->get(self::CONFIG_FILE_DROPBOX_MAX_SIZE, self::DEFAULT_FILE_DROPBOX_MAX_SIZE);
        $filesData = [];
        foreach ($files as $file) {
            $data = $file->getArray();
            $data['path'] = $file->getPath();
            $filesData[] = $data;
        }
        $options = [
            'id' => $id,
            'name' => $this->name,
            'uploadUrl' => route_url($this->uploadRoute, $this->uploadRouteParams, '&'),
            'removeUrl' => route_url($this->removeRoute, $this->removeRouteParams, '&'),
            'maxSize' => $maxSize,
            'biggerThanText' => text('files', 'bigger_than'),
            'removeText' => text('files', 'remove'),
            'removeConfirmText' => text('files', 'remove_confirm'),
            'hideText' => text('files', 'hide'),
            'filesData' => $filesData,
        ];        
        return $this->view->fetch(':files/file-dropbox', [
            'styleUrl' => $styleUrl,
            'options' => $options,
        ]);
    }
    
    protected function setError($message, $params=[]) {
        $content = json_encode([
            'number' => $params['number'],
            'error' => text('files', $message, $params)
        ]);
        $this->response->setContent($content);
        return false;
    }
    
    protected function checkUpload(UploadedFile $uploadedFile, array $errorParams) {
        if ($uploadedFile->getError() == UPLOAD_ERR_CANT_WRITE) {
            return $this->setError('no_disk_space', $errorParams);
        }
        if (in_array($uploadedFile->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
            return $this->setError('file_too_big', $errorParams);
        }
        if ($uploadedFile->getError() == UPLOAD_ERR_NO_TMP_DIR) {
            return $this->setError('server_config_error', $errorParams);
        }
        if ($uploadedFile->getError() != UPLOAD_ERR_OK) {
            return $this->setError('upload_was_unsuccessful', $errorParams);
        }
        return true;
    }
    
    protected function getMimeType(UploadedFile $uploadedFile) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $uploadedFile->getTempPath());
        finfo_close($finfo);
        return $type;
    }
    
    protected function checkMimeType($type, array $errorParams) {
        if ($this->disallowedTypes) {
            if (in_array($type, $this->disallowedTypes)) {
                return $this->setError('file_type_not_allowed', $errorParams);
            }
        } else if (!in_array($type, $this->allowedTypes)) {
            return $this->setError('file_type_not_allowed', $errorParams);
        }
        return true;
    }
    
    protected function createName(UploadedFile $uploadedFile) {
        $ext = pathinfo($uploadedFile->getName(), PATHINFO_EXTENSION);
        do {
            $name = bin2hex(random_bytes(16)).'.'.$ext;
        } while ($this->files->findByName($name));
        return $name;
    }
    
    protected function createFile(UploadedFile $uploadedFile, $name, $type) {
        $file = $this->framework->create('File');
        $file->setName($name);
        $file->setOriginalName($uploadedFile->getName());
        $file->setSize($uploadedFile->getSize());
        $file->setType($type);
        $file->setCreatedBy($this->userSession->get('id'));
        $file->setCreatedOn(date('Y-m-h H:i:s'));
        $file->save();
        return $file;
    }
    
    protected function setSuccess(File $file) {
        $json = $file->getArray();
        $json['error'] = null;
        $json['path'] = $file->getPath();
        $this->response->setContent(json_encode($json));
    }
    
    public function upload() {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $this->request->getUploadedFile('file');
        if (!$uploadedFile) {
            return $this->setError('upload_was_unsuccessful');
        }
        $errorParams = ['name' => $uploadedFile->getName()];
        if (!$this->checkUpload($uploadedFile, $errorParams)) {
            return false;
        }
        $type = $this->getMimeType($uploadedFile);
        $errorParams['type'] = $type;
        if (!$this->checkMimeType($type, $errorParams)) {
            return false;
        }        
        $name = $this->createName($uploadedFile);
        $file = $this->createFile($uploadedFile, $name, $type);
        mkdir(dirname($file->getPath()), 0755, true);
        $uploadedFile->moveTo($file->getPath());
        $this->setSuccess($file);
        return $file;
    }
    
}