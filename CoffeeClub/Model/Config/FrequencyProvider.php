<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class FrequencyProvider
{
    public const CONFIG_PATH = 'coffeeclub/general/frequencies';

    private ?array $cache = null;

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected LoggerInterface      $logger
    )
    {
    }

    /**
     * Get all configured frequencies as an associative array.
     *
     * @return array<string, array{label: string, modifier: string}>
     */
    public function getAll(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $rawValue = $this->scopeConfig->getValue(
            self::CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );

        if (!is_string($rawValue) || $rawValue === '') {
            return $this->cache = [];
        }

        $data = @unserialize($rawValue);

        // Fallback: newer Magento versions may save as JSON.
        if (!is_array($data)) {
            $data = json_decode($rawValue, true);
        }

        if (!is_array($data)) {
            $this->logger->warning(
                'CoffeeClub: Could not decode frequency configuration.'
            );
            return $this->cache = [];
        }

        $map = [];
        foreach ($data as $row) {
            if (!isset($row['code'], $row['label'], $row['modifier'])) {
                continue;
            }

            $code = trim((string)$row['code']);
            if ($code === '') {
                continue;
            }

            $map[$code] = [
                'label' => trim((string)$row['label']) ?: $code,
                'modifier' => trim((string)$row['modifier']),
            ];
        }

        return $this->cache = $map;
    }

    /**
     * @return array
     */
    public function getCodes(): array
    {
        return array_keys($this->getAll());
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isValid(string $code): bool
    {
        return isset($this->getAll()[$code]);
    }

    /**
     * @param string $code
     * @return string
     */
    public function getLabel(string $code): string
    {
        return $this->getAll()[$code]['label'] ?? $code;
    }

    /**
     * Calculate the next due date using the configured modifier.
     *
     * @param string $code
     * @param string|null $fromDate
     * @return string
     * @throws \InvalidArgumentException
     */
    public function calculateNextDate(string $code, ?string $fromDate = null): string
    {
        $map = $this->getAll();
        if (!isset($map[$code])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown frequency code "%s".', $code)
            );
        }

        $base = $fromDate !== null
            ? new \DateTime($fromDate)
            : new \DateTime();

        try {
            $base->modify($map[$code]['modifier']);
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid date modifier "%s" for frequency "%s".',
                    $map[$code]['modifier'],
                    $code
                ),
                0,
                $exception
            );
        }

        return $base->format('Y-m-d');
    }
}
