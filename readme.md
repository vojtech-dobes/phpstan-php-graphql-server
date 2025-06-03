# PHPStan extension for [PHP GraphQL Server](https://github.com/vojtech-dobes/php-graphql-server)

![Checks](https://github.com/vojtech-dobes/phpstan-php-graphql-server/actions/workflows/checks.yml/badge.svg?branch=master&event=push)

This is super-convenient companion if you use [`vojtech-dobes/php-graphql-server`](https://github.com/vojtech-dobes/php-graphql-server) and [PHPStan](https://phpstan.org/). With this extension, PHPStan will be able to point out:

- mismatch between GraphQL Schema & what your resolvers actually return
- mismatch between GraphQL Schema & what your resolvers actually accept as arguments
- mismatch between declared parent value type and what resolver will actually receive
- supports utility resolvers like `PropertyFieldResolver` etc.



## Installation

To install the latest version, run the following command:

```
composer require vojtech-dobes/phpstan-php-graphql-server
```

Then you can register in your `phpstan.neon`:

```neon
includes:
  - vendor/vojtech-dobes/phpstan-php-graphql-server/extension.neon

graphql:
  generatedDir: "<path to temp directory>"
  schemas:
    - "<path to your Schema file>"
```

Next you have to tell the extension about your resolvers. If you're using framework integration, use corresponding package:

- **Integration:** `vojtech-dobes/php-graphql-server-nette-integration` (for Nette Framework)<br />
  **Package:** [`vojtech-dobes/phpstan-php-graphql-server-nette-integration`](https://github.com/vojtech-dobes/phpstan-php-graphql-server-nette-integration)

In case of custom setup, please implement `Vojtechdobes\PHPStan\GraphQL\Adapter` interface and register like this in `phpstan.neon`:

```neon
services:
  - class: MyCustomAdapter
```
