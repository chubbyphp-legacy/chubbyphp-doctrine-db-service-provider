<?php

declare(strict_types=1);

/*
 * (c) Fabien Potencier <fabien@symfony.com> (https://github.com/silexphp/Silex-Providers)
 */

namespace Chubbyphp\DoctrineDbServiceProvider\Logger;

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Logging\SQLLogger;

final class DoctrineDbalLogger implements SQLLogger
{
    const MAX_STRING_LENGTH = 32;
    const BINARY_DATA_VALUE = '(binary value)';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        if (is_array($params)) {
            foreach ($params as $index => $param) {
                if (!is_string($params[$index])) {
                    continue;
                }

                // non utf-8 strings break json encoding
                if (!preg_match('//u', $params[$index])) {
                    $params[$index] = self::BINARY_DATA_VALUE;
                    continue;
                }

                if (self::MAX_STRING_LENGTH < mb_strlen($params[$index], 'UTF-8')) {
                    $params[$index] = mb_substr($params[$index], 0, self::MAX_STRING_LENGTH - 6, 'UTF-8').' [...]';
                    continue;
                }
            }
        }

        if (null !== $this->logger) {
            $this->log($sql, null === $params ? array() : $params);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }

    /**
     * Logs a message.
     *
     * @param string $message A message to log
     * @param array  $params  The context
     */
    private function log($message, array $params)
    {
        $this->logger->debug($message, $params);
    }
}
