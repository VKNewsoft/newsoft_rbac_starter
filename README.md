# Newsoft RBAC Starter

A reusable **Role-Based Access Control (RBAC) starter project** that provides comprehensive and dynamically configurable access management, including dynamic menu management, role and permission management, and module-level access control.

The project is built as a **starter kit / foundation** for new applications: it ships with a working admin panel whose users, roles, permissions, menus, and modules are all managed from the database, so access control can be reconfigured at runtime without touching code. It can be used as the base of a larger application, or as a standalone administration and access-control system.

> **Stack**
> - **Framework:** CodeIgniter 4.x (full HMVC architecture)
> - **Language:** PHP 8.2+
> - **Database:** MySQL 8.x (MariaDB compatible)
> - **Platform:** developed and tested on XAMPP (Windows), works on any standard AMP stack

---

## Project Overview

The application is an Admin Panel starter designed as a *Web Security* foundation: it centrally controls users, roles, permissions, menus, and modules for every application built on top of it.

- **Everything is dynamic.** Menus, roles, permissions, and module registration are stored in the `core_*` database tables and rendered by the application at runtime.
- **Full HMVC.** Every feature lives in its own module (`app/Modules/<Name>`), each containing its own controllers, models, views, assets, and routes. Shared code lives in the `Common` module.
- **Multi level roles and multi company.** Roles and users can be organized by hierarchy level and by company, enabling flexible access management for complex organizations.
- **Ready for extension.** New features are added as modules and integrate with the RBAC system automatically.

---

## Core Features

The following features are implemented and verified in the codebase:

- **User management** — create, update, deactivate, and delete user accounts (`builtin/user`), with user-role assignment (`builtin/user-role`).
- **Role management** — define and manage multi level roles (`builtin/role`), assign roles to users, and control which roles can see which menus (`builtin/menu-role`).
- **Permission management** — define permissions per module (`builtin/permission`) and bind permissions to roles (`builtin/role-permission`). Permissions are enforced in controllers via `hasPermission()` checks.
- **Dynamic menu management** — build and reorder the application menu from the admin panel (`builtin/menu`); menus support categories, parents, icons, ordering, and role visibility.
- **Module management** — register modules in the database (`builtin/module`), activate/deactivate them, and control whether they appear at login.
- **Module-level access control** — menu entries are bound to modules, and module access is granted per role; navigation and routes are filtered by the user's permissions.
- **Authentication and authorization** — login, registration, and password recovery modules, session-based auth, CSRF token handling on forms, and route protection filters.
- **Security Monitor** — attack-type logging (SQL injection / XSS / brute force), rate limiting, and IP block management (`securitymonitor`).
- **Module asset loader** — module CSS/JS served through a `module-assets` route with correct MIME types, caching, and nested path support.
- **Multi company support** — companies and identity data (`company`, `identitas`) can be managed and linked to users.
- **Reusable architecture** — a shared `Common` module (base controller, base model, design system, shared JS/CSS) that every module builds on.

> The default dashboard, DB schema synchronization tool, regional data, and other supporting modules are examples of this architecture and can be kept, adapted, or removed per project.

---

## RBAC Architecture

### How the pieces fit together

| Concept | Table(s) | Managed by |
|---|---|---|
| Users | `core_user`, `core_user_role` | `builtin/user`, `builtin/user-role` |
| Roles | `core_role` | `builtin/role` |
| Modules | `core_module` (+ `core_module_status`) | `builtin/module` |
| Module permissions | `core_module_permission` | `builtin/permission` |
| Role ↔ permission grants | `core_role_module_permission` | `builtin/role-permission` |
| Menus | `core_menu`, `core_menu_kategori` | `builtin/menu` |
| Menu ↔ role visibility | `core_menu_role` | `builtin/menu-role` |
| Companies | `core_company` | `company` |

### How access is resolved

1. **A module is registered** in `core_module` with a unique `nama_module` (e.g. `builtin/role`, `dashboard`).
2. **Permissions are attached** to the module in `core_module_permission` (e.g. `create`, `read_all`, `update_all`, `delete_all`).
3. **Roles are granted permissions** through `core_role_module_permission` from the Role Permission screen.
4. **Menus are bound to modules** in `core_menu` and made visible to specific roles via `core_menu_role`.
5. **At request time** the `Bootstrap` filter loads the user's session, the menu tree, and the permission map (cached for performance). Controllers call `$this->hasPermission('read_all')` etc. before rendering or writing, and the sidebar renders only the menus the user's roles allow.

Because all of these relationships live in the database, access control is **dynamically configurable**: adding a screen to a role, revoking a permission, or hiding a menu is a data change, not a code change.

---

## Project Structure

```text
app/
  Config/               CodeIgniter configuration (Routes, Database, Filters, ...)
  Database/
    newsoft_base.sql    Complete initial schema + seed data (34 tables)
  Filters/              Request filters (Bootstrap, security, ...)
  Helpers/              Global helpers
  Language/             Language files
  Libraries/            Shared libraries
  Models/               Global models
  Modules/              HMVC modules (see below)
  Views/                Global/error views
public/                 Web root (index.php, assets)
system/                 CodeIgniter 4 framework
tools/
  create_hmvc_module.php  HMVC module generator (scaffold a new module)
manual_installer/       CLI installer + verification scripts
writable/               Cache, logs, sessions, uploads (not version-controlled)
```

### HMVC module layout

Every module follows the same self-contained structure:

```text
app/Modules/<Name>/
  Config/Routes.php     Module routes (auto-discovered by app/Config/Routes.php)
  Controllers/          Module controllers
  Models/               Module models
  Views/                Module views (namespaced view resolution)
  Assets/               Module CSS/JS (served via /module-assets/<module>/...)
```

The `Common` module contains the shared base controller, base model, global design system, and reusable frontend assets used by all modules.

---

## Installation Guide

### Prerequisites

- PHP **8.2+** with `mysqli`/`pdo_mysql` enabled
- **MySQL 8.x** (or MariaDB)
- Apache with `mod_rewrite` (or any web server able to run CodeIgniter 4)
- [XAMPP](https://www.apachefriends.org/) is the easiest option on Windows — start **Apache** and **MySQL**

### Option 1 — Web installer (recommended)

1. Clone/download this project into your web root (e.g. `C:\xampp\htdocs\`).
2. Open the application URL in a browser (e.g. `http://localhost/newsoft_rbac_starter`).
3. If the database is not initialized, the `InstallerCheck` filter redirects to `/installer`.
4. Fill in the database host, port, username, password, and database name (typical XAMPP defaults: `localhost`, `3306`, `root`, empty password, `newsoft_app`).
5. Click **Install**. The installer imports `app/Database/newsoft_base.sql`, creates **34 tables**, and loads 82,000+ regional rows plus the seed data.
6. Confirm the success page and log in.

The web installer validates input, writes `app/Config/Database.php`, and reports clearer errors than a raw import. Do not assume the redirect means every table imported — verify the schema when investigating a partial installation.

### Option 2 — CLI installer

From the repository root:

```bash
cd manual_installer
install.bat            # Windows interactive menu
# or directly:
php import_sql.php
php verify_import.php
php check_tables.php
```

The import takes 1–2 minutes because of the large regional dataset. The CLI installer uses a **fresh-install workflow that drops and recreates the target database** — never run it against a database containing data that must be preserved.

### First login

| Username | Password |
|---|---|
| `admin` | `123456` |

> ⚠️ The bootstrap credentials are for initial setup only. **Change the admin password immediately** before exposing the application beyond your local machine.

### Post-install verification

- The schema should contain **34 tables** (`php manual_installer/verify_import.php`).
- `core_user` exists (used by `InstallerCheck` to detect initialization).
- Log in with the bootstrap account, then change its password.
- Check `writable/logs/` for errors.

---

## Configuration

- **Database** — configured in `app/Config/Database.php` (written by the web installer; not version-controlled). Copy `.env.example` / `project.config.example.json` for environment templates.
- **Base URL** — set in `app/Config/App.php` or left to auto-detection.
- **Routes** — global routes live in `app/Config/Routes.php`; every module contributes its own `app/Modules/*/Config/Routes.php` which is auto-discovered.
- **Filters** — request filters (bootstrap, security, rate limiting) are registered in `app/Config/Filters.php`.
- **Writable directory** — `writable/` must be writable by the web server (cache, logs, sessions). Its contents are not committed.

Never commit real credentials. Keep production secrets out of the repository.

---

## Module Development Guide

### Create a module

Use the included generator:

```bash
php tools/create_hmvc_module.php Produk produk
```

This scaffolds `app/Modules/Produk/` with `Config/Routes.php`, `Controllers/Produk.php`, `Models/ProdukModel.php`, and views.

### Register it with the RBAC system

1. **Routes** — add routes in `app/Modules/Produk/Config/Routes.php`; they are picked up automatically (no central registration needed).
2. **Register the module** — in the admin panel open **Module** (`builtin/module`) and add a module with `nama_module` = `produk`. Here you can also activate/deactivate it and control login behavior.
3. **Define permissions** — open **Module Permission** (`builtin/permission`) and add the permissions the module understands (e.g. `create`, `read_all`, `update_all`, `delete_all`).
4. **Grant permissions to roles** — open **Role Permission** (`builtin/role-permission`) and tick the new permissions per role.
5. **Add menus** — open **Menu** (`builtin/menu`) and create a menu entry bound to module `produk`; control which roles see it via **Menu Role** (`builtin/menu-role`).
6. **Database structures** — create the module's own tables (e.g. `base_produk`) with a migration or SQL, and keep the module's model queries inside the module.
7. **Guard the controller** — call `$this->hasPermission('read_all')` (etc.) in your controller actions so the framework enforces the same rules the UI shows.

From this point the module participates in the access-control system like any built-in module: menu visibility, permission checks, and role assignment all work without further code.

---

## Usage Guide

Log in and manage everything from the sidebar:

- **Dashboard** (`dashboard`) — landing page after login.
- **Manajemen Aplikasi / Module** (`builtin/module`) — register modules, toggle active status, see whether each module's controller exists.
- **Module Permission** (`builtin/permission`) — define the permission verbs available per module.
- **Role** (`builtin/role`) — create roles (e.g. Administrator, User Biasa) and set their level.
- **Role Permission** (`builtin/role-permission`) — grant each role its set of module permissions.
- **User / Semua User** (`builtin/user`) — manage accounts; **User Role** (`builtin/user-role`) assigns roles to users.
- **Menu** (`builtin/menu`) — build the sidebar: categories, parents, icons, order; **Menu Role** (`builtin/menu-role`) controls role visibility.
- **Security Monitor** (`securitymonitor`) — review attack logs, rate-limit counters, and blocked IPs.
- **DB Synchronisation** (`db-synchronisation`) — compare the live schema with the installer dump and generate safe/full sync SQL.
- **Setting** (`builtin/setting-app`, `builtin/setting-layout`, `builtin/setting-registrasi`) — application name, layout, and registration behavior.

A typical setup flow: create a role → grant it permissions → create users and assign the role → add menus for the new screens and attach the role.

---

## Development and Contribution

- Keep module controller/model/view/asset code together under the HMVC module; put reusable frontend code in the shared `Common` module instead of duplicating it.
- Preserve the shared design-system classes (`page-shell`, `page-hero`, `page-toolbar`, `page-card`, `form-card`, `card-table-wrap`).
- For list screens, keep server-side pagination, explicit query columns, and page-scoped lookups (avoid N+1 queries).
- Use CodeIgniter **Migrations** for future schema changes so they remain tracked and reproducible; back up the database before destructive DDL or synchronization.
- Check `writable/logs/` and the browser console after UI changes; test responsive behavior down to mobile widths.
- Additional guides in this repository: [`HMVC_MODULE_GUIDE.md`](HMVC_MODULE_GUIDE.md), [`INSTALLATION.md`](INSTALLATION.md), [`DATABASE_INSTALLATION_GUIDE.md`](DATABASE_INSTALLATION_GUIDE.md).

Contributions: fork the repository, create a feature branch, keep changes small and verifiable, and open a pull request describing the motivation and testing done.

---

## License

This project is licensed under the terms in [`LICENSE`](LICENSE) (© 2025 Newsoft Developer). See the license file for the full text.
