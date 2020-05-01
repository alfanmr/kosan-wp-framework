<?php
namespace Kosan\Framework\Module;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class OptionPageCarbon {
    protected $name = "";
    protected $label = "";
    protected $title = "";
    protected $position = 2;
    protected $priority = 10;
    protected $icon = null;
    protected $container;
    protected $parent = null;

    public function __construct($parent = null) {
        if($parent != null) $this->parent = $parent;
        $this->middleware();
        add_action( 'carbon_fields_register_fields', [$this, 'crb_attach'], $this->priority);
        if(@$_GET['page'] == $this->name){
            add_action( 'admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'] );
            add_action( 'carbon_fields_theme_options_container_saved', [$this, 'admin_save_hook']);
        }
        $this->func();
    }

    public function get_name()
    {
        return $this->container;
    }

    public function set_name($name)
    {
        $this->name = $name;
    }

    public function crb_attach()
    {
        $this->container = Container::make( 'theme_options', $this->label )
            ->set_page_menu_position($this->position);
        if($this->name != "")
            $this->container->set_page_file( $this->name );
        if($this->title != "")
            $this->container->set_page_menu_title( $this->title );
        if($this->parent != null)
            $this->container->set_page_parent($this->parent);
        if($this->icon != null)
            $this->container->set_icon($this->icon);
        $this->view();
            
		$fields = apply_filters( "$this->name/tabs", []);

		if(is_array($fields) && 0 < count($fields)) :
            foreach($fields as $field) :
				$this->container->add_tab($field[0], $field[1]);
			endforeach;
        endif;
    }

    public function admin_enqueue_scripts($hook)
    {   
		wp_register_style('kosan-css', KOSAN_ASSETS . '/kosan.css');
		wp_enqueue_style('kosan-css');
        $this->enqueue_scripts();
    }

    public function admin_save_hook() {
        $title = strtolower($this->label);
        $title = preg_replace('/\s+/', '_', $title);
		if (wp_create_nonce('carbon_fields_container_'.$title.'_nonce') != $_REQUEST['carbon_fields_container_'.$title.'_nonce']) {
			return false;
        }
        $this->save_hook();
    }
    
    public function add_tab($name, $field, $priority = 10){
        add_filter("$this->name/tabs", function($fields) use($name, $field){
            $fields[] = [$name, $field];
            return $fields;
        }, $priority);
    }

    public function save_hook() {}
    public function view() {}
    public function enqueue_scripts() {}
    public function func() {}
    public function middleware() {}
}
