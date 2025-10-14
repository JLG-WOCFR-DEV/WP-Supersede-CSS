# PHPUnit Offline Bootstrap Execution

The PHPUnit test suite was executed using the offline bootstrap fallback due to blocked downloads of the WordPress test suite in the execution environment.

```
vendor/bin/phpunit
```

Result: Tests completed with 73 tests, 77 assertions, and 65 skipped tests (WordPress-dependent tests skipped because WordPress could not be downloaded).
