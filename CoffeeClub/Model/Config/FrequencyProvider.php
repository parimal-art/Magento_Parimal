<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class FrequencyProvider
{
    public const CONFIG_PATH = 'coffeeclub/general/frequencies';

    /**
     * @var array<string, array{label: string, modifier: string}>|null
     */
    private ?array $cache = null;

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected LoggerInterface      $logger
    )
    {
    }

    /**
     * Return all configured frequencies keyed by code.
     *
     * @return array<string, array{label: string, modifier: string}>
     */
    public function getAll(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $raw = $this->scopeConfig->getValue(
            self::CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
        $rows = $this->decodeRows($raw);

        $map = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = trim((string)($row['code'] ?? ''));
            $label = trim((string)($row['label'] ?? ''));
            $modifier = trim((string)($row['modifier'] ?? ''));

            if ($code === '' || $modifier === '') {
                $this->logger->warning(sprintf(
                    'CoffeeClub: skipping frequency row with empty code/modifier: %s',
                    json_encode($row)
                ));
                continue;
            }

            $map[$code] = [
                'label' => $label !== '' ? $label : $code,
                'modifier' => $modifier,
            ];
        }

        return $this->cache = $map;
    }

    /**
     * @return string[]
     */
    public function getCodes(): array
    {
        return array_keys($this->getAll());
    }

    public function isValid(string $code): bool
    {
        return isset($this->getAll()[$code]);
    }

    public function getLabel(string $code): string
    {
        return $this->getAll()[$code]['label'] ?? $code;
    }

    /**
     * @throws \DateMalformedStringException
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

    /**
     * Decode the raw config value into an array of rows.
     *
     * Handles:
     *   1. Array (already decoded)
     *   2. JSON  — new format written by ArraySerialized backend model
     *   3. PHP-serialized array — fallback
     *   4. Legacy plain-text "code:Label:+N unit" (one per line)
     *
     * @param mixed $raw
     * @return array<int, array<string, string>>
     */
    private function decodeRows($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        // 1. JSON
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // 2. PHP serialized
        if (preg_match('/^a:\d+:{/', $raw)) {
            $unserialized = @unserialize($raw, ['allowed_classes' => false]);
            if (is_array($unserialized)) {
                return $unserialized;
            }
        }

        // 3. Legacy text format
        if (strpos($raw, ':') !== false) {
            $rows = [];
            $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode(':', $line, 3));
                if (count($parts) !== 3) {
                    continue;
                }
                [$code, $label, $modifier] = $parts;
                if ($code === '' || $modifier === '') {
                    continue;
                }
                $rows[] = [
                    'code' => $code,
                    'label' => $label,
                    'modifier' => $modifier,
                ];
            }
            if (!empty($rows)) {
                return $rows;
            }
        }

        return [];
    }
}
