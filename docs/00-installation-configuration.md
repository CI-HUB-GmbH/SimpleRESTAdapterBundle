# Installation
This bundle depends on the [Pimcore DataHub](https://github.com/pimcore/data-hub), which needs to be installed first.

To install the Simple REST Adapter complete following steps:
* Install via composer
  ```
  composer require ci-hub/simple-rest-adapter-bundle:^1.0
  ```
* Enable via command-line (or inside the Pimcore extension manager)
  ```
  bin/console pimcore:bundle:enable SimpleRESTAdapterBundle
  ```
* Clear cache and reload Pimcore
  ```
  bin/console cache:clear --no-warmup
  ```

> Make sure, that the priority of the Pimcore DataHub is higher than the priority of the Simple REST Adapter.
> This can be specified as parameter of the `pimcore:bundle:enable` command or in the Pimcore extension manager.

## Bundle Configuration
Configure Elasticsearch hosts and index name prefix with Symfony configuration:

```yaml
# Default configuration for "SimpleRESTAdapterBundle"
simple_rest_adapter:

    # Prefix for index names.
    index_name_prefix:    datahub_restindex

    # List of Elasticsearch hosts.
    es_hosts:

        # Default:
        - localhost

    # Global Elasticsearch index settings.
    index_settings:

        # Defaults:
        number_of_shards:    5
        number_of_replicas:  0
        max_ngram_diff:      20
        analysis:
            analyzer:
                datahub_ngram_analyzer:
                    type:                custom
                    tokenizer:           datahub_ngram_tokenizer
                    filter:
                        - lowercase
                datahub_whitespace_analyzer:
                    type:                custom
                    tokenizer:           datahub_whitespace_tokenizer
                    filter:
                        - lowercase
            normalizer:
                lowercase:
                    type:                custom
                    filter:
                        - lowercase
            tokenizer:
                datahub_ngram_tokenizer:
                    type:                nGram
                    min_gram:            2
                    max_gram:            20
                    token_chars:
                        - letter
                        - digit
                datahub_whitespace_tokenizer:
                    type:                whitespace
```

> Supported Elasticsearch version: ^7.0

To make sure the indexing queue is processed and index is filled, following command has to be executed on
a regular basis, e.g. every 5 minutes.

```
*/5 * * * * php /var/www/html/bin/console messenger:consume datahub_es_index_queue --limit=20 --time-limit=240 >/dev/null 2>&1
```
