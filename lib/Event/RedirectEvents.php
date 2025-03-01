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

namespace Pimcore\Event;

final class RedirectEvents
{
    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const PRE_SAVE = 'pimcore.redirect.preSave';

    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const POST_SAVE = 'pimcore.redirect.postSave';

    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.redirect.preDelete';

    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.redirect.postDelete';

    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const PRE_BUILD = 'pimcore.redirect.preBuild';
}
