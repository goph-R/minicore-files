<?php

class File extends Record {
    
    protected $tableName = 'file';
    
    protected $id;
    protected $size;
    protected $created_by;
    protected $created_on;
    protected $name;
    protected $original_name;
    protected $type;
    
    public function getPath() {
        return 'upload/'.substr($this->getName(), 0, 2).'/'.substr($this->getName(), 2, 2).'/'.$this->getName();
    }
 
    public function remove() {
        unlink($this->getPath());
        $this->db->query("DELETE FROM file WHERE id = :id LIMIT 1", [':id' => $this->getId()]);
        $this->setAsNew();
    }    
}
