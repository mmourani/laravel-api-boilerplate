# 📊 Coverage Improvement Plan  
*Last updated: April 19, 2025*  
*See also: [Test Configuration](./TEST_CONFIGURATION_PLAN.md)*

## 📊 Current Status  

| Metric          | Coverage | Target  |
|-----------------|----------|---------|
| Lines           | 82.05%   | ≥95%    |
| Methods         | 69.77%   | ≥90%    | 
| Classes         | 22.22%   | ≥90%    |

*Total testable lines: 468*

### Test Database Configuration
- MySQL 8.0 (Forge provisioned)
- Database transactions for isolation
- Laravel database factories
- Separate test database user
    <include>
      <directory suffix=".php">app</directory>
    </include>
    <exclude>
      <directory suffix=".php">app/Http/Middleware</directory>
      <file>app/Http/Kernel.php</file>
      <file>app/Console/Kernel.php</file>
      <file>app/Exceptions/Handler.php</file>
    </exclude>
  </coverage>
  ```
- Verify that middleware classes are no longer counted against class coverage.

## 2. Add Missing Tests
### A. HTTP Layer
1. **AuthControllerTest.php**
   - Test successful login, registration, logout.
   - Edge cases: invalid credentials, missing fields.
   - Error conditions: user locked, throttling.
2. **ProjectControllerTest.php**
   - Cover all remaining endpoints (index, store, show, update, destroy).
   - Edge cases: unauthorized access, validation errors.
3. **TaskControllerTest.php**
   - CRUD: create, read, update, delete tasks.
   - Exception paths: foreign key violations, not found.

### B. Models
1. **ProjectTest.php**
   - Relationship edge cases: projects without tasks, owner missing.
2. **TaskTest.php**
   - Attribute casting & validation boundaries.
3. **UserTest.php**
   - MySQL-specific behaviors: unique constraints, default values.

### C. Policies
- Test all policy methods for valid/invalid permissions.
- Edge: soft-deleted models, archived projects.
- Policy caching: repeated `Gate::allows()` calls.

## 3. Monitor Progress
- After each feature/test, run `./vendor/bin/phpunit --coverage-text`.
- Record coverage deltas in `TEST_COVERAGE_PLAN.md`.
- Enforce thresholds in CI (e.g., `--coverage-min=90`).

## 4. Guidelines for New Features
- Adopt TDD: write tests before code.
- Aim for ≥95% coverage on new modules.
- Cover edge cases & error flows.
- Document test cases in PR descriptions.

## ✅ Success Criteria  

1. **Coverage Targets**
   - Lines: ≥95% (currently 82.05%)  
   - Methods: ≥90% (currently 69.77%)  
   - Classes: ≥90% (once middleware excluded)  

2. **Test Quality**
   - No untested critical paths  
   - All edge cases documented  
   - CI enforcement at merge  

## 🗓 Timeline  
- **Week 1** (April 19-25, 2025): Update `phpunit.xml` & exclude middleware.
- **Week 2** (April 26-May 2, 2025): Complete controller tests.
- **Week 3** (May 3-9, 2025): Add model & policy tests.
- **Week 4** (May 10-16, 2025): Review coverage, fix gaps, stabilize CI.

## Progress Tracking

### Week 1
- [ ] Update phpunit.xml configuration
- [ ] Verify middleware exclusion
- [ ] Update CI pipeline configuration
- [ ] Document coverage requirements

### Week 2
- [ ] Complete AuthControllerTest.php
- [ ] Enhance ProjectControllerTest.php
- [ ] Finish TaskControllerTest.php
- [ ] Update coverage report

### Week 3
- [ ] Complete Model tests
  - [ ] Project relationships
  - [ ] Task validation
  - [ ] User MySQL tests
- [ ] Complete Policy tests
  - [ ] Soft delete scenarios
  - [ ] Permission caching
  - [ ] Edge cases

### Week 4
- [ ] Review all coverage reports
- [ ] Fix any remaining gaps
- [ ] Document coverage achievements
- [ ] Update contribution guidelines

## Maintenance Notes

1. Regular Coverage Checks:
   ```bash
   # Generate coverage report
   XDEBUG_MODE=coverage php artisan test --coverage-html build/coverage

   # Quick coverage check
   XDEBUG_MODE=coverage php artisan test --coverage-text
   ```

2. CI/CD Integration:
   ```yaml
   # Add to GitHub Actions workflow
   - name: Run tests with coverage
     run: XDEBUG_MODE=coverage php artisan test --coverage-clover build/logs/clover.xml
   
   - name: Check coverage thresholds
     run: php artisan test --coverage-min=95
   ```

3. New Feature Requirements:
   - Write tests first (TDD approach)
   - Include both happy path and edge cases
   - Document test coverage in PR
   - Maintain 95%+ coverage

---

*Last updated: April 19, 2025*
