<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;

/**
 * @see https://github.com/symfony/doctrine-bridge/blob/master/Logger/DbalLogger.php
 */
final class DoctrineDbalLogger implements SQLLogger
{
    const MAX_STRING_LENGTH = 32;
    const BINARY_DATA_VALUE = '(binary value)';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        if (is_array($params)) {
            $params = $this->escapeParams($params);
        }

        if (null !== $this->logger) {
            $this->logger->debug($sql, null === $params ? [] : $params);
        }
    }

    public function stopQuery(): void
    {
    }

    private function escapeParams(array $params): array
    {
        foreach ($params as $index => $param) {
            if (!is_string($param)) {
                continue;
            }

            // non utf-8 strings break json encoding
            if (!preg_match('//u', $param)) {
                $params[$index] = self::BINARY_DATA_VALUE;
                continue;
            }

            if (self::MAX_STRING_LENGTH < mb_strlen($param, 'UTF-8')) {
                $params[$index] = mb_substr($param, 0, self::MAX_STRING_LENGTH - 6, 'UTF-8').' [...]';
                continue;
            }
        }

        return $params;
    }
}
