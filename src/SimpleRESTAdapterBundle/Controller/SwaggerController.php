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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SwaggerController extends FrontendController
{
    /**
     * @return Response
     */
    public function userInterfaceAction(): Response
    {
        return $this->renderTemplate('@SimpleRESTAdapter/Swagger/index.html.twig', [
            'configUrl' => $this->generateUrl('simple_rest_adapter_swagger_config'),
        ]);
    }

    /**
     * @return Response
     */
    public function configAction(): Response
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in(__DIR__ . '/../Resources/config')
            ->depth('== 0')
            ->name('swagger.yml');

        if (!$finder->hasResults()) {
            throw new NotFoundHttpException('Swagger config not found.');
        }

        $config = '';

        foreach ($finder as $file) {
            $config = $file->getContents();

            if (!empty($config)) {
                break;
            }
        }

        return new Response($config);
    }
}
