<?php

namespace WP_Factory;

class Menu_Item {

    public function create( array $override_values = array() ) {
        return new \WP_Post( $override_values );
    }
}
