serve:
	@symfony serve --dir=tests/Application --daemon
server.start: serve
server.stop:
	@symfony server:stop --dir=tests/Application
frontend.install:
	@cd tests/Application && npm install
frontend.build:
	@cd tests/Application && npm run build
frontend.setup: frontend.install frontend.build
setup:
	@composer update
	@make frontend.setup
	@cd tests/Application && bin/console assets:install
	@cd tests/Application && bin/console doctrine:database:create --if-not-exists
	@cd tests/Application && bin/console doctrine:migrations:migrate -n
	@cd tests/Application && bin/console sylius:fixtures:load -n
	@cd tests/Application && APP_ENV=test bin/console doctrine:database:create --if-not-exists
	@cd tests/Application && APP_ENV=test bin/console doctrine:migrations:migrate -n
	@cd tests/Application && APP_ENV=test bin/console sylius:fixtures:load -n
ecs:
	@vendor/bin/ecs
phpstan:
	@vendor/bin/phpstan
phpunit:
	@vendor/bin/phpunit
behat:
	@vendor/bin/behat
behat.nojs:
	@vendor/bin/behat --tags="~@javascript"
qa.static-analysis: ecs phpstan
qa.tests: phpunit
ci: qa.static-analysis qa.tests
