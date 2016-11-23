# Re-indexation

Sitemaps will contain the following URL sources:

1. WordPress default post-types
2. Custom post-types (public)
3. Custom URLs provided


## Choices that have to be made

* Re-indexation control:
  * a) Re-indexation should not be run whenever an indexation is still unfinished
  * b) Re-indexation should be cancelled whenever a new re-indexation is run

## Expected functionality
* Re-indexation cleans up any existing sitemaps
* When re-indexing sitemap requests should result in a `503: Service Unavailable` response
* Whenever a new bucket is filled up, the index should be refreshed
* Whenever the indexation is finished, the index should be refreshed

### Iterating until done
* When the initial indexation takes too long, create a cronjob to finish the task
  * Register a new job until everything is done or just run until everything is done?
  * Do we need to make sure timeouts are not met (30 seconds hard limit on WPEngine)

## Indexation meta information
The indexation state should be managed in the WordPress Options, this way any page could visualise the current state of the sitemap indexation process.

## Custom URLs
Custom URL providing should be made as easy as possible, thus using filters.
This is a looped process which should end when the provider has run dry.

`$register_urls = apply_filters( 'wp_sitemap_register_urls', array(), $run_index );`

Data format for registering URLs:
```
array(
    array(
        'URL' => 'url',
        'identifier' => 'unique url identifier',
        'last_modified' => 'last modified date'
    )
);
```

## Measuring progress

All indexable post types should be known and listable.
The post type that is currently being indexed should be retrievable.
This gives an indication on what still needs to be done.

Custom URL registration will be just 1 step in this process.

Each URL can make a visual representation of the current process state without interfering with the the process itself.
