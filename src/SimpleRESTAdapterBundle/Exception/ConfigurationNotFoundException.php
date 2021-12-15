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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class ConfigurationNotFoundException extends \RuntimeException implements EndpointExceptionInterface
{
    /**
     * @param string $configName
     * @param int    $code
     */
    public function __construct(string $configName, int $code = Response::HTTP_NOT_FOUND)
    {
        parent::__construct(sprintf('Invalid or unknown config name \'%s\'.', $configName), $code);
    }
}

