<?php

/**
 * Build-time: scan for the DI attributes and compile a container file.
 *
 *   composer install
 *   php example.php
 *
 * The scanner finds ComponentProcessor by type and runs it over the fixtures:
 *   #[Service] Clock, Greeter        -> autowired beans
 *   #[Repository] UserRepository     -> autowired bean
 *   #[Configuration] AppConfig       -> bean, and its #[Bean] mailer() a factory bean
 *   #[Value('Hi')] on Greeter $prefix-> a literal
 */

use GenAI\Attribute\Context;
use GenAI\Attribute\Scanner;

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/vendor/autoload.php';

@mkdir(__DIR__ . '/cache', 0777, true);

// Flat config for #[Value('${...}')] — in a real build you'd populate this from
// your config source (e.g. the same .ini/.env php-property reads).
$parameters = array(
    'app.name'    => 'PujaCMS',
    'app.version' => '1.0.0',
);

$scanner = new Scanner($loader);
$scanner->scan([
    'Demo',                    // the annotated classes (targets)
    'GenAI\\Di\\Processor',    // ships ComponentProcessor
]);
$scanner->compile(new Context(__DIR__ . '/config', __DIR__ . '/cache', $parameters));

echo "===== cache/Container.php =====\n";
echo file_get_contents(__DIR__ . '/cache/Container.php');
