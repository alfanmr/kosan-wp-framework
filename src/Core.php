<?php
namespace Kosan\Framework\Module;

use Carbon_Fields\Carbon_Fields;
use Illuminate\Database\Capsule\Manager as DB;

class Core {

    protected $mainfile;
    protected $table_prefix;

    public function __construct($mainfile) {
        $this->mainfile = $mainfile;
    }

    public function init() {
        if( !defined( 'KOSAN_ASSETS' ) ) define('KOSAN_ASSETS', plugins_url( 'module/Assets', $this->mainfile));
        add_action( 'after_setup_theme', function(){
            Carbon_Fields::boot();
        });
        add_action('init', [$this, 'register_arista_session']);
        register_activation_hook( $this->mainfile, [$this, 'activate'] );
        register_deactivation_hook( $this->mainfile, [$this, 'deactivate'] );
        
		$this->filters();
		$this->actions();
        if(!class_exists('DB')){
            $capsule   = new DB;
            $host_data = explode(':', DB_HOST);

            $args = [
                'driver'    => 'mysql',
                'host'      => $host_data,
                'database'  => DB_NAME,
                'username'  => DB_USER,
                'password'  => DB_PASSWORD,
                'prefix'    => $this->table_prefix ?? '',
                'strict'    => false
            ];

            if(isset($host_data[1])) :
                $args['port'] = $host_data[1];
            endif;

            $capsule->addConnection($args);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
        }

        $this->update();
    }

    function register_arista_session(){
        if( ! session_id() ) {
            session_start();
        }
    }
    
    public function func_activate() {
        $this->activate();
        register_uninstall_hook( $this->mainfile, [$this, 'uninstall'] );
    }

    public function filters() {}

    public function actions() {}

    public function activate() {}

    public function deactivate() {}

    public function uninstall() {}

    public function update() {}
}