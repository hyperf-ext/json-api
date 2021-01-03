# Hyperf {JSON:API} 组件

Build feature-rich and standards-compliant APIs in Hyperf.

> This package is ported from [cloudcreativity/laravel-json-api](https://github.com/cloudcreativity/laravel-json-api).

This package provides all the capabilities you need to add [JSON API](http://jsonapi.org)
compliant APIs to your application. Extensive support for the specification, including:

- Fetching resources
- Fetching relationships
- Inclusion of related resources (compound documents)
- Sparse fieldsets.
- Sorting.
- Pagination.
- Filtering
- Creating resources.
- Updating resources.
- Updating relationships.
- Deleting resources.
- Validation of:
  - JSON API documents; and
  - Query parameters.

The following additional features are also supported:

- Full support for Hyperf model resources, with features such as:
  - Automatic eager loading when including related resources.
  - Easy relationship end-points.
  - Soft-deleting and restoring model resources.
  - Page and cursor based pagination.
- Asynchronous processing.
- Support multiple media-types within your API.
- Generators for all the classes you need to add a resource to your API.

### What is JSON API?

From [jsonapi.org](http://jsonapi.org)

> If you've ever argued with your team about the way your JSON responses should be formatted, JSON API is your
anti-bikeshedding weapon.
>
> By following shared conventions, you can increase productivity, take advantage of generalized tooling, and focus
on what matters: your application. Clients built around JSON API are able to take advantage of its features around
efficiently caching responses, sometimes eliminating network requests entirely.

For full information on the spec, plus examples, see [http://jsonapi.org](http://jsonapi.org).

## Documentation

@todo

## License

Apache License (Version 2.0). Please see [License File](LICENSE) for more information.

## Installation

```shell
composer require hyperf-ext/json-api
```

## Configuration

Publish configuration file:

```shell
php bin/hyperf.php vendor:publish hyperf-ext/json-api
```

The default configuration:

```php
<?php

return [
    'apis' => [
        'v1' => [
            /*
             |--------------------------------------------------------------------------
             | Root Namespace
             |--------------------------------------------------------------------------
             |
             | The root namespace for JSON API classes for this API.
             |
             | The `by-resource` setting determines how your units are organised within
             | your root namespace.
             |
             | - true:
             |   - e.g. App\JsonApi\Posts\{Adapter, Schema, Validators}
             |   - e.g. App\JsonApi\Comments\{Adapter, Schema, Validators}
             | - false:
             |   - e.g. App\JsonApi\Adapters\PostAdapter, CommentAdapter}
             |   - e.g. App\JsonApi\Schemas\{PostSchema, CommentSchema}
             |   - e.g. App\JsonApi\Validators\{PostValidator, CommentValidator}
             |
             */
            'namespace' => 'App\JsonApi\V1',
            'by-resource' => true,
    
            /*
             |--------------------------------------------------------------------------
             | Model
             |--------------------------------------------------------------------------
             |
             | Whether your JSON API resources predominantly relate to Eloquent models.
             | This is used by the package's generators.
             |
             | You can override the setting here when running a generator. If the
             | setting here is `true` running a generator with `--no-model` will
             | override it; if the setting is `false`, then `--model` is the override.
             |
             */
            'use-model' => true,
            'model-namespace' => 'App\Model',
    
            /*
             |--------------------------------------------------------------------------
             | Resources
             |--------------------------------------------------------------------------
             |
             | Here you map the list of JSON API resources in your API to the actual
             | record (model/entity) classes they relate to.
             |
             | For example, if you had a `posts` JSON API resource, that related to
             | an Eloquent model `App\Post`, your mapping would be:
             |
             | `'posts' => \App\Model\Post::class`
             */
            'resources' => [
                //'posts' => \App\Model\Post::class,
            ],
    
            /*
             |--------------------------------------------------------------------------
             | URL
             |--------------------------------------------------------------------------
             |
             | The API's url, made up of a host, URL namespace and route name prefix.
             |
             | If a JSON API is handling an inbound request, the host will always be
             | detected from the inbound HTTP request. In other circumstances
             | (e.g. broadcasting), the host will be taken from the setting here.
             | If it is `null`, the `app.url` config setting is used as the default.
             | If you set `host` to `false`, the host will never be appended to URLs
             | for inbound requests.
             |
             | The name setting is the prefix for route names within this API.
             |
             */
            'url' => [
                'host' => null,
                'namespace' => '/v1',
                'name' => 'v1:',
            ],
    
            /*
             |--------------------------------------------------------------------------
             | Controllers
             |--------------------------------------------------------------------------
             |
             | The default JSON API controller wraps write operations in transactions.
             | You can customise the connection for the transaction here. Or if you
             | want to turn transactions off, set `transactions` to `false`.
             |
             */
            'controllers' => [
                'transactions' => true,
                'connection' => null,
            ],
    
            /*
             |--------------------------------------------------------------------------
             | Jobs
             |--------------------------------------------------------------------------
             |
             | Defines settings for the asynchronous processing feature. We recommend
             | referring to the documentation on asynchronous processing if you are
             | using this feature.
             |
             | Note that if you use a different model class, it must implement the
             | asynchronous process interface.
             |
             */
            'jobs' => [
                'resource' => 'queue-jobs',
                'model' => \HyperfExt\JsonApi\Queue\ClientJob::class,
            ],
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Resolver
     |--------------------------------------------------------------------------
     |
     | The API's resolver is the class that works out the fully qualified
     | class name of adapters, schemas, authorizers and validators for your
     | resource types. We recommend using our default implementation but you
     | can override it here if desired.
     */
    'resolver' => \HyperfExt\JsonApi\Resolver\ResolverFactory::class,

    /*
     |--------------------------------------------------------------------------
     | Encoding Media Types
     |--------------------------------------------------------------------------
     |
     | This defines the JSON API encoding used for particular media
     | types supported by your API. This array can contain either
     | media types as values, or can be keyed by a media type with the value
     | being the options that are passed to the `json_encode` method.
     |
     | These values are also used for Content Negotiation. If a client requests
     | via the HTTP Accept header a media type that is not listed here,
     | a 406 Not Acceptable response will be sent.
     |
     | If you want to support media types that do not return responses with JSON
     | API encoded data, you can do this at runtime. Refer to the
     | Content Negotiation chapter in the docs for details.
     |
     */
    'encoding' => [
        'application/vnd.api+json',
    ],

    /*
     |--------------------------------------------------------------------------
     | Decoding Media Types
     |--------------------------------------------------------------------------
     |
     | This defines the media types that your API can receive from clients.
     | This array is keyed by expected media types, with the value being the
     | service binding that decodes the media type.
     |
     | These values are also used for Content Negotiation. If a client sends
     | a content type not listed here, it will receive a
     | 415 Unsupported Media Type response.
     |
     | Decoders can also be calculated at runtime, and/or you can add support
     | for media types for specific resources or requests. Refer to the
     | Content Negotiation chapter in the docs for details.
     |
     */
    'decoding' => [
        'application/vnd.api+json',
    ],

    /*
     |--------------------------------------------------------------------------
     | Providers
     |--------------------------------------------------------------------------
     |
     | Providers allow vendor packages to include resources in your API. E.g.
     | a Shopping Cart vendor package might define the `orders` and `payments`
     | JSON API resources.
     |
     | A package author will define a provider class in their package that you
     | can add here. E.g. for our shopping cart example, the provider could be
     | `Vendor\ShoppingCart\JsonApi\ResourceProvider`.
     |
     */
    'providers' => [],

    /*
     |--------------------------------------------------------------------------
     | Logger
     |--------------------------------------------------------------------------
     |
     */
    'logger' => [
        'enabled' => false,
        'name' => 'json-api',
        'group' => 'default',
    ],
];
```
