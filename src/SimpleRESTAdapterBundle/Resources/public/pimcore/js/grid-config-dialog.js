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

pimcore.registerNS('pimcore.plugin.simpleRestAdapterBundle.configuration.gridConfigDialog');
pimcore.plugin.simpleRestAdapterBundle.configuration.gridConfigDialog = Class.create(pimcore.object.helpers.gridConfigDialog, {
    availableOperators: [
        'assetmetadatagetter',
        'fieldcollectiongetter',
        'objectfieldgetter',
        'propertygetter',
        'booleanformatter',
        'dateformatter',
        'text',
        'alias',
        'localeswitcher',
        'merge',
        'phpcode',
        'boolean',
        'isequal',
        'arithmetic',
        'base64',
        'elementcounter',
        'json',
        'lfexpander',
        'anonymizer',
        'caseconverter',
        'charcounter',
        'concatenator',
        'stringreplace',
        'substring',
        'translatevalue',
        'trimmer',
    ],
});
