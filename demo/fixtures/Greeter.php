<?php

namespace Demo;

use GenAI\Di\Service;
use GenAI\Di\Value;

#[Service]
class Greeter
{
    public function __construct(
        private Clock $clock,
        #[Value('${app.name}')] private string $appName,   // config value, baked at build
        #[Value('Hi')] private string $prefix              // literal
    ) {
    }
}
