<?php
namespace Kosan\Framework\Module;

use eftec\bladeone\BladeOne;

class CustomUrl {
    protected $url;
    protected $blade;
    protected $viewDir = __DIR__ . '/View';

    public function __construct() {
        $this->middleware();
        add_action( 'init', [$this, 'endpoint_init'] );
        add_action( 'template_include', [$this, 'endpoint_template_include'] );
        $views = $this->viewDir;
        $compiledFolder = __DIR__ . '/../cache';
        $this->blade = new Blade($views, $compiledFolder, BladeOne::MODE_AUTO);
        $this->init();
    }
    
    public function endpoint_init() {
        add_rewrite_endpoint( $this->url, EP_ROOT );
        flush_rewrite_rules();
    }
    
    public function endpoint_template_include($template) {
        global $wp_query; 
        if ( is_home() && isset( $wp_query->query_vars[$this->url] ) ) {
            $this->func();
            $this->view();
            exit();
        }
        return $template;
    }

    public function init() {}
    public function view() {}
    public function func() {}
    public function middleware() {}
}
