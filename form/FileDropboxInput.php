<?php

class FileDropboxInput extends Input {
        
    protected $trimValue = false;
    
    /** @var Files */
    protected $files;
    
    /** @var FileDropbox */
    protected $fileDropbox;
    
    public function __construct(Framework $framework, $name, $defaultValue=[]) {
        parent::__construct($framework, $name, $defaultValue);
        $this->files = $framework->get('files');
        $this->fileDropbox = $framework->create('FileDropbox');
        $this->setValue($defaultValue);
    }
    
    public function setUploadRoute($value, array $params=[]) {
        $this->fileDropbox->setUploadRoute($value, $params);
    }
    
    public function setRemoveRoute($value, array $params=[]) {
        $this->fileDropbox->setRemoveRoute($value, $params);
    }

    public function setOption($name, $value) {
        $this->fileDropbox->setOption($name, $value);
    }

    public function setCallback($name, $value) {
        $this->fileDropbox->setCallback($name, $value);
    }
    
    public function setValue($value) {
        if (is_array($value) && isset($value[0])) {
            if (is_string($value[0])) {
                $this->value = $this->files->findByNames($value);
            } else {
                $this->value = $value;
            }
        } else {
            $value = [];            
        }
    }
    
    public function fetch() {
        $this->fileDropbox->setName($this->form->getName().'['.$this->getName().']');
        $value = $this->getValue();
        if (!empty($value) && is_array($value)) {
            $files = $value;
        } else {
            $files = [];
        }
        return $this->fileDropbox->fetch($this->getId(), $files);
    }
    

}
