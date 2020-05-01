<?php
namespace Kosan\Framework\Module;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class PostType {
    protected $name;
    protected $label;
    protected $slug;
    protected $meta_name = "";
    protected $container;
    protected $position = 4;
    protected $parent = "";
    protected $labels = [];
    protected $args = [
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => [ 'title', 'editor', 'thumbnail' ],
    ];

    public function __construct() {
        $this->middleware();
        $this->args['label'] = $this->label;
        $this->args['menu_position'] = $this->position;
        if($this->slug != '')
            $this->args['rewrite'] = ['slug' => $this->slug];
        if($this->parent != '')
            $this->args['show_in_menu'] = false;
        if($this->labels != [])
            $this->args['labels'] = $this->labels;
        add_action( 'init', [$this, 'load_post_type'] );
        if($this->parent != ''){
            add_action( 'admin_menu', [$this, 'load_menu'] );
        }
        if($this->meta_name != ''){
            add_action( 'carbon_fields_register_fields', [$this, 'crb_attach'] );
        }
		add_action( 'carbon_fields_post_meta_container_saved', [$this, 'admin_save_hook']);
        add_filter('single_template', [$this, 'custom_template']);
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        $this->func();
    }

    public function custom_template($single) {

        global $post;
    
        /* Checks for single template by post type */
        if ( $post->post_type == $this->name ) {
            $this->view();
            exit();
        }
    
        return $single;
    
    }

    public function crb_attach()
    {
        $this->container = Container::make('post_meta', $this->meta_name)
            ->where( 'post_type', '=', $this->name);
        $this->meta_box();
        
		$fields = apply_filters( "$this->name/tabs", []);

		if(is_array($fields) && 0 < count($fields)) :
            foreach($fields as $field) :
				$this->container->add_tab($field[0], $field[1]);
			endforeach;
		endif;
    }

    public function load_post_type()
    {
        flush_rewrite_rules(false);
        register_post_type( $this->name, $this->args );
    }
    
    public function load_menu()
    {
        add_submenu_page("edit.php?post_type=$this->parent", $this->label, $this->label, 'manage_options', "edit.php?post_type=$this->name");
    }

    public function admin_enqueue_scripts($hook)
    {
        global $post;
    
        if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' ) {
            if ( $this->name === $post->post_type ) {   
                wp_register_style('kosan-css', KOSAN_ASSETS . '/kosan.css');
                wp_enqueue_style('kosan-css');  
                $this->enqueue_scripts();
            }
        }
    }

    public function admin_save_hook($post_id) {
		if ( get_post_type( $post_id ) == $this->name ) {
            $this->save_hook($post_id);
        }
    }

    public function add_tab($name, $field, $priority = 10){
        add_filter("$this->name/tabs", function($fields) use($name, $field){
            $fields[] = [$name, $field];
            return $fields;
        }, $priority);
    }

    public function save_hook($post_id) {}
    public function meta_box() {}
    public function enqueue_scripts() {}
    public function func() {}
    public function view() {}
    public function middleware() {}
}