<?php

namespace GenAI\Di;

/**
 * The root class-level stereotype. #[Service], #[Repository] and #[Configuration]
 * all extend it, so a single ComponentProcessor handles them (matched with
 * ReflectionAttribute::IS_INSTANCEOF). You can use #[Component] directly too.
 *
 * Build-time only (PHP 8): read by the scanner, never loaded on the PHP 5.3
 * runtime (there #[Component] is a comment).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Component
{
}
