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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Search;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class SearchBackendReindexCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('pimcore:search-backend-reindex')
            ->setAliases(['search-backend-reindex'])
            ->setDescription('Re-indexes the backend search of pimcore');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // clear all data
        $db = \Pimcore\Db::get();
        $db->executeQuery('TRUNCATE `search_backend_data`;');

        $elementsPerLoop = 100;
        $types = ['asset', 'document', 'object'];

        foreach ($types as $type) {
            $baseClass = Service::getBaseClassNameForElement($type);
            $listClassName = '\\Pimcore\\Model\\' . $baseClass . '\\Listing';
            $list = new $listClassName();
            if (method_exists($list, 'setUnpublished')) {
                $list->setUnpublished(true);
            }

            if (method_exists($list, 'setObjectTypes')) {
                $list->setObjectTypes([
                    DataObject::OBJECT_TYPE_OBJECT,
                    DataObject::OBJECT_TYPE_FOLDER,
                    DataObject::OBJECT_TYPE_VARIANT,
                ]);
            }

            $elementsTotal = $list->getTotalCount();

            for ($i = 0; $i < (ceil($elementsTotal / $elementsPerLoop)); $i++) {
                $list->setLimit($elementsPerLoop);
                $list->setOffset($i * $elementsPerLoop);

                $this->output->writeln('Processing ' .$type . ': ' . ($list->getOffset() + $elementsPerLoop) . '/' . $elementsTotal);

                $elements = $list->load();
                foreach ($elements as $element) {
                    try {
                        //process page count, if not exists
                        if ($element instanceof Asset\Document && $element->getCustomSetting('document_page_count')) {
                            $element->processPageCount();
                        }

                        $searchEntry = Search\Backend\Data::getForElement($element);
                        if ($searchEntry instanceof Search\Backend\Data && $searchEntry->getId() instanceof Search\Backend\Data\Id) {
                            $searchEntry->setDataFromElement($element);
                        } else {
                            $searchEntry = new Search\Backend\Data($element);
                        }

                        $searchEntry->save();
                    } catch (\Exception $e) {
                        Logger::err((string) $e);
                    }
                }
                \Pimcore::collectGarbage();
            }
        }

        $db->executeQuery('OPTIMIZE TABLE search_backend_data;');

        return 0;
    }
}
