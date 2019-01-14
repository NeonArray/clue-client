<?php

// Disable xdebug stack traces. They arent really used anyway and it was causing
// me to receive tons and tons of streaming output in the console when there was
// a file not found error (see below). Disabling the stack traces remedies this.
if ( function_exists( 'xdebug_disable' ) ) {
    xdebug_disable();
}

require_once dirname( dirname(__FILE__ ) ) . '/vendor/autoload.php';

require_once dirname( dirname(__FILE__ ) ) . '/tests/factory/factory.class.php';
require_once dirname( dirname(__FILE__ ) ) . '/tests/factory/post.class.php';
require_once dirname( dirname(__FILE__ ) ) . '/tests/factory/objects/wp_post.class.php';

// The abstract class has to be required by itself here or else all of the tests will
// fail with a file not found error. I believe this to be due to the alphabetical nature
// of the files. When it was named `abstract-trigger.php` everything run smooth. When
// I renamed it to `trigger.class.php` (something done for autoloading classes) it
// would throw the error. My hack-around is to require this file explicitly.
require_once dirname( dirname( __FILE__ ) ) . '/core/triggers/trigger.class.php';

require_once dirname( dirname(__FILE__ ) ) . '/tests/functions.php';

WP_Mock::bootstrap();
