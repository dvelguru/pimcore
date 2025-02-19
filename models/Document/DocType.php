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

namespace Pimcore\Model\Document;

use Pimcore\Model;

/**
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method \Pimcore\Model\Document\DocType\Dao getDao()
 * @method void delete()
 * @method void save()
 */
class DocType extends Model\AbstractModel
{
    /**
     * ID of the document-type
     *
     * @internal
     *
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * Name of the document-type
     *
     * @internal
     *
     * @var string
     */
    protected string $name;

    /**
     * Group of document-types
     *
     * @internal
     *
     * @var string
     */
    protected string $group;

    /**
     * The specified controller
     *
     * @internal
     *
     * @var ?string
     */
    protected ?string $controller = null;

    /**
     * The specified template
     *
     * @internal
     *
     * @var string|null
     */
    protected ?string $template = null;

    /**
     * Type, must be one of the following: page,snippet,email
     *
     * @internal
     *
     * @var string
     */
    protected string $type;

    /**
     * @internal
     *
     * @var int
     */
    protected int $priority = 0;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $creationDate = null;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $modificationDate = null;

    /**
     * @internal
     */
    protected bool $staticGeneratorEnabled = false;

    /**
     * Static helper to retrieve an instance of Document\DocType by the given ID
     *
     * @param string $id
     *
     * @return self|null
     */
    public static function getById(string $id): ?DocType
    {
        if (empty($id)) {
            return null;
        }

        try {
            $docType = new self();
            $docType->getDao()->getById($id);

            return $docType;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Shortcut to quickly create a new instance
     *
     * @return DocType
     */
    public static function create(): DocType
    {
        $type = new self();
        $type->save();

        return $type;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setController(string $controller): static
    {
        $this->controller = $controller;

        return $this;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setGroup(string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getStaticGeneratorEnabled(): bool
    {
        return $this->staticGeneratorEnabled;
    }

    public function setStaticGeneratorEnabled(bool $staticGeneratorEnabled): void
    {
        $this->staticGeneratorEnabled = $staticGeneratorEnabled;
    }

    public function __clone()
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
