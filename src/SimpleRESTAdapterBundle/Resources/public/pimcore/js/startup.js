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

pimcore.registerNS('pimcore.plugin.simpleRestAdapterBundle');
pimcore.plugin.simpleRestAdapterBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return 'pimcore.plugin.simpleRestAdapterBundle';
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },
});

const simpleRestAdapterBundle = new pimcore.plugin.simpleRestAdapterBundle();
