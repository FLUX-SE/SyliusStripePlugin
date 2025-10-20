---
type: "manual"
---

# Sylius Test Application Migration - Before/After Examples

This document provides concrete before/after examples for all file changes during migration.

## Table of Contents

1. [composer.json](#composerjson)
2. [TestApplication Configuration Files](#testapplication-configuration-files)
   - [bundles.php](#bundlesphp)
   - [config.yaml](#configyaml)
   - [routes.yaml](#routesyaml)
   - [services_test.yaml](#services_testyaml)
   - [.env](#env)
   - [.env.test](#envtest)
3. [.gitignore](#gitignore)
4. [behat.yml.dist](#beathatyml-dist)
5. [phpunit.xml.dist](#phpunitxmldist)
6. [phpstan.neon.dist](#phpstanneondist)
7. [GitHub Actions Workflow](#github-actions-workflow)
8. [Directory Structure](#directory-structure)

---

## composer.json

### require-dev Section

**BEFORE:**
```json
{
    "require-dev": {
        "behat/behat": "^3.7",
        "friends-of-behat/symfony-extension": "^2.1",
        "phpunit/phpunit": "^9.5",
        "sylius/sylius": "^2.0",
        "symfony/browser-kit": "^6.4|^7.1",
        "symfony/debug-bundle": "^6.4|^7.1",
        "symfony/dotenv": "^6.4|^7.1"
    }
}
```

**AFTER:**
```json
{
    "require-dev": {
        "behat/behat": "^3.7",
        "friends-of-behat/symfony-extension": "^2.1",
        "phpunit/phpunit": "^9.5",
        "sylius/sylius": "^2.0",
        "sylius/test-application": "^2.0.0@alpha",
        "symfony/browser-kit": "^6.4|^7.1",
        "symfony/debug-bundle": "^6.4|^7.1",
        "symfony/dotenv": "^6.4|^7.1"
    }
}
```

### autoload-dev Section

**BEFORE:**
```json
{
    "autoload-dev": {
        "psr-4": {
            "Tests\\Acme\\SyliusExamplePlugin\\": "tests/",
            "Tests\\Acme\\SyliusExamplePlugin\\App\\": "tests/Application/src/"
        }
    }
}
```

**AFTER:**
```json
{
    "autoload-dev": {
        "psr-4": {
            "Tests\\Acme\\SyliusExamplePlugin\\": ["tests/", "tests/TestApplication/src/"]
        }
    }
}
```

### extra Section

**BEFORE:**
```json
{
    "extra": {
        "branch-alias": {
            "dev-main": "1.0-dev"
        },
        "runtime": {
            "project_dir": "tests/Application",
            "dotenv_path": "tests/Application/.env"
        }
    }
}
```

**AFTER:**
```json
{
    "extra": {
        "branch-alias": {
            "dev-main": "1.0-dev"
        },
        "public-dir": "vendor/sylius/test-application/public"
    }
}
```

### scripts Section

**BEFORE:**
```json
{
    "scripts": {
        "post-install-cmd": [
            "@php bin/create_node_symlink.php"
        ],
        "post-update-cmd": [
            "@php bin/create_node_symlink.php"
        ]
    }
}
```

**AFTER:**
```json
{
    "scripts": {
        "database-reset": [
            "vendor/bin/console doctrine:database:drop --force --if-exists",
            "vendor/bin/console doctrine:database:create",
            "vendor/bin/console doctrine:migration:migrate -n",
            "vendor/bin/console sylius:fixtures:load -n"
        ],
        "frontend-clear": [
            "yarn --cwd vendor/sylius/test-application install",
            "yarn --cwd vendor/sylius/test-application encore prod",
            "vendor/bin/console assets:install"
        ],
        "test-app-init": [
            "@database-reset",
            "@frontend-clear"
        ]
    }
}
```

---

## TestApplication Configuration Files

### bundles.php

**CREATE:** `tests/TestApplication/config/bundles.php`

```php
<?php

declare(strict_types=1);

return [
    FluxSE\SyliusStripePlugin\FluxSESyliusStripePlugin::class => ['all' => true],
];
```

**Note:** Only register your plugin bundle. All Sylius core bundles are already registered in `vendor/sylius/test-application/config/bundles.php`.

---

### config.yaml

**CREATE:** `tests/TestApplication/config/config.yaml`

```yaml
imports:
    - { resource: "@YourPlugin/config/config.yaml" }
    - { resource: "services_test.yaml" }
```

**Example for FluxSE Stripe Plugin:**
```yaml
imports:
    - { resource: "@FluxSESyliusStripePlugin/config/config.yaml" }
    - { resource: "services_test.yaml" }
```

**Note:** Replace `@YourPlugin` with your actual plugin bundle alias.

---

### routes.yaml

**CREATE:** `tests/TestApplication/config/routes.yaml`

```yaml
# Plugin routes (if any) can be imported here
```

**If your plugin has routes:**
```yaml
your_plugin:
    resource: "@YourPlugin/config/routes.yaml"
```

---

### services_test.yaml

**CREATE:** `tests/TestApplication/config/services_test.yaml`

```yaml
imports:
    - { resource: "../../Behat/Resources/services.xml" }
    - { resource: "../../../vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services.xml" }
```

**Note:** This imports Behat test services. Adjust paths if your Behat services are in a different location.

---

### .env

**CREATE:** `tests/TestApplication/.env`

```dotenv
###> sylius/test-application ###
SYLIUS_TEST_APP_CONFIGS_TO_IMPORT="@YourPlugin/tests/TestApplication/config/config.yaml"
SYLIUS_TEST_APP_ROUTES_TO_IMPORT="@YourPlugin/tests/TestApplication/config/routes.yaml"
SYLIUS_TEST_APP_BUNDLES_PATH="tests/TestApplication/config/bundles.php"
###< sylius/test-application ###

###> doctrine/doctrine-bundle ###
DATABASE_URL=mysql://root@127.0.0.1/your_plugin_%kernel.environment%?serverVersion=5.7
###< doctrine/doctrine-bundle ###
```

**Example for FluxSE Stripe Plugin:**
```dotenv
###> sylius/test-application ###
SYLIUS_TEST_APP_CONFIGS_TO_IMPORT="@FluxSESyliusStripePlugin/tests/TestApplication/config/config.yaml"
SYLIUS_TEST_APP_ROUTES_TO_IMPORT="@FluxSESyliusStripePlugin/tests/TestApplication/config/routes.yaml"
SYLIUS_TEST_APP_BUNDLES_PATH="tests/TestApplication/config/bundles.php"
###< sylius/test-application ###

###> doctrine/doctrine-bundle ###
DATABASE_URL=mysql://root@127.0.0.1/sylius_stripe_plugin_%kernel.environment%?serverVersion=5.7
###< doctrine/doctrine-bundle ###
```

---

### .env.test

**CREATE:** `tests/TestApplication/.env.test`

```dotenv
###> sylius/test-application ###
SYLIUS_TEST_APP_CONFIGS_TO_IMPORT="@YourPlugin/tests/TestApplication/config/config.yaml"
SYLIUS_TEST_APP_ROUTES_TO_IMPORT="@YourPlugin/tests/TestApplication/config/routes.yaml"
SYLIUS_TEST_APP_BUNDLES_PATH="tests/TestApplication/config/bundles.php"
###< sylius/test-application ###
```

**Note:** Test environment typically doesn't need DATABASE_URL as it's often set in phpunit.xml.dist or uses SQLite.

---

## .gitignore

**BEFORE:**
```gitignore
/drivers/
/vendor/
/node_modules/
/composer.lock

/etc/build/*
!/etc/build/.gitkeep

/tests/Application/yarn.lock
```

**AFTER:**
```gitignore
/drivers/
/vendor/
/node_modules/
/composer.lock
/var/

/etc/build/*
!/etc/build/.gitkeep

/tests/Application/yarn.lock
/tests/TestApplication/.env.local
/tests/TestApplication/.env.*.local
```

---

## behat.yml.dist

### Full Configuration

**BEFORE:**
```yaml
imports:
    - vendor/sylius/sylius/src/Sylius/Behat/Resources/config/suites.yml
    - tests/Behat/Resources/suites.yaml

default:
    extensions:
        Behat\MinkExtension:
            base_url: "https://127.0.0.1:8080/"
            sessions:
                symfony:
                    symfony: ~
                panther:
                    panther:
                        options:
                            webServerDir: "%paths.base%/tests/Application/public"

        FriendsOfBehat\SymfonyExtension:
            bootstrap: tests/Application/config/bootstrap.php
            kernel:
                class: Tests\Acme\SyliusExamplePlugin\App\Kernel
```

**AFTER:**
```yaml
imports:
    - vendor/sylius/sylius/src/Sylius/Behat/Resources/config/suites.yml
    - tests/Behat/Resources/suites.yaml

default:
    extensions:
        Behat\MinkExtension:
            base_url: "https://127.0.0.1:8080/"
            sessions:
                symfony:
                    symfony: ~
                panther:
                    panther:
                        options:
                            webServerDir: "%paths.base%/vendor/sylius/test-application/public"

        FriendsOfBehat\SymfonyExtension:
            bootstrap: vendor/sylius/test-application/config/bootstrap.php
            kernel:
                class: Sylius\TestApplication\Kernel
```

---

## phpunit.xml.dist

### Full Configuration

**BEFORE:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         colors="true"
         bootstrap="tests/Application/config/bootstrap.php">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <php>
        <server name="KERNEL_CLASS" value="Tests\Acme\SyliusExamplePlugin\App\Kernel" />
        <server name="IS_DOCTRINE_ORM_SUPPORTED" value="true" />
        <env name="APP_ENV" value="test"/>
    </php>
</phpunit>
```

**AFTER:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         colors="true"
         bootstrap="vendor/sylius/test-application/config/bootstrap.php">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <php>
        <server name="KERNEL_CLASS" value="Sylius\TestApplication\Kernel" />
        <server name="IS_DOCTRINE_ORM_SUPPORTED" value="true" />
        <env name="APP_ENV" value="test"/>
    </php>
</phpunit>
```

---

## phpstan.neon.dist

### excludePaths Configuration

**BEFORE:**
```neon
parameters:
    level: max
    paths:
        - src
        - tests/
    excludePaths:
        - tests/Application
        - tests/Functional/Api/JsonApiTestCase.php
```

**AFTER:**
```neon
parameters:
    level: max
    paths:
        - src
        - tests/
    excludePaths:
        - tests/TestApplication
        - tests/Functional/Api/JsonApiTestCase.php
```

---

## GitHub Actions Workflow

### .github/workflows/build.yml

**BEFORE:**
```yaml
jobs:
    tests:
        steps:
            - name: Run webserver
              run: (cd tests/Application && symfony server:start --port=8080 --dir=public --daemon)

            - name: Install JS dependencies
              run: (cd tests/Application && yarn install)

            - name: Prepare test application database
              run: |
                  (cd tests/Application && bin/console doctrine:database:create -vvv)
                  (cd tests/Application && bin/console doctrine:migrations:migrate -n -vvv -q)

            - name: Prepare test application assets
              run: |
                  (cd tests/Application && bin/console assets:install public -vvv)
                  (cd tests/Application && yarn build:prod)

            - name: Prepare test application cache
              run: (cd tests/Application && bin/console cache:warmup -vvv)

            - name: Load fixtures
              run: (cd tests/Application && bin/console sylius:fixtures:load -n)

            - name: Validate database schema
              run: (cd tests/Application && bin/console doctrine:schema:validate)

            - name: Upload logs
              uses: actions/upload-artifact@v4
              with:
                  path: |
                      etc/build/
                      tests/Application/var/log
```

**AFTER:**
```yaml
jobs:
    tests:
        steps:
            - name: Run webserver
              run: (cd vendor/sylius/test-application && symfony server:start --port=8080 --dir=public --daemon)

            - name: Install JS dependencies
              run: (cd vendor/sylius/test-application && yarn install)

            - name: Prepare test application database
              run: |
                  vendor/bin/console doctrine:database:create -vvv
                  vendor/bin/console doctrine:migrations:migrate -n -vvv -q

            - name: Prepare test application assets
              run: |
                  vendor/bin/console assets:install public -vvv
                  (cd vendor/sylius/test-application && yarn build:prod)

            - name: Prepare test application cache
              run: vendor/bin/console cache:warmup -vvv

            - name: Load fixtures
              run: vendor/bin/console sylius:fixtures:load -n

            - name: Validate database schema
              run: vendor/bin/console doctrine:schema:validate

            - name: Upload logs
              uses: actions/upload-artifact@v4
              with:
                  path: |
                      etc/build/
                      vendor/sylius/test-application/var/log
```

---

## Directory Structure

### Before Migration

```
your-plugin/
├── .github/
│   └── workflows/
│       └── build.yml
├── src/
│   └── YourPlugin.php
├── tests/
│   ├── Application/              ← CUSTOM TEST APP (TO BE REMOVED)
│   │   ├── bin/
│   │   │   └── console
│   │   ├── config/
│   │   │   ├── bootstrap.php
│   │   │   ├── bundles.php
│   │   │   ├── packages/
│   │   │   │   ├── framework.yaml
│   │   │   │   ├── doctrine.yaml
│   │   │   │   ├── your_plugin.yaml  ← KEEP THIS
│   │   │   │   └── ...
│   │   │   ├── routes/
│   │   │   └── services.yaml
│   │   ├── public/
│   │   │   ├── index.php
│   │   │   └── bundles/
│   │   ├── src/
│   │   │   ├── Kernel.php         ← CUSTOM KERNEL
│   │   │   └── Entity/            ← KEEP IF CUSTOM
│   │   ├── var/
│   │   ├── package.json
│   │   ├── webpack.config.js
│   │   └── yarn.lock
│   ├── Behat/                     ← KEEP
│   │   ├── Context/
│   │   ├── Page/
│   │   └── Resources/
│   └── Functional/                ← KEEP
├── behat.yml.dist
├── composer.json
└── phpunit.xml.dist
```

### After Migration

```
your-plugin/
├── .github/
│   └── workflows/
│       └── build.yml              ← UPDATED
├── src/
│   └── YourPlugin.php
├── tests/
│   ├── TestApplication/           ← NEW MINIMAL STRUCTURE
│   │   ├── config/
│   │   │   └── packages/
│   │   │       └── your_plugin.yaml  ← MOVED HERE
│   │   └── src/
│   │       └── Entity/            ← CUSTOM ENTITIES (IF ANY)
│   ├── Behat/                     ← UNCHANGED
│   │   ├── Context/
│   │   ├── Page/
│   │   └── Resources/
│   └── Functional/                ← UNCHANGED
├── vendor/
│   └── sylius/
│       └── test-application/      ← OFFICIAL TEST APP
│           ├── bin/
│           ├── config/
│           ├── public/
│           ├── src/
│           └── var/
├── behat.yml.dist                 ← UPDATED
├── composer.json                  ← UPDATED
└── phpunit.xml.dist               ← UPDATED
```

---

## Plugin Configuration File

### tests/TestApplication/config/packages/your_plugin.yaml

**Minimal Example:**
```yaml
imports:
    - { resource: "@YourPluginBundle/config/config.yaml" }
```

**With Test Overrides:**
```yaml
imports:
    - { resource: "@YourPluginBundle/config/config.yaml" }

your_plugin:
    # Override settings for testing
    api_key: "test_key"
    debug_mode: true
```

---

## Custom Entity Example

### tests/TestApplication/src/Entity/CustomProduct.php

```php
<?php

declare(strict_types=1);

namespace Tests\Acme\SyliusExamplePlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Product as BaseProduct;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product")
 */
class CustomProduct extends BaseProduct
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $customField = null;

    public function getCustomField(): ?string
    {
        return $this->customField;
    }

    public function setCustomField(?string $customField): void
    {
        $this->customField = $customField;
    }
}
```

---

## Summary of Path Changes

| Context | Before | After |
|---------|--------|-------|
| Bootstrap | `tests/Application/config/bootstrap.php` | `vendor/sylius/test-application/config/bootstrap.php` |
| Kernel Class | `Tests\Vendor\Plugin\App\Kernel` | `Sylius\TestApplication\Kernel` |
| Public Dir | `tests/Application/public` | `vendor/sylius/test-application/public` |
| Console | `tests/Application/bin/console` | `vendor/bin/console` |
| Var/Log | `tests/Application/var/log` | `vendor/sylius/test-application/var/log` |
| Plugin Config | `tests/Application/config/packages/` | `tests/TestApplication/config/packages/` |
| Custom Entities | `tests/Application/src/Entity/` | `tests/TestApplication/src/Entity/` |

---

## Quick Command Reference

### Before Migration
```bash
# Setup
cd tests/Application
yarn install
yarn build
bin/console doctrine:database:create
bin/console doctrine:migrations:migrate -n

# Run tests
cd ../..
vendor/bin/phpunit
vendor/bin/behat
```

### After Migration
```bash
# Setup
composer run test-app-init

# Or manually:
cd vendor/sylius/test-application
yarn install && yarn build
cd ../../..
vendor/bin/console assets:install
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n

# Run tests
vendor/bin/phpunit
vendor/bin/behat
```

