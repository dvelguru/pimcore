<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Targeting\Storage;

use Pimcore\Targeting\Model\VisitorInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Implements a 2-step storage handling a primary storage which needs a visitor ID (e.g. external DB)
 * and a fallback storage which is able to save data without a visitor ID (e.g. session or cookie).
 *
 * As soon as the primary storage is able to handle the request, data is migrated from the fallback to
 * the primary. Example flow (cookie + redis):
 *
 *  - Visitor visits page for the first time without a visitor ID. This request will write to the fallback storage
 *  (cookie) and a visitor ID is generated during this first request.
 *  - The next request already includes a visitorID. Upon encountering the visitor ID for the first time, data is
 *  migrated from the fallback to the primary and the fallback data is cleared (if configured).
 */
class FallbackStorage implements TargetingStorageInterface
{
    private TargetingStorageInterface $primaryStorage;

    private TargetingStorageInterface $fallbackStorage;

    private LoggerInterface $logger;

    private array $options = [];

    public function __construct(
        TargetingStorageInterface $primaryStorage,
        TargetingStorageInterface $fallbackStorage,
        LoggerInterface $logger,
        array $options = []
    ) {
        $this->primaryStorage = $primaryStorage;
        $this->fallbackStorage = $fallbackStorage;
        $this->logger = $logger;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'clear_after_migration' => false,
        ]);

        $resolver->setAllowedTypes('clear_after_migration', 'bool');
    }

    public function all(VisitorInfo $visitorInfo, string $scope): array
    {
        if ($visitorInfo->hasVisitorId()) {
            $this->migrateFromFallback($visitorInfo, $scope);

            return $this->primaryStorage->all($visitorInfo, $scope);
        } else {
            return $this->fallbackStorage->all($visitorInfo, $scope);
        }
    }

    public function has(VisitorInfo $visitorInfo, string $scope, string $name): bool
    {
        if ($visitorInfo->hasVisitorId()) {
            if (!$this->primaryStorage->has($visitorInfo, $scope, $name)) {
                $this->migrateFromFallback($visitorInfo, $scope);
            }

            return $this->primaryStorage->has($visitorInfo, $scope, $name);
        } else {
            return $this->fallbackStorage->has($visitorInfo, $scope, $name);
        }
    }

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, mixed $value): void
    {
        if ($visitorInfo->hasVisitorId()) {
            $this->primaryStorage->set($visitorInfo, $scope, $name, $value);
        } else {
            $this->fallbackStorage->set($visitorInfo, $scope, $name, $value);
        }
    }

    /**
     * {@inheritdoc }
     */
    public function get(VisitorInfo $visitorInfo, string $scope, string $name, mixed $default = null): mixed
    {
        if ($visitorInfo->hasVisitorId()) {
            if (!$this->primaryStorage->has($visitorInfo, $scope, $name)) {
                $this->migrateFromFallback($visitorInfo, $scope);
            }

            return $this->primaryStorage->get($visitorInfo, $scope, $name, $default);
        } else {
            return $this->fallbackStorage->get($visitorInfo, $scope, $name, $default);
        }
    }

    /**
     * {@inheritdoc }
     */
    public function clear(VisitorInfo $visitorInfo, string $scope = null): void
    {
        $this->fallbackStorage->clear($visitorInfo, $scope);

        if ($visitorInfo->hasVisitorId()) {
            $this->primaryStorage->clear($visitorInfo, $scope);
        }
    }

    public function migrateFromStorage(TargetingStorageInterface $storage, VisitorInfo $visitorInfo, string $scope): void
    {
        throw new \LogicException('migrateFromStorage() is not supported in FallbackStorage');
    }

    public function getCreatedAt(VisitorInfo $visitorInfo, string $scope): ?\DateTimeImmutable
    {
        if ($visitorInfo->hasVisitorId()) {
            return $this->primaryStorage->getCreatedAt($visitorInfo, $scope);
        } else {
            return $this->fallbackStorage->getCreatedAt($visitorInfo, $scope);
        }
    }

    public function getUpdatedAt(VisitorInfo $visitorInfo, string $scope): ?\DateTimeImmutable
    {
        if ($visitorInfo->hasVisitorId()) {
            return $this->primaryStorage->getUpdatedAt($visitorInfo, $scope);
        } else {
            return $this->fallbackStorage->getUpdatedAt($visitorInfo, $scope);
        }
    }

    private function migrateFromFallback(VisitorInfo $visitorInfo, string $scope): void
    {
        try {
            $this->primaryStorage->migrateFromStorage($this->fallbackStorage, $visitorInfo, $scope);

            if ($this->options['clear_after_migration']) {
                // clear fallback after successful migration
                $this->fallbackStorage->clear($visitorInfo, $scope);
            }
        } catch (\Throwable $e) {
            $this->logger->error((string) $e);
        }
    }
}
