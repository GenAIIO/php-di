<?php

namespace Demo;

use GenAI\Di\Bean;
use GenAI\Di\Configuration;

#[Configuration]
class AppConfig
{
    #[Bean]
    public function mailer(Clock $clock): Mailer
    {
        return new Mailer($clock);
    }
}
