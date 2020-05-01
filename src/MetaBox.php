<?php
namespace Kosan\Framework\Module;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class MetaBox {
    protected $name;
    protected $post_type;
    protected $container;

    public function __construct() {
        $this->middleware();
        add_action( 'carbon_fields_register_fields', [$this, 'crb_attach'] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        add_action( 'carbon_fields_post_meta_container_saved', 'admin_save_hook' );
        $this->func();
    }

    public function admin_save_hook($post_id) {
		if ( get_post_type( $post_id ) == $this->post_type ) {
            $this->save_hook($post_id);
        }
    }

    public function crb_attach()
    {
        $this->container = Container::make('post_meta', $this->name)
            ->where( 'post_type', '=', $this->post_type);
        $this->view();
    }

    public function admin_enqueue_scripts($hook)
    {
        global $post;
        if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' ) {
            if ( $this->post_type === $post->post_type ) {     
                wp_register_style('kosan-css', KOSAN_ASSETS . '/kosan.css');
                wp_enqueue_style('kosan-css');  
                $this->enqueue_scripts();
            }
        }
    }

    public function save_hook($post_id) {}
    public function enqueue_scripts() {}
    public function func() {}
    public function view() {}
    public function middleware() {}
}