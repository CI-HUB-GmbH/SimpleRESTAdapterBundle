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

use Pimcore\Model\DataObject\Data\ImageGallery;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

final class ImageGalleryDataCollector implements DataCollectorInterface
{
    /**
     * @var HotspotImageDataCollector
     */
    private $hotspotImageDataCollector;

    /**
     * @param HotspotImageDataCollector $hotspotImageDataCollector
     */
    public function __construct(HotspotImageDataCollector $hotspotImageDataCollector)
    {
        $this->hotspotImageDataCollector = $hotspotImageDataCollector;
    }

    /**
     * {@inheritdoc}
     */
    public function collect($value, ConfigReader $reader): array
    {
        $data = [];
        $items = $value->getItems() ?? [];

        foreach ($items as $item) {
            $data[] = $this->hotspotImageDataCollector->collect($item, $reader);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($value): bool
    {
        return $value instanceof ImageGallery;
    }
}
