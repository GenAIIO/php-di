<?php

namespace GenAI\Di;

/**
 * Marks a class as a repository bean (an autowired Component — a stereotype
 * for the persistence layer). Build-time only.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Repository extends Component
{
}
