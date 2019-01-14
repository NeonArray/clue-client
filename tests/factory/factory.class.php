<?php

namespace WP_Factory;

final class Factory {

    public $post;

    public function __construct() {
        $this->post = new Post();
    }
}
