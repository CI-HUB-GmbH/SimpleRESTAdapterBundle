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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\DataCollector;

use CIHub\Bundle\SimpleRESTAdapterBundle\Provider\AssetProvider;
use Exception;
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
     * @var AssetProvider
     */
    private $assetProvider;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router, AssetProvider $assetProvider)
    {
        $this->router = $router;
        $this->assetProvider = $assetProvider;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function collect($value, ConfigReader $reader): array
    {
        $id = $value->getId();
        $thumbnails = $reader->getAssetThumbnails();

        $data = [
            'id' => $id,
            'type' => 'asset',
        ];

        $data['binaryData'] = $this->assetProvider->getBinaryDataValues($value, $reader);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($value): bool
    {
        return $value instanceof Asset\Image;
    }

    /**
     * @param Asset     $asset
     * @param string    $type
     *
     * @return null|string
     *
     * @throws Exception
     */
    private function getChecksum(Asset $asset, $type = 'md5'): ?string
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
}
