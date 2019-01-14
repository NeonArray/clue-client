<?php

function register_activation_hook() {
    return true;
}

function register_deactivation_hook() {
    return true;
}

function plugin_dir_path() {
    return dirname( dirname( __FILE__ ) ) . '/';
}

function invokeMethod( &$object, $methodName, array $parameters = array() ) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
}
