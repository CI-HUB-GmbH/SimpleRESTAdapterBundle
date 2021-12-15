# Indexing Details
All data that is delivered by simple REST endpoints is indexed in Elasticsearch indices.
Queries and data delivery takes place directly out of Elasticsearch (not from the Pimcore database).

For each DataHub configuration separate Elasticsearch indices will be created and updated.

**Indexing of data** takes places asynchronously with a Symfony Messenger queue, and the consume queue command
`messenger:consume datahub_es_index_queue`. This command needs to be executed on a regular basis, e.g. every 5 minutes.

**Index mapping and queue filling** takes place automatically when creating and updating DataHub configurations.

Multiple indices are created per endpoint – one for each DataObject class, one for DataObject folders, one for Assets
and one for Asset folders.

**Tree Hierarchy Management**: The indexing process tries to keep a valid folder structure in an index.
Based on workspace settings a combined parent folder is calculated. This combined parent folder,
might be a sub folder of the parent folder in Pimcore folder structure, and all element paths are rewritten to it.

Also, it might be possible, that due to workspace and data schema settings, missing links in folder structure occur.
In this case, the indexing process creates virtual folders to fill up these gaps.
