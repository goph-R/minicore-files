<?php

class Files {

    /** @var Framework */
    protected $framework;

    /** @var Database */
    protected $db;

    protected $dbInstanceName = 'database';
    protected $tableName = 'file';
    protected $recordClass = 'File';

    public function __construct(Framework $framework) {
        $this->framework = $framework;
        $this->db = $framework->get($this->dbInstanceName);
    }
    
    public function findByName($name) {
        $query = "SELECT * FROM {$this->tableName} WHERE name = :name LIMIT 1";
        return $this->db->fetch($this->recordClass, $query, [
            ':name' => $name
        ]);
    }
    
    public function findByNames(array $names) {
        $in = $this->db->getInConditionAndParams($names);
        $limit = count($names);
        $query = "SELECT * FROM {$this->tableName}";
        $query .= " WHERE name IN (".$in['condition'].")";
        $query .= " ORDER BY id";
        $query .= " LIMIT ".$limit;
        return $this->db->fetchAll($this->recordClass, $query, $in['params']);
    }
    
    public function remove(File $file) {
        unlink($file->getPath());
        $this->db->query("DELETE FROM file WHERE id = :id LIMIT 1", [':id' => $file->getId()]);
        $file->setAsNew();
    }

}
