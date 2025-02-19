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

namespace Pimcore\Bundle\AdminBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @internal
 */
final class PimcoreAdminExtension extends Extension
{
    const PARAM_DATAOBJECTS_NOTES_EVENTS_TYPES = 'pimcore_admin.dataObjects.notes_events.types';

    const PARAM_ASSETS_NOTES_EVENTS_TYPES = 'pimcore_admin.assets.notes_events.types';

    const PARAM_DOCUMENTS_NOTES_EVENTS_TYPES = 'pimcore_admin.documents.notes_events.types';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');

        $loader->load('security_services.yaml');
        $loader->load('event_listeners.yaml');
        $loader->load('serializer.yaml');
        $loader->load('export.yaml');
        $loader->load('aliases.yaml');

        //Set Config for GDPR data providers to container parameters
        $container->setParameter('pimcore.gdpr-data-extrator.dataobjects', $config['gdpr_data_extractor']['dataObjects']);
        $container->setParameter('pimcore.gdpr-data-extrator.assets', $config['gdpr_data_extractor']['assets']);

        //Set Config for Notes/Events Types to container parameters
        $container->setParameter(self::PARAM_DATAOBJECTS_NOTES_EVENTS_TYPES, $config['objects']['notes_events']['types']);
        $container->setParameter(self::PARAM_ASSETS_NOTES_EVENTS_TYPES, $config['assets']['notes_events']['types']);
        $container->setParameter(self::PARAM_DOCUMENTS_NOTES_EVENTS_TYPES, $config['documents']['notes_events']['types']);
        $container->setParameter('pimcore_admin.csrf_protection.excluded_routes', $config['csrf_protection']['excluded_routes']);
        $container->setParameter('pimcore_admin.admin_languages', $config['admin_languages']);
        $container->setParameter('pimcore_admin.custom_admin_path_identifier', $config['custom_admin_path_identifier']);
        $container->setParameter('pimcore_admin.custom_admin_route_name', $config['custom_admin_route_name']);

        $container->setParameter('pimcore_admin.config', $config);

        // unauthenticated routes do not double-check for authentication
        $container->setParameter('pimcore_admin.unauthenticated_routes', $config['unauthenticated_routes']);
        $container->setParameter('pimcore_admin.translations.path', $config['translations']['path']);
    }
}
