<?php

namespace Demo;

/**
 * A plain class — not a #[Service]. It is produced by AppConfig's #[Bean] method,
 * not autowired directly.
 */
class Mailer
{
    public function __construct(
        private Clock $clock
    ) {
    }
}
