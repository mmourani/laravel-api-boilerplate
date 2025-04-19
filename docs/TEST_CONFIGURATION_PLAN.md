# 🧪 Test Configuration Plan  
*Last updated: April 19, 2025*

## 🌐 Overview  
Current test configuration for Laravel API Boilerplate including:  
- Test types and structure  
- Coverage configuration  
- Database setup  
- CI/CD integration  

*See also: [Coverage Improvement Plan](./COVERAGE_IMPROVEMENT_PLAN.md)*

## Steps

1. Backup existing configuration
   - Copy `phpunit.xml` to `phpunit.xml.bak`
   - Copy `.env.testing` to `.env.testing.bak`

2. Update phpunit.xml for file-based SQLite
   - In the `<php>` section, set:
     ```xml
     <env name="DB_CONNECTION" value="sqlite"/>
     <env name="DB_DATABASE" value="/tmp/test.sqlite"/>
     ```
   - Remove any references to `:memory:` in DSN or DB settings

3. Add database file creation step
   - Under `<phpunit>`'s `<bootstrap>` or `<listeners>`, add a small PHP or shell snippet:
     ```php
     if (! file_exists('/tmp/test.sqlite')) {
       touch('/tmp/test.sqlite');
     }
     ```
   - Ensure `/tmp/test.sqlite` has correct read/write permissions

4. Update .env.testing
   - Set:
     ```
     DB_CONNECTION=sqlite
     DB_DATABASE=/tmp/test.sqlite
     ```
   - Leave other test env settings (cache, queue, mail, etc.) unchanged

5. Remove VACUUM commands
   - Search all migrations and test files for `VACUUM`
   - Comment out or delete any `VACUUM` statements

6. Ensure proper test isolation
   - Verify Laravel's RefreshDatabase or equivalent trait is in use
   - Confirm foreign key constraints or transaction rollbacks are configured as needed

7. Configure coverage execution
   - Prefix the test command with `XDEBUG_MODE=coverage`
   - Example execution command:
     ```
     XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --colors=never
     ```
   - Update CI pipeline or `composer.json` scripts accordingly

8. Prepare the SQLite database before tests
   - In CI or a setup script, run:
     ```bash
     mkdir -p /tmp
     touch /tmp/test.sqlite
     chmod 666 /tmp/test.sqlite
     ```

9. Execute the test suite
   - Run the updated PHPUnit command
   - Verify tests pass and coverage report is generated

## 🔧 Current Configuration

### phpunit.xml Highlights
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>

<coverage cacheDirectory=".phpunit.cache">
  <include>
    <directory suffix=".php">app</directory>
  </include>
  <report>
    <clover outputFile="build/logs/clover.xml"/>
    <html outputDirectory="build/coverage"/>
    <text outputFile="php://stdout"/>
  </report>
</coverage>
```

### 📊 Coverage Metrics  
*Current status (April 19, 2025):*  
✔️ Line coverage: 82.05%  
✔️ Method coverage: 69.77%  
🔧 Class coverage: 22.22%  

## 📂 Test Directory Structure
```
tests/
├── Feature/       # Feature tests (API endpoints)
│   ├── Auth/      # Authentication tests  
│   ├── Projects/  # Projects module tests
│   └── Tasks/     # Tasks module tests
├── Unit/          # Unit tests (services, policies)
└── TestCase.php   # Base test case
```

## 🛠 CI/CD Integration
```yaml
# GitHub Actions workflow
steps:
  - name: Setup PHP
    uses: shivammathur/setup-php@v2
    with:
      php-version: '8.2'
      extensions: pdo_sqlite
      coverage: xdebug
  - name: Execute tests
    run: composer test-cover
```

---
*For detailed coverage improvement strategy, see [COVERAGE_IMPROVEMENT_PLAN.md](./COVERAGE_IMPROVEMENT_PLAN.md)*
