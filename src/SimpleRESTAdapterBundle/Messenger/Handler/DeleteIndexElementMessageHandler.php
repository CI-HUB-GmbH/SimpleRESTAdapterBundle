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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Messenger\Handler;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Index\IndexPersistenceService;
use CIHub\Bundle\SimpleRESTAdapterBundle\Manager\IndexManager;
use CIHub\Bundle\SimpleRESTAdapterBundle\Messenger\DeleteIndexElementMessage;

final class DeleteIndexElementMessageHandler implements MessageHandlerInterface
{
    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @var IndexPersistenceService
     */
    private $indexService;

    /**
     * @param IndexManager            $indexManager
     * @param IndexPersistenceService $indexService
     */
    public function __construct(IndexManager $indexManager, IndexPersistenceService $indexService)
    {
        $this->indexManager = $indexManager;
        $this->indexService = $indexService;
    }

    /**
     * @param DeleteIndexElementMessage $message
     */
    public function __invoke(DeleteIndexElementMessage $message): void
    {
        switch ($message->getEntityType()) {
            case 'asset':
                $element = Asset::getById($message->getEntityId());
                break;
            case 'object':
                $element = DataObject\AbstractObject::getById($message->getEntityId());
                break;
            default:
                $element = null;
        }

        if (!$element instanceof ElementInterface) {
            return;
        }

        $this->indexService->delete(
            $element,
            $this->indexManager->getIndexName($element, $message->getEndpointName())
        );
    }
}
