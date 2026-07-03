<?php

namespace GenAI\Di;

/**
 * Marks a class as a service bean (an autowired Component). Build-time only.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Service extends Component
{
}
