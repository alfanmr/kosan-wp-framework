<?php
namespace Kosan\Framework\Module;

use WP_List_Table;

class List_WP extends WP_List_Table {

	public $table = "";
	public $ob = 'id';
	public $primary = 'id';
	public $per_page = 15;
	public $ajax = false;
	public $name = "";
	public $action_button = "";
	public $has_checkbox = false;
	public $actions = [];
	// public $search_column = "";
    
    function __construct(){
        parent::__construct( array(
			'singular' => $this->name,
            'ajax'      => $this->ajax
        ));
    }
	
	public function validate_post()
	{
		return check_admin_referer('bulk-' . $this->_args['plural']);
	}
    /**
	 * Add columns to grid view
	 */
	function get_columns(){
		$table = new $this->table();
		$fields = $table->fields();
		$columns = [];

		if($this->has_checkbox){
			$columns['cb'] = '<input type="checkbox" />';
		}
		foreach ($fields as $field) {
			$option = $field->option;
			if($option != [] && @$option['column'] ?? false){
				$column_name = $option['column_name'] ?? $field->name;
				$columns[$field->name] = $column_name;
			}
		}
		return $columns;
	}	
 
	function column_default( $item, $column_name ) {
		$table = new $this->table();
        $primary = $table->getKeyName();
		$fields = $table->fields();
		$txt = $this->render_field($item[$column_name], $column_name);
		
		if($this->has_checkbox){
			if($column_name == 'cb')
				return $this->column_cb($item, $this->id);
		}
		foreach ($fields as $field) {
			if($field->name == $column_name){
				$option = $field->option;
				if($option != []){
					if(isset($option['column_type'])){
						switch ($option['column_type']) {
							case 'currency':
								$item[$column_name] = number_format((float) $this->render_field($item[$column_name], $column_name), 0, ',', '.');
								break;
						}
					}

					if(isset($option['column_text'])){
						$txt = $option['column_text'];
						foreach ($fields as $field2) {
							$txt = str_replace("{".$field2->name."}", $this->render_field($item[$field2->name], $field2->name), $txt);
						}
					} else $txt = $this->render_field($item[$column_name], $column_name);

					if(@$option['column_action'] ?? false){
						$actbtn = str_replace("{PRIMARY}", $primary."=".$item[$primary], $this->action_button);
						$actbtn = str_replace("{ID}", $item[$primary], $actbtn);
						$actbtn = str_replace("{NONCEID}", wp_create_nonce('kosan-'.$this->table.$item[$primary]), $actbtn);
						foreach ($fields as $field2) {
							$actbtn = str_replace("{".$field2->name."}", $this->render_field($item[$field2->name], $field2->name), $actbtn);
						}
						$txt .= $actbtn;
					}
				}
				return $txt;
			}
		}
		return $txt;
	}

	public function render_field($text, $column)
	{
		$table = new $this->table();
		$fields = $table->fields();
		
		foreach ($fields as $field) {
			if($field->name == $column){
				$option = $field->option;
				if(isset($option['column_alias'])){
					foreach ($option['column_alias'] as $key => $value) {
						if($text == $key)
							return $value;
					}
				}
			}
		}
		return $text;
	}

	public function delete($id) {
		$table = new $this->table();
        $primary = $table->getKeyName();
		$this->table::where($primary, $id)->delete();
	}

	public function bulkDelete($ids) {
		$table = new $this->table();
        $primary = $table->getKeyName();
		$this->table::whereIn($primary, $ids)->delete();
	}

	public function get_bulk_actions() {
		return $this->actions;
	}

	function get_sortable_columns() {
		$table = new $this->table();
		$fields = $table->fields();
		$sortable_columns = [];

		foreach ($fields as $field) {
			$option = $field->option;
			if($option != [] && @$option['column'] ?? false == true && @$option['column_sortable'] ?? false == true){
				array_push($sortable_columns, $field->name);
			}
		}
		return $sortable_columns;
	}	

	function get_searchable_columns() {
		$table = new $this->table();
		$fields = $table->fields();
		$sortable_columns = [];

		foreach ($fields as $field) {
			$option = $field->option;
			if($option != [] && @$option['column'] ?? false == true && @$option['column_searchable'] ?? false == true){
				array_push($sortable_columns, $field->name);
			}
		}
		return $sortable_columns;
	}	

	function column_cb($item, $id = 'id') {
		return sprintf(
		  '<input type="checkbox" name="bulk[]" value="%s" />', $item[$id]
		);
	}

	function prepare_items() {
		$table = $this->table;
		$per_page = $this->per_page;
		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		
		$items = $table::skip($offset)->take($per_page);

		if ( ! empty( $_REQUEST['s'] ) ) {
			// if(is_array($this->search_column)){
			foreach ($this->get_searchable_columns() as $search) {
				$item->where($search, $_REQUEST['s']);
			}
			// } else $item->where($this->search_column, $_REQUEST['s']);
		}
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$item->orderBy($_REQUEST['orderby'], $_REQUEST['order']);
		}

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$count = $table::count();
 
		$this->items = $items->get();
 
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page )
		) );
	}
}