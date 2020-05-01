<?php
namespace Kosan\Framework\Module;

use Illuminate\Database\Capsule\Manager as DB;

class Model extends \Illuminate\Database\Eloquent\Model {

    protected $table = "";

    public function up() {
        if(!DB::schema()->hasTable( $this->getTable() )){
            DB::schema()->create($this->getTable(), function($table){
                foreach ($this->fields() as $field) {
                    $field->setField($table);
                }
                $table->timestamps();
            });
        } else {
            DB::schema()->table( $this->getTable(), function($table){
                $prev = "";
                foreach ($this->fields() as $field) {
                    if(!DB::schema()->hasColumn($this->getTable(), $field->name)){
                        $field->updateField($table, $prev);
                    }
                    $prev = $field->name;
                }
            });
        }
    }

    public function down() {
        DB::schema()->dropIfExists($this->getTable());
    }
    
    public function getTable()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        return $prefix.$this->table;
    }

    public function getTableColumns() {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    public function update_fields(){}
    public function fields(){ return []; }
}