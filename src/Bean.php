<?php

namespace GenAI\Di;

/**
 * Marks a method (inside a Component, typically a #[Configuration]) as a bean
 * factory: the bean is produced by calling this method on its declaring bean.
 *
 * Bean id resolution (first match wins):
 *   1. explicit name:      #[Bean('mailer')] / #[Bean(Mailer::class)]
 *   2. method return type: public function mailer(): Mailer   -> 'Mailer'
 *   3. method name:        public function mailer()           -> 'mailer'
 *
 * PHP 5.3 runtime caveat: a #[Bean] method is called at runtime (on the config
 * bean), so if your runtime is PHP 5.3 the method MUST NOT declare a
 * ': ReturnType' (return types are PHP 7+). Give the id explicitly instead with
 * #[Bean(Type::class)] — the attribute line is a comment on 5.3, so Type::class
 * inside it is harmless. On a PHP 7+/8 runtime you can rely on the return type.
 *
 * Build-time only (PHP 8) — read by ComponentProcessor.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Bean
{
    public function __construct(
        public ?string $name = null
    ) {
    }
}
