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
 * @copyright  Copyright (c) 2021 CI HUB GmbH (https://ci-hub.com)
 * @license    https://github.com/ci-hub-gmbh/SimpleRESTAdapterBundle/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Provider;

use Exception;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

final class AssetProvider implements ProviderInterface
{
    /**
     * This thumbnail needs to be passed with every image and document, so CI HUB can display a preview for it.
     */
    public const CIHUB_PREVIEW_THUMBNAIL = 'galleryThumbnail';

    /**
     * @var array
     */
    private $defaultPreviewThumbnail;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(array $defaultPreviewThumbnail, RouterInterface $router)
    {
        $this->defaultPreviewThumbnail = $defaultPreviewThumbnail;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function getIndexData(ElementInterface $element, ConfigReader $reader): array
    {
        /** @var Asset $element */
        Assert::isInstanceOf($element, Asset::class);

        $data = [
            'system' => $this->getSystemValues($element),
        ];

        if (!$element instanceof Asset\Folder) {
            $data = array_merge($data, array_filter([
                'binaryData' => $this->getBinaryDataValues($element, $reader),
                'metaData' => $this->getMetaDataValues($element),
            ]));
        }

        if ($element instanceof Asset\Image) {
            $data = array_merge($data, array_filter([
                'dimensionData' => [
                    'width' => $element->getWidth(),
                    'height' => $element->getHeight(),
                ],
                'xmpData' => $element->getXMPData() ?: null,
                'exifData' => $element->getEXIFData() ?: null,
                'iptcData' => $element->getIPTCData() ?: null,
            ]));
        }

        return $data;
    }

    /**
     * Returns the binary data values of an asset.
     *
     * @param Asset $asset
     * @param ConfigReader $reader
     *
     * @return array<string, array>
     * @throws Exception
     */
    private function getBinaryDataValues(Asset $asset, ConfigReader $reader): array
    {
        $data = [];

        $id = $asset->getId();

        try {
            $checksum = $this->getChecksum($asset);
        } catch (Exception $exception) {
            $checksum = null;
        }

        if ($asset instanceof Asset\Image) {
            $thumbnails = $reader->getAssetThumbnails();

            if ($reader->isOriginalImageAllowed()) {
                $data['original'] = [
                    'checksum' => $checksum,
                    'path' => $this->router->generate('simple_rest_adapter_endpoints_download_asset', [
                        'config' => $reader->getName(),
                        'id' => $id,
                    ], UrlGeneratorInterface::ABSOLUTE_PATH),
                    'filename' => $asset->getFilename(),
                ];
            }

            foreach ($thumbnails as $thumbnailName) {
                $thumbnail = $asset->getThumbnail($thumbnailName);

                try {
                    $thumbChecksum = $this->getChecksum($thumbnail->getAsset());
                } catch (Exception $exception) {
                    $thumbChecksum = null;
                }


                $data[$thumbnailName] = [
                    'checksum' => $thumbChecksum,
                    'path' => $this->router->generate('simple_rest_adapter_endpoints_download_asset', [
                        'config' => $reader->getName(),
                        'id' => $id,
                        'thumbnail' => $thumbnailName,
                    ], UrlGeneratorInterface::ABSOLUTE_PATH),
                    'filename' => $thumbnail->getAsset()->getFilename() //pathinfo($thumbnail->getAsset()->getKey(), PATHINFO_BASENAME),
                ];
            }

            // Make sure the preview thumbnail used by CI HUB is added to the list of thumbnails
            if (!array_key_exists(self::CIHUB_PREVIEW_THUMBNAIL, $data) && 'ciHub' === $reader->getType()) {
                if (Thumbnail\Config::getByName(self::CIHUB_PREVIEW_THUMBNAIL) instanceof Thumbnail\Config) {
                    $thumbnail = $asset->getThumbnail(self::CIHUB_PREVIEW_THUMBNAIL);
                } else {
                    $thumbnail = $asset->getThumbnail($this->defaultPreviewThumbnail);
                }

                try {
                    $thumbChecksum = $this->getChecksum($thumbnail->getAsset());
                } catch (Exception $exception) {
                    $thumbChecksum = null;
                }

                $data[self::CIHUB_PREVIEW_THUMBNAIL] = [
                    'checksum' => $thumbChecksum,
                    'path' => $this->router->generate('simple_rest_adapter_endpoints_download_asset', [
                        'config' => $reader->getName(),
                        'id' => $id,
                        'thumbnail' => self::CIHUB_PREVIEW_THUMBNAIL,
                    ], UrlGeneratorInterface::ABSOLUTE_PATH),
                    'filename' => $thumbnail->getAsset()->getKey() // pathinfo($thumbnail->get(), PATHINFO_BASENAME),
                ];
            }
        } else {
            $data['original'] = [
                'checksum' => $checksum,
                'path' => $this->router->generate('simple_rest_adapter_endpoints_download_asset', [
                    'config' => $reader->getName(),
                    'id' => $id,
                ], UrlGeneratorInterface::ABSOLUTE_PATH),
                'filename' => $asset->getFilename(),
            ];

            // Add the preview thumbnail for CI HUB
            if ($asset instanceof Asset\Document && 'ciHub' === $reader->getType()) {
                if (Thumbnail\Config::getByName(self::CIHUB_PREVIEW_THUMBNAIL) instanceof Thumbnail\Config) {
                    $thumbnail = $asset->getImageThumbnail(self::CIHUB_PREVIEW_THUMBNAIL);
                } else {
                    $thumbnail = $asset->getImageThumbnail($this->defaultPreviewThumbnail);
                }

                try {
                    $thumbChecksum = $this->getChecksum($thumbnail->getAsset());
                } catch (Exception $exception) {
                    $thumbChecksum = null;
                }

                $data[self::CIHUB_PREVIEW_THUMBNAIL] = [
                    'checksum' => $thumbChecksum,
                    'path' => $this->router->generate('simple_rest_adapter_endpoints_download_asset', [
                        'config' => $reader->getName(),
                        'id' => $id,
                        'thumbnail' => self::CIHUB_PREVIEW_THUMBNAIL,
                    ], UrlGeneratorInterface::ABSOLUTE_PATH),
                    'filename' => $thumbnail->getAsset()->getFilename() // pathinfo($thumbnail->getFileSystemPath(), PATHINFO_BASENAME),
                ];
            }
        }

        return $data;
    }

    /**
     * @param Asset     $asset
     * @param string    $type
     *
     * @return null|string
     *
     * @throws Exception
     */
    public function getChecksum(Asset $asset, $type = 'md5'): ?string
    {
        $file = $asset->getLocalFile();
        if (is_file($file)) {
            if ($type == 'md5') {
                return md5_file($file);
            } elseif ($type == 'sha1') {
                return sha1_file($file);
            } else {
                throw new Exception("hashing algorithm '" . $type . "' isn't supported");
            }
        }

        return null;
    }

    /**
     * Returns the meta data values of an asset.
     *
     * @param Asset $asset
     *
     * @return array<string, string>|null
     */
    private function getMetaDataValues(Asset $asset): ?array
    {
        $data = null;
        $metaData = $asset->getMetadata();

        foreach ($metaData as $item) {
            $data[$item['name']] = $item['data'];
        }

        return $data;
    }

    /**
     * Returns the system values of an asset.
     *
     * @param Asset $asset
     *
     * @return array<string, mixed>
     */
    private function getSystemValues(Asset $asset): array
    {
        $data = [
            'id' => $asset->getId(),
            'key' => $asset->getKey(),
            'fullPath' => $asset->getFullPath(),
            'parentId' => $asset->getParentId(),
            'type' => 'asset',
            'subtype' => $asset->getType(),
            'hasChildren' => $asset->hasChildren(),
            'creationDate' => $asset->getCreationDate(),
            'modificationDate' => $asset->getModificationDate(),
        ];

        if (!$asset instanceof Asset\Folder) {
            try {
                $checksum =  $this->getChecksum($asset);
            } catch (Exception $exception) {
                $checksum = null;
            }

            $data = array_merge($data, [
                'checksum' => $checksum,
                'mimeType' => $asset->getMimetype(),
                'fileSize' => $asset->getFileSize(),
            ]);
        }

        return $data;
    }
}
