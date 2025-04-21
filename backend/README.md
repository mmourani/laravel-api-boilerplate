# 🧱 Laravel SaaS Boilerplate (v12)  
*Last updated: April 21, 2025*  

> For a complete project overview, see [SUMMARY.md](./SUMMARY.md)

⸻

## 🚀 Key Features

**Laravel SaaS Boilerplate v12** provides a robust foundation for building scalable SaaS applications with:

- **Laravel Nova** installed with Fortify & Prompts
- Multi-tenant ready structure
- Clean **modular architecture** (Controller, Service, Repository, Interface)
- Feature toggles via `spatie/laravel-settings` (FeatureSettings)
- Auth auto-login in local environment for fast testing
- Comprehensive tests with high coverage (`.env.testing` on MySQL)
- CI/CD with GitHub Actions + Slack notifications

[![Tests](https://github.com/mmourani/laravel-api-boilerplate/actions/workflows/tests.yml/badge.svg)](https://github.com/mmourani/laravel-api-boilerplate/actions/workflows/tests.yml)
[![Coverage Status](https://coveralls.io/repos/github/mmourani/laravel-api-boilerplate/badge.svg?branch=main)](https://coveralls.io/github/mmourani/laravel-api-boilerplate?branch=main)

---

## 🏗 Project Structure
## 🏗 Project Structure

Sites/
├── backend/ # Laravel API backend
│   ├── app/
│   │   ├── Services/ # Business logic
│   │   ├── Repositories/ # Data access
│   │   ├── Http/Controllers/ # API endpoints
│   │   └── Settings/FeatureSettings.php # Feature flags
│   ├── tests/ # Feature & Unit tests
│   └── .env.testing # Testing configuration
├── frontend/ # React frontend (planned)
├── supabase/ # Supabase config (planned)
├── n8n-supabase-saas-deployment/ # Automation & workflows (planned)
└── .env.shared # Shared env variables

---

## 🔧 Tech Stack

- **Backend**: Laravel 12, Sanctum, Nova
- **Database**: MySQL, Redis
- **Testing**: PHPUnit, Xdebug, Debugbar
- **Code Quality**: PHPStan, Rector, Pint
- **CI/CD**: GitHub Actions, Coveralls

---
## ✅ Implemented Modules

### 🔐 Authentication  
- Token-based auth using Laravel Sanctum  
- All standard authentication flows  
- Protected endpoints with policies  
- Auto-login in local development  

### 📁 Projects Module  
- Full CRUD operations  
- Ownership protection via policies  
- Soft delete functionality  
- Multi-tenant isolation  

### ✅ Tasks Module  
- Project-nested CRUD operations  
- Advanced filtering & sorting  
- State management  
- Feature toggle integration  

### ⚙️ Settings Module  
- Feature flags via `spatie/laravel-settings`  
- Environment-based configuration  
- Runtime modifications via Nova UI  

---
### ✅ Recent Updates (v12)

- Modular architecture implementation
- Laravel Nova + Fortify integration
- Feature toggle system
- Enhanced test coverage
- Multi-tenant improvements

### 📊 Test Coverage (As of April 21, 2025)

```text
Line Coverage:    87.12% (+5.07% from v11)
Method Coverage:  75.42% (+5.65% from v11)
```

### 🔜 Coverage Improvement Progress

- Current Target: 95%+ line coverage by Q3 2025
- Next Milestone: 90% by June 2025
