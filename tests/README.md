# Static Analysis

To run static analysis on the plugin, install PHPStan and run the following command from the root of your project.

    vendor/bin/phpstan analyse vendor/putyourlightson/craft-sherlock/src -c vendor/putyourlightson/craft-sherlock/phpstan.neon -l 3

# Testing

To test the plugin, install Codeception, update `.env` and run the following command from the root of your project.

    codecept run -c ./vendor/putyourlightson/craft-sherlock

Or to run a specific test.

     codecept run -c ./vendor/putyourlightson/craft-sherlock unit services/GenerateCacheTest:cacheSaved

> Ensure that the database you specify in `.env` is not one that actually contains any data as it will be cleared when the tests are run. 
