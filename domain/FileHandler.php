<?php

class FileHandler {

    private $id;
    private $tableName;
    private $reference;
    private $files;
    private $db;
    
    public function __construct($tableName, $reference, $dbInstanceName='database') {
        $framework = Framework::instance();
        $this->tableName = $tableName;
        $this->reference = $reference;
        $this->db = $framework->get($dbInstanceName);
    }
    
    public function setId($id) {
        $this->id = $id;
    }

    public function findFiles() {
        $query = "SELECT f.* FROM file AS f";
        $query .= " JOIN {$this->tableName} AS r ON r.{$this->reference} = :id AND r.file_id = f.id";
        $query .= " ORDER BY f.id";
        return $this->db->fetchAll('File', $query, [':id' => $this->id]);
    }
    
    public function getFiles() {
        if (!$this->files) {
            $this->files = $this->findFiles();
        }
        return $this->files;
    }
    
    private function removeUnusedFiles($oldFiles, $newFiles) {
        $oldFilesById = [];
        $oldFileIds = [];
        $newFileIds = [];
        foreach ($oldFiles as $file) {
            $oldFilesById[$file->getId()] = $file;
            $oldFileIds[] = $file->getId();
        }
        foreach ($newFiles as $file) {
            $newFileIds[] = $file->getId();
        }
        $diff = array_diff($oldFileIds, $newFileIds);
        foreach ($diff as $id) {
            $oldFilesById[$id]->remove();
        }
    }
        
    public function saveFiles($files) {
        $this->removeUnusedFiles($this->getFiles(), $files);        
        $query = "DELETE FROM {$this->tableName} WHERE {$this->reference} = :id";
        $this->db->query($query, [':id' => $this->id]);
        foreach ($files as $file) {
            $query = "INSERT INTO {$this->tableName} ({$this->reference}, file_id) VALUES (:id, :file_id)";
            $this->db->query($query, [
                ':id' => $this->id,
                ':file_id' => $file->getId()
            ]);
        }
    }    

}
