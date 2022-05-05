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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class ElementNotFoundException extends \RuntimeException implements EndpointExceptionInterface
{
    /**
     * @param int         $id
     * @param string|null $type
     * @param int         $code
     */
    public function __construct(int $id, string $type = null, int $code = Response::HTTP_NOT_FOUND)
    {
        if (null === $type) {
            parent::__construct(sprintf('Element with ID \'%s\' not found.', $id), $code);
        } else {
            parent::__construct(sprintf('Element with type \'%s\' and ID \'%s\' not found.', $type, $id), $code);
        }
    }
}
