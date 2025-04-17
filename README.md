# 🧱 SaaS Boilerplate Documentation

A modular and secure SaaS boilerplate using **Laravel (API-only)** with token-based authentication, ownership policies, and scalable structure.

---

## 📁 Project Structure

```
Sites/
├── backend/               # Laravel API backend
├── frontend/              # React frontend (planned)
├── supabase/              # Supabase config (planned)
├── n8n-supabase-saas-deployment/  # Automation & workflows (planned)
└── .env.shared            # Shared env variables
```

---

## ✅ Features Implemented

### 🔐 Authentication

-   Laravel Sanctum for API token auth
-   Endpoints:
    -   `POST /api/register`
    -   `POST /api/login`
    -   `POST /api/logout`
    -   `GET  /api/user`
-   Token returned on login, required for all protected endpoints

### 🧱 Projects Module

-   Model: `Project`
-   Linked to user via `user_id`
-   Full CRUD:
    -   `GET /api/projects`
    -   `POST /api/projects`
    -   `GET /api/projects/{id}`
    -   `PUT /api/projects/{id}`
    -   `DELETE /api/projects/{id}`
-   Ownership protected via `ProjectPolicy`

### ✅ Tasks Module

-   Model: `Task`
-   Linked to projects via `project_id`
-   Fields: `title`, `done`, `priority`, `due_date`
-   Endpoints (nested):
    -   `GET /api/projects/{project}/tasks`
    -   `POST /api/projects/{project}/tasks`
    -   `GET /api/projects/{project}/tasks/{task}`
    -   `PUT /api/projects/{project}/tasks/{task}`
    -   `DELETE /api/projects/{project}/tasks/{task}`
-   Filtering support:
    -   `?priority=high`
    -   `?done=true`
    -   `?due_date=2025-04-25`
    -   `?sort_by=due_date&direction=desc`
-   Ownership protected via `ProjectPolicy`

---

## 🧪 Testing with `curl`

### Register

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "password": "password"}'
```

### Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password"}'
```

### Create Project

```bash
curl -X POST http://localhost:8000/api/projects \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Project Title","description":"Some description"}'
```

### Create Task

```bash
curl -X POST http://localhost:8000/api/projects/1/tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Setup DB schema","priority":"high","due_date":"2025-04-25"}'
```

---

## 🛡 Policies Implemented

-   `ProjectPolicy` applied via `authorize()` calls
    -   Only project owners can view/edit/delete associated resources

---

## 🔧 Configuration

-   Sanctum installed and configured
-   CORS enabled
-   Laravel API-only stack (no web routes)
-   Intelephense and VS Code configured for PHP 8.2+

---

## 📌 Next Steps (Planned)

-   Clients module
-   Teams or Roles
-   Global task listing: `/api/tasks`
-   React frontend
-   Supabase integration
-   Workflow automation via N8N
-   Docker/CI/CD setup

---
