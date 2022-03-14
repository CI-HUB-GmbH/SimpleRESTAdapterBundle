<?php
/**
 * Simple REST Adapter.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2021 CI HUB GmbH (https://www.ci-hub.com)
 * @license    https://github.com/ci-hub-gmbh/SimpleRESTAdapterBundle/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Controller;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use Pimcore\File;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Index\IndexQueryService;
use CIHub\Bundle\SimpleRESTAdapterBundle\Manager\IndexManager;
use CIHub\Bundle\SimpleRESTAdapterBundle\Provider\AssetProvider;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

class EndpointController extends BaseEndpointController
{
    /**
     * @return BinaryFileResponse|JsonResponse|Response
     */
    public function downloadAssetAction()
    {
        $crossOriginHeaders = [
            'Allow' => 'GET, OPTIONS',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'authorization',
        ];

        // Send empty response for OPTIONS requests
        if ($this->request->isMethod('OPTIONS')) {
            return new Response('', 204, $crossOriginHeaders);
        }

        $configuration = $this->getDataHubConfiguration();
        $reader = new ConfigReader($configuration->getConfiguration());

        // Check if request is authenticated properly
        $this->checkAuthentication($reader->getApiKey());

        $id = $this->request->get('id');

        // Check if required parameters are missing
        $this->checkRequiredParameters(['id' => $id]);

        $asset = Asset::getById($id);

        if (!$asset instanceof Asset) {
            throw $this->createElementNotFoundException($id);
        }

        $thumbnail = $this->request->get('thumbnail');
        $defaultPreviewThumbnail = $this->getParameter('simple_rest_adapter.default_preview_thumbnail');

        if (null !== $thumbnail && ($asset instanceof Asset\Image || $asset instanceof Asset\Document)) {
            // Explicitly disable WebP support, because Adobe's browser is Chromium based,
            // but e.g. Adobe InDesign doesn't support WebP images.
            // Asset\Image\Thumbnail\Processor::setHasWebpSupport(false);

            if (AssetProvider::CIHUB_PREVIEW_THUMBNAIL === $thumbnail && 'ciHub' === $reader->getType() &&
                !Thumbnail\Config::getByName(AssetProvider::CIHUB_PREVIEW_THUMBNAIL) instanceof Thumbnail\Config) {
                if ($asset instanceof Asset\Image) {
                    $file = $asset->getThumbnail($defaultPreviewThumbnail)->getLocalFile();
                } else {
                    $file = $asset->getImageThumbnail($defaultPreviewThumbnail)->getLocalFile();
                }
            } else if ($asset instanceof Asset\Image) {
                $file = $asset->getThumbnail($thumbnail)->getLocalFile();
            } else {
                $file = $asset->getImageThumbnail($thumbnail)->getLocalFile();

            }


            if ($asset instanceof Asset\Document && !$this->isPdfDocument($asset)) {
                $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
            } else {
                $disposition = ResponseHeaderBag::DISPOSITION_INLINE;
            }
        } else {
            $file = $asset->getLocalFile();
            $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        }


        $response = $this->file($file, pathinfo($asset, PATHINFO_BASENAME), $disposition);
        $response->headers->add($crossOriginHeaders);

        return $response;
    }

    /**
     * @param IndexManager      $indexManager
     * @param IndexQueryService $indexService
     *
     * @return JsonResponse
     */
    public function getElementAction(IndexManager $indexManager, IndexQueryService $indexService): JsonResponse
    {
        $configuration = $this->getDataHubConfiguration();
        $reader = new ConfigReader($configuration->getConfiguration());

        // Check if request is authenticated properly
        $this->checkAuthentication($reader->getApiKey());

        $id = $this->request->get('id');
        $type = $this->request->get('type');

        // Check if required parameters are missing
        $this->checkRequiredParameters(['id' => $id, 'type' => $type]);

        $indices = [];

        if ('asset' === $type && $reader->isAssetIndexingEnabled()) {
            $indices = [
                $indexManager->getIndexName(IndexManager::INDEX_ASSET, $this->config),
                $indexManager->getIndexName(IndexManager::INDEX_ASSET_FOLDER, $this->config),
            ];
        } elseif ('object' === $type && $reader->isObjectIndexingEnabled()) {
            $indices = array_merge(
                [$indexManager->getIndexName(IndexManager::INDEX_OBJECT_FOLDER, $this->config)],
                array_map(function ($className) use ($indexManager) {
                    return $indexManager->getIndexName(strtolower($className), $this->config);
                }, $reader->getObjectClassNames())
            );
        }

        foreach ($indices as $index) {
            try {
                $result = $indexService->get($id, $index);
            } catch (Missing404Exception $exception) {
                $result = [];
            }

            if (isset($result['found']) && true === $result['found']) {
                break;
            }
        }

        if (empty($result) || false === $result['found']) {
            throw $this->createElementNotFoundException($id, $type);
        }

        return $this->json($this->buildResponse($result, $reader));
    }

    /**
     * @param IndexManager            $indexManager
     * @param IndexQueryService       $indexService
     *
     * @return JsonResponse
     */
    public function searchAction(IndexManager $indexManager, IndexQueryService $indexService): JsonResponse
    {
        $configuration = $this->getDataHubConfiguration();
        $reader = new ConfigReader($configuration->getConfiguration());

        // Check if request is authenticated properly
        $this->checkAuthentication($reader->getApiKey());

        $indices = [];

        if ($reader->isAssetIndexingEnabled()) {
            $indices = [$indexManager->getIndexName(IndexManager::INDEX_ASSET, $this->config)];
        }

        if ($reader->isObjectIndexingEnabled()) {
            $indices = array_merge(
                $indices,
                array_map(function ($className) use ($indexManager) {
                    return $indexManager->getIndexName(strtolower($className), $this->config);
                }, $reader->getObjectClassNames())
            );
        }

        $search = $indexService->createSearch();
        $this->applySearchSettings($search);
        $this->applyQueriesAndAggregations($search, $reader);

        $result = $indexService->search(implode(',', $indices), $search->toArray());

        return $this->json($this->buildResponse($result, $reader));
    }

    /**
     * @param IndexManager      $indexManager
     * @param IndexQueryService $indexService
     *
     * @return JsonResponse
     */
    public function treeItemsAction(IndexManager $indexManager, IndexQueryService $indexService): JsonResponse
    {
        $configuration = $this->getDataHubConfiguration();
        $reader = new ConfigReader($configuration->getConfiguration());

        // Check if request is authenticated properly
        $this->checkAuthentication($reader->getApiKey());

        $type = $this->request->get('type');

        // Check if required parameters are missing
        $this->checkRequiredParameters(['type' => $type]);

        $parentId = $this->request->get('parent_id', '1');
        $includeFolders = filter_var(
            $this->request->get('include_folders', true),
            FILTER_VALIDATE_BOOLEAN
        );

        $indices = [];

        if ('asset' === $type && $reader->isAssetIndexingEnabled()) {
            $indices = [$indexManager->getIndexName(IndexManager::INDEX_ASSET, $this->config)];

            if (true === $includeFolders) {
                $indices[] = $indexManager->getIndexName(IndexManager::INDEX_ASSET_FOLDER, $this->config);
            }
        } elseif ('object' === $type && $reader->isObjectIndexingEnabled()) {
            $indices = array_map(function ($className) use ($indexManager) {
                return $indexManager->getIndexName(strtolower($className), $this->config);
            }, $reader->getObjectClassNames());

            if (true === $includeFolders) {
                $indices[] = $indexManager->getIndexName(IndexManager::INDEX_OBJECT_FOLDER, $this->config);
            }
        }

        $search = $indexService->createSearch();
        $this->applySearchSettings($search);
        $this->applyQueriesAndAggregations($search, $reader);
        $search->addQuery(new MatchQuery('system.parentId', $parentId));

        $result = $indexService->search(implode(',', $indices), $search->toArray());

        return $this->json($this->buildResponse($result, $reader));
    }

    /**
     * Checks whether the Asset\Document is a PDF file or not.
     *
     * @param Asset\Document $asset
     *
     * @return bool
     */
    private function isPdfDocument(Asset\Document $asset): bool
    {
        $mimeTypeAndFileExtension = sprintf(
            '%s .%s',
            $asset->getMimetype(),
            File::getFileExtension($asset->getFilename())
        );

        return false !== strpos($mimeTypeAndFileExtension, 'pdf');
    }
}
