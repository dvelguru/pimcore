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

namespace Pimcore\Targeting;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for override handlers which can influence the debug toolbar form and override
 * targeting data based on form results.
 */
interface OverrideHandlerInterface
{
    const REQUEST_ATTRIBUTE = 'pimcore_targeting_overrides';

    /**
     * Add fields to the targeting toolbar override form
     */
    public function buildOverrideForm(FormBuilderInterface $form, Request $request): void;

    /**
     * Override targeting data from the override data as gathered from the form
     */
    public function overrideFromRequest(array $overrides, Request $request): void;
}
