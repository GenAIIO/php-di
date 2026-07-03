<?php

namespace GenAI\Di;

/**
 * Marks a class as a configuration component: it is registered as a bean, and
 * its #[Bean] methods become factory beans built by calling them on it.
 * Build-time only.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Configuration extends Component
{
}
