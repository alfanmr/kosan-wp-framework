<?php
namespace Kosan\Framework\Module;

use Kosan\Framework\Module\List_WP;
use Kosan\Framework\Module\CarbonStore;
use eftec\bladeone\BladeOne;
use Carbon_Fields\Container;
use Carbon_Fields\Field;

class OptionPage {
    protected $name = null;
    protected $label = null;
    protected $title = null;
    protected $parent = null; 
    protected $position = 2;
    protected $priority = 20;
    protected $icon = null;
    protected $blade;
    protected $container;
	protected $has_checkbox = false;
    protected $table = "";
    protected $enable_search = false;
    protected $enable_input = false;
    protected $viewDir = __DIR__ . '/View';

    public function __construct($parent = null) {
        if($parent != null) $this->parent = $parent;
        $this->middleware();
        add_action('admin_menu', [$this, 'add_menu'], $this->priority);
        $this->func();
        $views = $this->viewDir;
        $compiledFolder = __DIR__ . '/../cache';
        $this->blade = new Blade($views, $compiledFolder, BladeOne::MODE_AUTO);
        
        if(@$_GET['page'] == $this->name){
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            if($this->enable_input){
                // add_submenu_page($this->name, $this->label, $this->title ?? $this->label, 'manage_options', $this->name.'-input', [ $this, 'view_input' ]);
                // add_action( 'submenu_file', [$this, 'hide_menu'] );
                add_action( 'carbon_fields_register_fields', [$this, 'crb_attach'] );
                add_action( 'carbon_fields_theme_options_container_saved', [$this, 'admin_save_hook']);
                if(@$_GET['page'] == $this->name.'-input')
                    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_input' ) );
            }
        }
    }

    public function get_name()
    {
        return $this->name;
    }

    public function set_name($name)
    {
        $this->name = $name;
    }

    public function add_menu()
    {
        if($this->parent == "")
            add_menu_page($this->label, $this->title ?? $this->label, 'manage_options', $this->name, [ $this, 'view' ], $this->icon, $this->position);
        else
            add_submenu_page($this->parent, $this->label, $this->title ?? $this->label, 'manage_options', $this->name, [ $this, 'view' ]);
    }

    public function hide_menu($submenu_file)
    {
        global $plugin_page;
    
        $hidden_submenus = array(
            $this->name.'-input' => true,
        );
    
        // Select another submenu item to highlight (optional).
        if ( $plugin_page && isset( $hidden_submenus[ $plugin_page ] ) ) {
            $submenu_file = $this->name;
        }
    
        // Hide the submenu.
        foreach ( $hidden_submenus as $submenu ) {
            remove_submenu_page( $this->name, $submenu );
        }
    
        return $submenu_file;
    }

    public function admin_save_hook() {
        $title = strtolower($this->label);
        $title = preg_replace('/\s+/', '_', $title);
		if ( $_GET['page'] !== $this->name.'-input' || wp_create_nonce('carbon_fields_container_'.$title.'_nonce') != $_REQUEST['carbon_fields_container_'.$title.'_nonce']) {
			return false;
        }
        $this->save_hook();
    }
    
    public function crb_attach()
    {
        $this->container = Container::make('theme_options', $this->title)
            ->set_page_parent($this->name."-")
            ->set_datastore(new CarbonStore())
            ->set_page_menu_title($this->title)
            ->set_page_file($this->name.'-input');
        $this->view_input();
    }

    public function view() {
        $this->preview();
        if($this->table != ""){
            $table = new List_WP();
            $table->table = $this->table;
            $table->actions = $this->bulk_action();
            $table->action_button = $this->action_button();
            $table->name = $this->title;
            $table->has_checkbox = $this->has_checkbox;
            
            if (@$_SERVER['REQUEST_METHOD'] == 'POST' && $table->validate_post()){
                if(isset($_POST['delete']) && check_admin_referer('kosan-'.$table->table.$_POST['delete'], '_wpnonce-'.$_POST['delete'])){
                    $table->delete($_POST["delete"]);
                }
                if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
                || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
                ) {
                    $table->bulkDelete($_POST["bulk"]);
                }
            }

            $table->prepare_items();
            add_thickbox();
            
            echo '<div class="wrap"><h1 class="wp-heading-inline">'.$this->title.'</h1>'; 
            if($this->enable_input)
                $this->show_input();
            echo '<hr class="wp-header-end">';
            echo '<form method="post">';
            if($this->enable_search)
                $table->search_box( 'Search', 'q' );
            $table->display();  
            echo '</form></div>';
        }
    }

    public function action_button()
    {
        $txt = '<div id="kosan-delete-{ID}" style="display:none;">'.$this->delete_txt().'</div>';
        $txt .= '<button style="display: none;" name="delete" value="{ID}" id="btn-kosan-{ID}">Ya</button>';
        $txt .= '<input type="hidden" name="_wpnonce-{ID}" value="{NONCEID}" />';
        $txt .= '<div class="row-actions"><span class="edit"><a href="admin.php?page='.$this->name.'-input&{PRIMARY}">Edit</a> | </span><span class="trash"><a href="#TB_inline?width=150&height=100&inlineId=kosan-delete-{ID}" class="thickbox submitdelete">Delete</a></span></div>';
        return $txt;
    }

    public function delete_txt()
    {
        $txt = '<center>Apakah anda yakin ingin menghapus data ini?</center></br>';
        $txt .= '<div style="margin: auto;width: 200px;text-align: center">
            <button class="button-primary" type="button" name="delete" onclick="jQuery(\'#btn-kosan-{ID}\').click()">Ya</button>
            <a class="button-secondary" onclick="tb_remove();">Tidak</a>
        </div>';
        return $txt;
    }

    public function show_input()
    {
        echo "<a href='admin.php?page=$this->name"."-input' class='page-title-action'>Add new</a>";
    }

    public function bulk_action()
    {
        return [
            'bulk-delete' => 'Delete'
        ];
    }

    public function view_input(){
		$table = new $this->table();
        $primary = $table->getKeyName();
        $fields = $table->fields();
        $inputs = [];
        $value = [];
		foreach ($fields as $field) {
			$option = $field->option;
			if($option != [] && isset($option['input'])){
                $option['input']['id'] = $field->name;
                array_push($inputs, $option['input']);
                $value[$field->name] = $option['input']['default_value'] ?? '';
			}
        }
        if(isset($_GET[$primary])){
            $value = $this->table::where($primary, $_GET[$primary])->first();
            $value != null ? $value = $value->toArray() : [];
        }
        foreach ($inputs as $input) {
            $field = Field::make($input['type'], $input['id'], $input['label'] ?? $input['id']);
            if($value[$input['id']] != '0000-00-00 00:00:00')
                $field->set_default_value($value[$input['id']]);
            if($input['type'] == 'select' || $input['type'] == 'multiselect')
                $field->set_options($input['option'] ?? []);
            if(isset($input['help']))
                $field->set_help_text($input['help']);
            if(isset($input['width']))
                $field->set_width($input['width']);
            if($input['required'] ?? false)
                $field->set_required(true);
            $this->container->add_fields([$field]);
        }
    }

    public function save_hook(){
        $nonce = strtolower("carbon_fields_container_$this->label"."_nonce");
        if(check_admin_referer($nonce, $nonce)){
            $table = new $this->table();
            $primary = $table->getKeyName();
            $post_data = [];
            foreach ($_POST['carbon_fields_compact_input'] as $key => $value) {
                $post_data[substr($key, 1)] = $value;
            }
            $fields = $table->fields();
            foreach ($fields as $field) {
                if(isset($field->option) && $field->name != $primary){
                    $option = $field->option;
                    if(!isset($post_data[$field->name])){
                        $post_data[$field->name] = $option['default_value'] ?? null;
                    }
                }
            }
            if($_REQUEST[$primary] != ""){
                $table = $this->table::where($primary, $_REQUEST[$primary])->update($post_data);
                wp_redirect("admin.php?page=$this->name"); exit;
            }
            foreach ($post_data as $key => $value) {
                $table->{$key} = $value;
            }
            $table->save();
            wp_redirect("admin.php?page=$this->name"); exit;
        }
    }

    
    public function admin_enqueue_scripts_input($hook)
    {
        wp_register_script('kosan-js', KOSAN_ASSETS . '/kosan-input.js');
        wp_enqueue_script('kosan-js');
        wp_register_style('kosan-css', KOSAN_ASSETS . '/kosan.css');
        wp_enqueue_style('kosan-css');
        $this->enqueue_scripts_input();
    }

	public function top_form_input() {
		if ( $_GET['page'] == $this->name.'-input' ){
			$url = admin_url( "admin.php?page=$this->name" );
			echo "<script type='text/javascript'>";
			echo "jQuery(document).ready(function(){";
			echo "	jQuery('#screen-meta-links').after(\"<a style='margin-top: 20px;' href='$url' class='button button-primary button-hero'><i style='vertical-align: text-bottom;' class='dashicons dashicons-arrow-left-alt'></i> Back to List</a>\");";
			echo "});";
			echo "</script>";
		}
	}

    public function enqueue_scripts() {}
    public function enqueue_scripts_input() {}
    public function func() {}
    public function preview() {}
    public function middleware() {}
    
    public function view_input_old() {
        if (@$_SERVER['REQUEST_METHOD'] == 'POST'){
            if(check_admin_referer('kosan-input')){
                $table = new $this->table();
                $primary = $table->getKeyName();
                if($_POST["kosan-$primary"] != ""){
                    $table = $this->table::where($primary, $_POST["kosan-$primary"])->update($_POST['kosan']);
                    wp_redirect("admin.php?page=$this->name"); exit;
                }
                foreach ($_POST['kosan'] as $key => $value) {
                    $table->{$key} = $value;
                }
                $table->save();
                wp_redirect("admin.php?page=$this->name"); exit;
            }
        }

		$table = new $this->table();
        $primary = $table->getKeyName();
        $fields = $table->fields();
        $input = [];
        $value = [];
		foreach ($fields as $field) {
			$option = $field->option;
			if($option != [] && isset($option['input'])){
                $option['input']['id'] = $field->name;
                array_push($input, $option['input']);
                $value[$field->name] = $option['input']['default_value'] ?? '';
			}
        }
        if(isset($_GET[$primary])){
            $value = $this->table::where($primary, $_GET[$primary])->first();
            $value != null ? $value = $value->toArray() : [];
        }
        $nonce = wp_create_nonce("kosan-input");
        echo $this->blade->run("base", [
            'title' => $this->title,
            'inputs' => $input,
            'nonce' => $nonce,
            'primary' => $primary,
            'value' => $value ?? []
        ]);
    }

}