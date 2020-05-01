<?php
namespace Kosan\Framework\Module;

class Field
{
    public $type = null;
    public $name = null;
    public $length = 0;
    public $nullable = false;
    public $option = [];

    public function __construct($type, $name, $length = 0, $nullable = false, $option = []) {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
        $this->nullable = $nullable;
        $this->option = $option;
    }

    public function setField($table)
    {
        if($this->length > 0 && $this->nullable)
            $table->{$this->type}($this->name, $this->length)->nullable();
        else if($this->length > 0)
            $table->{$this->type}($this->name, $this->length);
        else if($this->nullable)
            $table->{$this->type}($this->name)->nullable();
        else
            $table->{$this->type}($this->name);
    }
    
    public function updateField($table, $after)
    {
        if($after == '') return $this->setField($table);
        if($this->length > 0 && $this->nullable)
            $table->{$this->type}($this->name, $this->length)->nullable()->after($after);
        else if($this->length > 0)
            $table->{$this->type}($this->name, $this->length)->after($after);
        else if($this->nullable)
            $table->{$this->type}($this->name)->nullable()->after($after);
        else
            $table->{$this->type}($this->name)->after($after);
    }
}