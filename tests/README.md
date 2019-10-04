# Running tests

## Test suites

This way will run next tests:

- Static tests, including PHPMD and PHPCS
- All unit tests
- Code coverage test

This is the best way to run tests locally.

1. Navigate to working directory
2. Run `composer test` and verify results

## Unit tests

To run unit tests, specify the configuration file in the following command:

```
./vendor/bin/phpunit --configuration tests/unit
```

## Static tests

1. Run PHPCS test with following command:
```
./vendor/bin/phpcs src --standard=tests/static/phpcs-ruleset.xml -p -n
```
2. Run PHPMD tests withfollowing command:
```
./vendor/bin/phpmd src xml tests/static/phpmd-ruleset.xml
```

## Code coverage check

This test will generate a pretty report for unit test coverage.

1. Run the command `composer test-coverage`
2. Observe result in CLI output
 - Be sure to enable [xDebug](http://devdocs.magento.com/guides/v2.2/cloud/howtos/debug.html) for this test

## Code coverage report

This test will generate a pretty report for unit test coverage.

1. Run the command `composer test-coverage-generate`
2. Navigate to `tests/unit/tmp/coverage` and open `index.html` file in browser
 - Be sure to enable [xDebug](http://devdocs.magento.com/guides/v2.2/cloud/howtos/debug.html) for this test

## Best practices

- After you setup PhpStorm with PhpUnit and PHPCS, etc, it sometimes runs really slow. But, there is an icon in the bottom right corner of PhpStorm you can click on (it looks like Travis) that will let you temporarily disable inspections.

