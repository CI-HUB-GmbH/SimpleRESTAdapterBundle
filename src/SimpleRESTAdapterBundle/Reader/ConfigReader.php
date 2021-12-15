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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Reader;

final class ConfigReader
{
    /**
     * @var array<string, array>
     */
    private $config;

    /**
     * @param array<string, array> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Merges the existing config with additional data.
     *
     * @param array<string, mixed> $data
     */
    public function add(array $data): void
    {
        $this->config = array_merge($this->config, $data);
    }

    /**
     * Extracts the correct object schema from a DataHub configuration.
     *
     * @param string $className
     *
     * @return array<string, array|string>
     */
    public function extractObjectSchema(string $className): array
    {
        foreach ($this->getObjectClasses() as $schema) {
            if ($schema['name'] === $className) {
                return $schema;
            }
        }

        return [];
    }

    /**
     * Filters the label setting values for given labels.
     *
     * @param array<int, string> $labels
     *
     * @return array<string, array>
     */
    public function filterLabelSettings(array $labels): array
    {
        $labelSettings = $this->getLabelSettings();

        $data = [];
        foreach ($labelSettings as $setting) {
            $id = $setting['id'];

            if (!in_array($id, $labels, true)) {
                continue;
            }

            unset($setting['id'], $setting['useInAggs']);

            $data[$id] = array_filter($setting);
        }

        return $data;
    }

    /**
     * Returns the API key to authenticate against.
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->config['deliverySettings']['apikey'] ?? null;
    }

    /**
     * Returns the Asset schema.
     *
     * @return array<string, array|string>
     */
    public function getAssetSchema(): array
    {
        return $this->config['schema']['assets'] ?? [];
    }

    /**
     * Returns the configured asset thumbnails.
     *
     * @return array<int, string>
     */
    public function getAssetThumbnails(): array
    {
        $assetSchema = $this->getAssetSchema();
        $thumbnails = $assetSchema['thumbnails'] ?? [];

        return is_array($thumbnails) ? $thumbnails : [];
    }

    /**
     * Returns the configured label settings.
     *
     * @return array<int, array>
     */
    public function getLabelSettings(): array
    {
        return $this->config['labelSettings'] ?? [];
    }

    /**
     * Returns the modification date (timestamp) of the configuration.
     *
     * @return int
     */
    public function getModificationDate(): int
    {
        return $this->config['general']['modificationDate'] ?? 0;
    }

    /**
     * Returns the name of the configuration.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->config['general']['name'] ?? null;
    }

    /**
     * Returns all configured DataObject classes.
     *
     * @return array<int, array>
     */
    public function getObjectClasses(): array
    {
        return $this->config['schema']['dataObjectClasses'] ?? [];
    }

    /**
     * Returns only the DataObject class names.
     *
     * @return array<int, string>
     */
    public function getObjectClassNames(): array
    {
        return array_map(
            static function ($class) {
                return $class['name'];
            },
            $this->getObjectClasses()
        );
    }

    /**
     * Returns the type of the configuration.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->config['general']['type'] ?? null;
    }

    /**
     * Returns a workspace by type.
     *
     * @param string $type
     *
     * @return array<int, array>
     */
    public function getWorkspace(string $type): array
    {
        return $this->config['workspaces'][$type] ?? [];
    }

    /**
     * Checks if asset indexing is enabled.
     *
     * @return bool
     */
    public function isAssetIndexingEnabled(): bool
    {
        $assetSchema = $this->getAssetSchema();

        return 'on' === ($assetSchema['enabled'] ?? null);
    }

    /**
     * Checks if object indexing is enabled.
     *
     * @return bool
     */
    public function isObjectIndexingEnabled(): bool
    {
        return !empty($this->getObjectClasses());
    }

    /**
     * Checks if the original image is allowed.
     *
     * @return bool
     */
    public function isOriginalImageAllowed(): bool
    {
        $assetSchema = $this->getAssetSchema();

        return 'on' === ($assetSchema['allowOriginalImage'] ?? null);
    }

    /**
     * Returns the whole configuration.
     *
     * @return array<string, array>
     */
    public function toArray(): array
    {
        return $this->config;
    }
}
