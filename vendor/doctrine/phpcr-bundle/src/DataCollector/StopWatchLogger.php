<?php

namespace Doctrine\Bundle\PHPCRBundle\DataCollector;

use Jackalope\Transport\Logging\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class StopWatchLogger implements LoggerInterface
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(Stopwatch $stopwatch = null)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function startCall($method, array $params = null, array $env = null)
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('doctrine_phpcr', 'doctrine_phpcr');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopCall()
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('doctrine_phpcr');
        }
    }
}
