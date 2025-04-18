### 🙌 Thank you for considering contributing!

We welcome pull requests that:
- Add new features or modules (e.g. Tasks, Clients)
- Improve test coverage
- Fix bugs
- Improve developer experience (tooling, documentation)

### 📦 Local Development
1. Clone the repository:
   \`\`\`bash
   git clone https://github.com/mmourani/laravel-api-boilerplate.git
   \`\`\`
2. Install dependencies:
   \`\`\`bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   \`\`\`
3. Run migrations:
   \`\`\`bash
   php artisan migrate
   \`\`\`
4. Run tests:
   \`\`\`bash
   ./vendor/bin/phpunit
   \`\`\`

### 🧪 Testing Guidelines
- Feature tests should go in \`tests/Feature\`
- Use Laravel’s built-in testing tools (e.g., HTTP assertions)

### 🚀 Code Style
Please follow PSR-12 coding standards. Use \`php-cs-fixer\` or \`pint\` if needed.

### ✅ Pull Request Checklist
- [ ] Tests added for new logic
- [ ] No sensitive data committed
- [ ] \`php artisan test\` passes
- [ ] Feature is documented (if applicable)
