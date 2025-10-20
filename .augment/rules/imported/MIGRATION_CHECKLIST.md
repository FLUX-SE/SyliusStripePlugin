---
type: "manual"
---

# Sylius Test Application Migration - Quick Checklist

Use this checklist to track your migration progress.

## Pre-Migration

- [ ] Backup/commit current codebase
- [ ] Review `tests/Application` for custom code
- [ ] Identify plugin-specific configuration files
- [ ] Identify custom entities (if any)
- [ ] Document current test setup

## Migration Steps

### 1. Update composer.json

- [ ] Add `"sylius/test-application": "^2.0.0@alpha"` to require-dev
- [ ] Update autoload-dev: Change `"Tests\\Vendor\\Plugin\\App\\"` to array format
- [ ] Remove `extra.runtime` section
- [ ] Add `extra.public-dir: "vendor/sylius/test-application/public"`
- [ ] Add/update scripts: `database-reset`, `frontend-clear`, `test-app-init`
- [ ] Sort require-dev dependencies alphabetically (optional but recommended)

### 2. Create New Directory Structure

- [ ] Create `tests/TestApplication/config/` directory
- [ ] Create `tests/TestApplication/config/bundles.php`
- [ ] Create `tests/TestApplication/config/config.yaml`
- [ ] Create `tests/TestApplication/config/routes.yaml`
- [ ] Create `tests/TestApplication/config/services_test.yaml` (if using Behat)
- [ ] Create `tests/TestApplication/.env`
- [ ] Create `tests/TestApplication/.env.test`
- [ ] Create `tests/TestApplication/src/Entity/` (only if plugin has custom entities)
- [ ] Create `tests/TestApplication/templates/` (only if plugin has custom templates)

### 3. Configure bundles.php

- [ ] Register your plugin bundle in `tests/TestApplication/config/bundles.php`
- [ ] Add any additional bundles not in Sylius TestApplication core

### 4. Configure config.yaml

- [ ] Import plugin configuration: `@YourPlugin/config/config.yaml`
- [ ] Import test services: `services_test.yaml`
- [ ] Add doctrine entity mappings (only if custom entities exist)

### 5. Configure routes.yaml

- [ ] Import plugin routes (if any) or leave empty

### 6. Configure services_test.yaml

- [ ] Import Behat services from `../../Behat/Resources/services.xml`
- [ ] Import Sylius Behat services from vendor

### 7. Configure Environment Files

- [ ] Set `SYLIUS_TEST_APP_CONFIGS_TO_IMPORT` in `.env` and `.env.test`
- [ ] Set `SYLIUS_TEST_APP_ROUTES_TO_IMPORT` in `.env` and `.env.test`
- [ ] Set `SYLIUS_TEST_APP_BUNDLES_PATH` in `.env` and `.env.test`
- [ ] Set `DATABASE_URL` in `.env` (for dev/test database)

### 8. Update .gitignore

- [ ] Add `/var/` to .gitignore
- [ ] Add `/tests/TestApplication/.env.local` to .gitignore
- [ ] Add `/tests/TestApplication/.env.*.local` to .gitignore

### 9. Migrate Custom Code (if applicable)

- [ ] Copy custom entities from `tests/Application/src/Entity/` to `tests/TestApplication/src/Entity/`
- [ ] Update entity namespaces if needed
- [ ] Copy custom templates from `tests/Application/templates/` to `tests/TestApplication/templates/`
- [ ] Copy custom fixtures (if any)
- [ ] Copy custom services (if any)

### 10. Update behat.yml.dist

- [ ] Change `bootstrap:` to `vendor/sylius/test-application/config/bootstrap.php`
- [ ] Change `kernel.class:` to `Sylius\TestApplication\Kernel`
- [ ] Update `webServerDir:` to `vendor/sylius/test-application/public`

### 11. Update phpunit.xml.dist

- [ ] Change `bootstrap=` to `vendor/sylius/test-application/config/bootstrap.php`
- [ ] Change `KERNEL_CLASS` to `Sylius\TestApplication\Kernel`
- [ ] Verify `APP_ENV` is set to `test`

### 12. Update phpstan.neon.dist (if applicable)

- [ ] Change `excludePaths` from `tests/Application` to `tests/TestApplication`

### 13. Update CI/CD Workflows

- [ ] Update webserver start path to `vendor/sylius/test-application`
- [ ] Update console command paths (use `vendor/bin/console` instead of `cd tests/Application && bin/console`)
- [ ] Update yarn/npm paths (use `yarn --cwd vendor/sylius/test-application` or `cd vendor/sylius/test-application && yarn`)
- [ ] Update log paths for artifact uploads to `vendor/sylius/test-application/var/log`
- [ ] Update asset build commands to use `yarn encore prod` instead of `yarn build`

### 14. Install and Test

- [ ] Run `composer update` to install sylius/test-application
- [ ] Run `vendor/bin/console doctrine:database:create --if-not-exists`
- [ ] Run `vendor/bin/console doctrine:migrations:migrate -n` (or `doctrine:schema:create` if no migrations)
- [ ] Run `composer run frontend-clear` (or manually: `yarn --cwd vendor/sylius/test-application install && yarn --cwd vendor/sylius/test-application encore prod`)
- [ ] Run `vendor/bin/console assets:install`
- [ ] Run `vendor/bin/console sylius:fixtures:load -n`
- [ ] Run `vendor/bin/phpunit`
- [ ] Run `vendor/bin/behat --tags="~@javascript"`
- [ ] Run `vendor/bin/behat --tags="@javascript"` (if applicable)
- [ ] Run `vendor/bin/phpstan analyse`
- [ ] Run `vendor/bin/ecs check`

### 15. Cleanup

- [ ] Remove `tests/Application/` directory (ONLY after all tests pass)
- [ ] Remove `bin/create_node_symlink.php` (if exists)
- [ ] Remove any other obsolete files related to old test application

### 16. Final Verification

- [ ] All tests pass locally
- [ ] CI/CD pipeline passes
- [ ] Manual testing of plugin features
- [ ] Documentation updated (if needed)
- [ ] Commit changes with descriptive message

## Post-Migration

- [ ] Update README.md with new setup instructions
- [ ] Update CONTRIBUTING.md (if applicable)
- [ ] Tag a new release (if applicable)
- [ ] Announce migration in changelog

## Common File Changes Summary

| File | Change Type | Description |
|------|-------------|-------------|
| `composer.json` | Modified | Add test-application, update autoload-dev, add scripts |
| `tests/TestApplication/config/bundles.php` | Created | Register plugin bundle |
| `tests/TestApplication/config/config.yaml` | Created | Import plugin config and test services |
| `tests/TestApplication/config/routes.yaml` | Created | Import plugin routes (or empty) |
| `tests/TestApplication/config/services_test.yaml` | Created | Import Behat services |
| `tests/TestApplication/.env` | Created | TestApplication environment configuration |
| `tests/TestApplication/.env.test` | Created | Test environment configuration |
| `.gitignore` | Modified | Add TestApplication local files |
| `behat.yml.dist` | Modified | Update bootstrap and kernel paths |
| `phpunit.xml.dist` | Modified | Update bootstrap and kernel class |
| `phpstan.neon.dist` | Modified | Update excludePaths |
| `.github/workflows/*.yml` | Modified | Update all test application paths |
| `tests/Application/` | Deleted | Entire directory removed (after tests pass) |

## Estimated Time

- Simple plugin (no custom entities): **30 minutes**
- Standard plugin (with custom config): **45 minutes**
- Complex plugin (custom entities, fixtures): **60 minutes**

## Need Help?

- Review the full migration guide: `SYLIUS_TEST_APPLICATION_MIGRATION_GUIDE.md`
- Check Sylius documentation: https://docs.sylius.com
- Ask in Sylius Slack: https://sylius.com/slack

