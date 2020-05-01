<?php
namespace Kosan\Framework\Module;

use Carbon_Fields\Field\Field;
use Carbon_Fields\Datastore\Datastore;

class CarbonStore extends Datastore {
    public function init() {

    }

    protected function get_key_for_field( Field $field ) {
        $key = '_' . $field->get_base_name();
        return $key;
    }

    protected function save_key_value_pair_with_autoload( $key, $value, $autoload ) {
        return;
    }

    public function load( Field $field ) {
        return null;
    }

    public function save( Field $field ) {}

    public function delete( Field $field ) {}
}