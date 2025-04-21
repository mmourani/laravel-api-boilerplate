# 🧱 SaaS Boilerplate Documentation  
*Last updated: April 20, 2025*  

> For a complete project overview, see [SUMMARY.md](./SUMMARY.md)

⸻

## 📌 SaaS Boilerplate Documentation

A modular and secure SaaS boilerplate using **Laravel (API-only)** with token-based authentication, ownership policies, and scalable structure.

[![Tests](https://github.com/mmourani/laravel-api-boilerplate/actions/workflows/tests.yml/badge.svg)](https://github.com/mmourani/laravel-api-boilerplate/actions/workflows/tests.yml)
[![Coverage Status](https://coveralls.io/repos/github/mmourani/laravel-api-boilerplate/badge.svg?branch=main)](https://coveralls.io/github/mmourani/laravel-api-boilerplate?branch=main)

---

## 📁 Project Structure

Sites/
├── backend/ # Laravel API backend
├── frontend/ # React frontend (planned)
├── supabase/ # Supabase config (planned)
├── n8n-supabase-saas-deployment/ # Automation & workflows (planned)
└── .env.shared # Shared env variables

---

## ✅ Features Implemented

### ✅ Features Implemented  

#### 🔐 Authentication  
- Token-based auth using Laravel Sanctum  
- All standard authentication flows  
- Protected endpoints with policies  

#### 📁 Projects Module  
- Full CRUD operations  
- Ownership protection via policies  
- Soft delete functionality  

#### ✅ Tasks Module  
- Project-nested CRUD operations  
- Advanced filtering & sorting  
- State management  

|---

## 📅 Current Status

### ✅ Implemented in Latest Version
- v1.2.0: Soft Delete Implementation
- v1.3.0: Cross-Database Search Enhancements  
- v1.4.0: Standard API Pagination  

### 📊 Test Coverage (As of April 20, 2025)
```text
Line Coverage:    82.05% (+12.3% from v1.0)
Method Coverage:  69.77% (+8.5% from v1.0)
```

### 🔜 Coverage Improvement Progress
- Current Target: 95%+ line coverage by Q3 2025
- Next Milestone: 85% by May 2025

## 🧪 Testing with `curl`

### Register

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "password": "password"}'

Login

curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password"}'

Create Project

curl -X POST http://localhost:8000/api/projects \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Project Title","description":"Some description"}'

Create Task

curl -X POST http://localhost:8000/api/projects/1/tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Setup DB schema","priority":"high","due_date":"2025-04-25"}'



⸻

🛡 Policies Implemented
	•	ProjectPolicy applied via authorize() calls
	•	Only project owners can view/edit/delete associated resources

⸻

🔧 Configuration
	•	Sanctum installed and configured
	•	CORS enabled
	•	Laravel API-only stack (no web routes)
	•	Intelephense and VS Code configured for PHP 8.2+

⸻

⚙️ GitHub Actions

This repo includes CI testing via GitHub Actions.
Every push and PR to main runs automated tests and (optionally) code coverage with Coveralls.

⸻

📄 Additional Documentation
	•	Contributing Guide
	•	Security Policy
	•	Code of Conduct
	•	License (MIT)

⸻

## 🚧 Next Steps  

1. Achieve 95% test coverage (target: Q3 2025)  
2. Research OAuth integrations  
3. Evaluate payment providers  
4. Document API pagination patterns  
5. Complete frontend strategy research  

*For detailed roadmap, see [Development Plan](./docs/DEVELOPMENT_PLAN.md)*  

---
```
