<?php

namespace GenAI\Di;

/**
 * Function/method-level twin of #[Value]: injects values into parameters by name,
 * from ONE attribute above the function instead of a #[Value] interleaved in the
 * parameter list. Each value uses the same grammar as #[Value]:
 *
 *   #[ParamValues(['host' => 'sendmail', 'dsn' => '%db.dsn%', 'name' => '${app.name}'])]
 *   public function __construct($host, $dsn, $name) {}
 *
 *   // named-argument form works too (param names that are valid identifiers):
 *   #[ParamValues(host: 'sendmail', name: '${app.name}')]
 *
 * Each entry: 'literal' | '${config.key}' (baked from build Context) | '%param%'
 * (runtime container parameter). A per-parameter #[Value] still wins over the map.
 *
 * Why: a single attribute line above the function reads as one #-comment under
 * PHP 5.3 (where attributes interleaved in the parameter list are awkward), while
 * PHP 8 reflection reads it at build time. Keep it on ONE line for 5.3 safety.
 *
 * Build-time only (PHP 8). Read by ComponentProcessor while autowiring params.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
class ParamValues
{
    /** @var array<string, string> parameter name => value spec */
    public array $values;

    public function __construct(array $values = [], string ...$named)
    {
        // Accept both the associative-array form and named arguments.
        $this->values = array_merge($values, $named);
    }
}
