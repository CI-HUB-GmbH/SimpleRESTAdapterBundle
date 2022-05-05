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

pimcore.registerNS('pimcore.plugin.datahub.adapter.simpleRest');
pimcore.plugin.datahub.adapter.simpleRest = Class.create(pimcore.plugin.datahub.adapter.graphql, {
    createConfigPanel: function (data) {
        new pimcore.plugin.simpleRestAdapterBundle.configuration.configItem(data, this);
    },

    deleteConfiguration: function (tree, record) {
        Ext.Msg.confirm(t('delete'), t('delete_message'), function (btn) {
            if ('yes' === btn) {
                Ext.Ajax.request({
                    url: Routing.generate('simple_rest_adapter_config_delete'),
                    params: {
                        name: record.data.id,
                    },
                });

                this.configPanel.getEditPanel().removeAll();
                record.remove();
            }
        }.bind(this));
    },

    openConfiguration: function (id) {
        const existingPanel = Ext.getCmp(`plugin_pimcore_datahub_configpanel_panel_${id}`);

        if (existingPanel) {
            this.configPanel.editPanel.setActiveTab(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: Routing.generate('simple_rest_adapter_config_get'),
            params: {
                name: id
            },
            success: (response) => {
                this.createConfigPanel(Ext.decode(response.responseText));
                pimcore.layout.refresh();
            },
        });
    },
});
