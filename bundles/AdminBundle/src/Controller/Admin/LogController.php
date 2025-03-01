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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Helper\QueryParams;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Log\Handler\ApplicationLoggerDb;
use Pimcore\Tool\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
class LogController extends AdminController implements KernelControllerEventInterface
{
    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$this->getAdminUser()->isAllowed('application_logging')) {
            throw new AccessDeniedHttpException("Permission denied, user needs 'application_logging' permission.");
        }
    }

    /**
     * @Route("/log/show", name="pimcore_admin_log_show", methods={"GET", "POST"})
     *
     *
     */
    public function showAction(Request $request, Connection $db): JsonResponse
    {
        $qb = $db->createQueryBuilder();
        $qb
            ->select('*')
            ->from(ApplicationLoggerDb::TABLE_NAME)
            ->setFirstResult((int) $request->get('start', 0))
            ->setMaxResults((int) $request->get('limit', 50));

        $sortingSettings = QueryParams::extractSortingSettings(array_merge(
            $request->request->all(),
            $request->query->all()
        ));

        if ($sortingSettings['orderKey']) {
            $qb->orderBy($sortingSettings['orderKey'], $sortingSettings['order']);
        } else {
            $qb->orderBy('id', 'DESC');
        }

        $priority = $request->get('priority');
        if ($priority !== '-1' && ($priority == '0' || $priority)) {
            $levels = [];

            // add every level until the filtered one
            foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level) {
                $levels[] = $level;

                if ($priority === $level) {
                    break;
                }
            }

            $qb->andWhere($qb->expr()->in('priority', ':priority'));
            $qb->setParameter('priority', $levels, Connection::PARAM_STR_ARRAY);
        }

        if ($fromDate = $this->parseDateObject($request->get('fromDate'), $request->get('fromTime'))) {
            $qb->andWhere('timestamp > :fromDate');
            $qb->setParameter('fromDate', $fromDate, Types::DATETIME_MUTABLE);
        }

        if ($toDate = $this->parseDateObject($request->get('toDate'), $request->get('toTime'))) {
            $qb->andWhere('timestamp <= :toDate');
            $qb->setParameter('toDate', $toDate, Types::DATETIME_MUTABLE);
        }

        if (!empty($component = $request->get('component'))) {
            $qb->andWhere('component = ' . $qb->createNamedParameter($component));
        }

        if (!empty($relatedObject = $request->get('relatedobject'))) {
            $qb->andWhere('relatedobject = ' . $qb->createNamedParameter($relatedObject));
        }

        if (!empty($message = $request->get('message'))) {
            $qb->andWhere('message LIKE ' . $qb->createNamedParameter('%' . $message . '%'));
        }

        if (!empty($pid = $request->get('pid'))) {
            $qb->andWhere('pid LIKE ' . $qb->createNamedParameter('%' . $pid . '%'));
        }

        $totalQb = clone $qb;
        $totalQb->setMaxResults(null)
            ->setFirstResult(0)
            ->select('COUNT(id) as count');
        $total = $totalQb->executeQuery()->fetch();
        $total = (int) $total['count'];

        $stmt = $qb->executeQuery();
        $result = $stmt->fetchAllAssociative();

        $logEntries = [];
        foreach ($result as $row) {
            $fileobject = null;
            if ($row['fileobject']) {
                $fileobject = str_replace(PIMCORE_PROJECT_ROOT, '', $row['fileobject']);
            }

            $logEntry = [
                'id' => $row['id'],
                'pid' => $row['pid'],
                'message' => $row['message'],
                'timestamp' => $row['timestamp'],
                'priority' => $row['priority'],
                'fileobject' => $fileobject,
                'relatedobject' => $row['relatedobject'],
                'relatedobjecttype' => $row['relatedobjecttype'],
                'component' => $row['component'],
                'source' => $row['source'],
            ];

            $logEntries[] = $logEntry;
        }

        return $this->adminJson([
            'p_totalCount' => $total,
            'p_results' => $logEntries,
        ]);
    }

    private function parseDateObject(?string $date, ?string $time): ?\DateTime
    {
        if (empty($date)) {
            return null;
        }

        $pattern = '/^(?P<date>\d{4}\-\d{2}\-\d{2})T(?P<time>\d{2}:\d{2}:\d{2})$/';

        $dateTime = null;
        if (preg_match($pattern, $date, $dateMatches)) {
            if (!empty($time) && preg_match($pattern, $time, $timeMatches)) {
                $dateTime = new \DateTime(sprintf('%sT%s', $dateMatches['date'], $timeMatches['time']));
            } else {
                $dateTime = new \DateTime($date);
            }
        }

        return $dateTime;
    }

    /**
     * @Route("/log/priority-json", name="pimcore_admin_log_priorityjson", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function priorityJsonAction(Request $request): JsonResponse
    {
        $priorities[] = ['key' => '-1', 'value' => '-'];
        foreach (ApplicationLoggerDb::getPriorities() as $key => $p) {
            $priorities[] = ['key' => $key, 'value' => $p];
        }

        return $this->adminJson(['priorities' => $priorities]);
    }

    /**
     * @Route("/log/component-json", name="pimcore_admin_log_componentjson", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function componentJsonAction(Request $request): JsonResponse
    {
        $components[] = ['key' => '', 'value' => '-'];
        foreach (ApplicationLoggerDb::getComponents() as $p) {
            $components[] = ['key' => $p, 'value' => $p];
        }

        return $this->adminJson(['components' => $components]);
    }

    /**
     * @Route("/log/show-file-object", name="pimcore_admin_log_showfileobject", methods={"GET"})
     *
     * @param Request $request
     *
     * @return StreamedResponse|Response
     *
     * @throws \Exception
     */
    public function showFileObjectAction(Request $request): StreamedResponse|Response
    {
        $filePath = $request->get('filePath');
        $storage = Storage::get('application_log');

        if ($storage->fileExists($filePath)) {
            $fileHandle = $storage->readStream($filePath);
            $response = $this->getResponseForFileHandle($fileHandle);
            $response->headers->set('Content-Type', 'text/plain');
        } else {
            // Fallback to local path when file is not found in flysystem that might still be using the constant

            if (!filter_var($filePath, FILTER_VALIDATE_URL)) {
                if (!file_exists($filePath)) {
                    $filePath = PIMCORE_PROJECT_ROOT.DIRECTORY_SEPARATOR.$filePath;
                }
                $filePath = realpath($filePath);
                $fileObjectPath = realpath(PIMCORE_LOG_FILEOBJECT_DIRECTORY);
            } else {
                $fileObjectPath = PIMCORE_LOG_FILEOBJECT_DIRECTORY;
            }

            if (!str_starts_with($filePath, $fileObjectPath)) {
                throw new AccessDeniedHttpException('Accessing file out of scope');
            }

            if (file_exists($filePath)) {
                $fileHandle = fopen($filePath, 'rb');
                $response = $this->getResponseForFileHandle($fileHandle);
                $response->headers->set('Content-Type', 'text/plain');
            } else {
                $response = new Response();
                $response->headers->set('Content-Type', 'text/plain');
                $response->setContent('Path `'.$filePath.'` not found.');
                $response->setStatusCode(404);
            }
        }

        return $response;
    }

    /**
     * @param resource $fileHandle
     */
    private function getResponseForFileHandle($fileHandle): StreamedResponse
    {
        return new StreamedResponse(
            static function () use ($fileHandle) {
                while (!feof($fileHandle)) {
                    echo fread($fileHandle, 8192);
                }
                fclose($fileHandle);
            }
        );
    }
}
