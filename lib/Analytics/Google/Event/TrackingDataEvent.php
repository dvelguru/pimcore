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

namespace Pimcore\Analytics\Google\Event;

use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Analytics\Google\Config\Config;
use Pimcore\Analytics\SiteId\SiteId;
use Symfony\Contracts\EventDispatcher\Event;

class TrackingDataEvent extends Event
{
    private Config $config;

    private SiteId $siteId;

    private array $data;

    /**
     * @var CodeBlock[]
     */
    private array $blocks;

    private string $template;

    public function __construct(
        Config $config,
        SiteId $siteId,
        array $data,
        array $blocks,
        string $template
    ) {
        $this->config = $config;
        $this->siteId = $siteId;
        $this->data = $data;
        $this->blocks = $blocks;
        $this->template = $template;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getSiteId(): SiteId
    {
        return $this->siteId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return CodeBlock[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getBlock(string $block): CodeBlock
    {
        if (!isset($this->blocks[$block])) {
            throw new \InvalidArgumentException(sprintf('Invalid block "%s"', $block));
        }

        return $this->blocks[$block];
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
