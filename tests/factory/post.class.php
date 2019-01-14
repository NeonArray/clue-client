<?php

namespace WP_Factory;

class Post {

    public function create( array $overrides = array() ) {
        return new \WP_Post( $overrides );
    }
}
