<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;

class SpacedLogFormatter
{
    private const FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n\n";

    public function __invoke(Logger $logger): void
    {
        $formatter = new LineFormatter(
            self::FORMAT,
            'Y-m-d H:i:s',
            true,
            true,
        );

        foreach ($logger->getLogger()->getHandlers() as $handler) {
            if (method_exists($handler, 'setFormatter')) {
                $handler->setFormatter($formatter);
            }
        }
    }
}
