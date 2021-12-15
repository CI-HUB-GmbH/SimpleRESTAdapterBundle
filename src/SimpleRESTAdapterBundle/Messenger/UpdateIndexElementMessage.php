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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Messenger;

final class UpdateIndexElementMessage
{
    /**
     * @var int
     */
    private $entityId;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var string
     */
    private $endpointName;

    /**
     * @param int    $entityId
     * @param string $entityType
     * @param string $endpointName
     */
    public function __construct(int $entityId, string $entityType, string $endpointName)
    {
        $this->entityId = $entityId;
        $this->entityType = $entityType;
        $this->endpointName = $endpointName;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @return string
     */
    public function getEndpointName(): string
    {
        return $this->endpointName;
    }
}
