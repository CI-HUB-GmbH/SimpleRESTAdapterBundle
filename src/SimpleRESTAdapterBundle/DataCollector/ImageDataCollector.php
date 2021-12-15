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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\DataCollector;

use Pimcore\Model\Asset;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

final class ImageDataCollector implements DataCollectorInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function collect($value, ConfigReader $reader): array
    {
        $id = $value->getId();
        $thumbnails = $reader->getAssetThumbnails();

        $data = [
            'id' => $id,
            'type' => 'asset',
        ];

        if ($reader->isOriginalImageAllowed()) {
            $data['binaryData']['original'] = [
                'checksum' => $value->getChecksum(),
                'path' => $this->router->generate('simple_rest_adapter_endpoints_download_asset', [
                    'config' => $reader->getName(),
                    'id' => $id,
                ], UrlGeneratorInterface::ABSOLUTE_PATH),
                'filename' => $value->getFilename(),
            ];
        }

        // Explicitly disable WebP support, because Adobe's browser is Chromium based,
        // but e.g. Adobe InDesign doesn't support WebP images.
        Asset\Image\Thumbnail\Processor::setHasWebpSupport(false);

        foreach ($thumbnails as $thumbnailName) {
            $thumbnail = $value->getThumbnail($thumbnailName);

            $data['binaryData'][$thumbnailName] = [
                'checksum' => $thumbnail->getChecksum(),
                'path' => $this->router->generate('simple_rest_adapter_endpoints_download_asset', [
                    'config' => $reader->getName(),
                    'id' => $id,
                    'thumbnail' => $thumbnailName,
                ], UrlGeneratorInterface::ABSOLUTE_PATH),
                'filename' => pathinfo($thumbnail->getFileSystemPath(), PATHINFO_BASENAME),
            ];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($value): bool
    {
        return $value instanceof Asset\Image;
    }
}
