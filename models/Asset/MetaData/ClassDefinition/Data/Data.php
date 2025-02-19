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

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

use Pimcore\Model\DataObject\Traits\SimpleNormalizerTrait;
use Pimcore\Normalizer\NormalizerInterface;

abstract class Data implements DataDefinitionInterface, NormalizerInterface
{
    use SimpleNormalizerTrait;

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return mixed
     *
     * @deprecated use normalize() instead, will be removed in Pimcore 11
     */
    public function marshal(mixed $value, array $params = []): mixed
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.4',
            sprintf('%s is deprecated, please use normalize() instead. It will be removed in Pimcore 11.', __METHOD__)
        );

        return $this->normalize($value, $params);
    }

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return mixed
     *
     * @deprecated use denormalize() instead, will be removed in Pimcore 11
     */
    public function unmarshal(mixed $value, array $params = []): mixed
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.4',
            sprintf('%s is deprecated, please use denormalize() instead. It will be removed in Pimcore 11.', __METHOD__)
        );

        return $this->denormalize($value, $params);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return get_class($this);
    }

    public function transformGetterData(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function transformSetterData(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromEditMode(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataForResource(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromResource(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataForEditMode(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function isEmpty(mixed $data, array $params = []): bool
    {
        return empty($data);
    }

    public function checkValidity(mixed $data, array $params = []): void
    {
    }

    public function getDataForListfolderGrid(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromListfolderGrid(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function resolveDependencies(mixed $data, array $params = []): array
    {
        return [];
    }

    public function getVersionPreview(mixed $value, array $params = []): string
    {
        return (string)$value;
    }

    public function getDataForSearchIndex(mixed $data, array $params = []): ?string
    {
        if (is_scalar($data)) {
            return $params['name'] . ':' . $data;
        }

        return null;
    }
}
