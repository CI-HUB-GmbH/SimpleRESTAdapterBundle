# Docker Setup

If you are running Pimcore in a Docker Setup (e.g. [Pimcore Skeleton](https://github.com/pimcore/skeleton#docker)),
you can follow this example.

First, download and install the bundles:
```
# 1. Datahub
docker compose exec php-fpm composer require pimcore/data-hub
docker compose exec php-fpm php bin/console pimcore:bundle:enable PimcoreDataHubBundle
docker compose exec php-fpm php bin/console pimcore:bundle:install PimcoreDataHubBundle

# 2. SimpleRESTAdapterBundle
docker compose exec php-fpm composer require ci-hub/simple-rest-adapter-bundle
docker compose exec php-fpm bin/console pimcore:bundle:enable SimpleRESTAdapterBundle
```

Next, we need to add an Elasticsearch service so that the SimpleRESTAdapter can index
the configured data.

* Add the following sections to your `docker-compose.yaml`:
```
services:
    elasticsearch:
        image: elasticsearch:8.1.0
        container_name: 'elasticsearch'
        environment:
          - xpack.security.enabled=false
          - discovery.type=single-node
        ulimits:
          memlock:
            soft: -1
            hard: -1
          nofile:
            soft: 65536
            hard: 65536
        cap_add:
          - IPC_LOCK
        volumes:
          - 'elasticsearch-data:/usr/share/elasticsearch/data'
        ports:
          - '9200:9200'
          - '9300:9300'

# ...

volumes:
    # ...
    elasticsearch-data:
      driver: local
```

> Note: We are using Elasticsearch version 8.1 here – sometimes that might conflict with
> indices which were created on version 7.4. It looks like version 7.17 works too,
> but be careful – **you cannot downgrade** (at least not fast and easy).

* Configure the SimpleRESTAdapterBundle according to this [documentation section](00-installation-configuration.md#bundle-configuration).

> Note: If you are using Elasticsearch version 8.0 and above, you should set
> `tokenizer.datahub_ngram_tokenizer.type` as `ngram`, not `nGram`.

* To keep indexing running, you can add the corresponding messenger consume command to your supervisor configuration `.docker/supervisor.conf`:

```
command=php /var/www/html/bin/console messenger:consume datahub_es_index_queue --memory-limit=250M --time-limit=3600
```

> Note: This step is completely optional, and you could achieve the same with a crontab task for example.

## Checking the installation

To make sure the installation is correct, you can do the following checks.

* Be sure to have some Assets/DataObjects in your system, otherwise no data will be in the index.
* Check your DataHub endpoint configuration (see [here](01-endpoint-configuration.md) for details).
* Check your Elasticsearch indices here: `http://localhost:9200/_cat/indices`. If none are present, something went wrong.
* Now you can some API calls, for example from the Swagger page: `http://localhost/pimcore-datahub-webservices/simplerest/swagger`.
* If you can retrieve the data through those API calls, everything works as expected, and you are done!
