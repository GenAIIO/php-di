<?php

namespace GenAI\Di;

/**
 * Injects a value into a constructor/factory parameter:
 *
 *   #[Value('sendmail')]      // a literal
 *   #[Value('%db.dsn%')]      // a container parameter (resolved via $c->get('db.dsn'))
 *
 * Build-time only (PHP 8). Read by ComponentProcessor while autowiring params.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Value
{
    public function __construct(
        public string $value
    ) {
    }
}
