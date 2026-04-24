# Laravel X-Ray

[![Laravel](https://img.shields.io/badge/Laravel-8%2B-red)]()
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)]()
[![License](https://img.shields.io/badge/license-MIT-green)]()
[![Stars](https://img.shields.io/github/stars/JaydeepGadhiya/laravel-xray)]()

Analyze and understand large Laravel applications through architecture visualization and dead code detection.

Laravel X-Ray is a developer tool that scans your application's source code statically — without executing it — and produces detailed reports about your controllers, models, routes, views, services, middleware, and form requests. It detects unused code and visualizes dependency relationships to help you maintain a clean, well-structured codebase.

## Features

- Full project health scan with component counts and dead code metrics
- Architecture dependency tree visualization in the terminal and as Mermaid diagrams
- Dead code detection for controllers, models, views, and services/repositories
- Route analysis including named routes, route groups with prefixes, resource and API resource routes
- Model relationship extraction (hasOne, hasMany, belongsTo, belongsToMany, and more)
- Middleware analysis with handle() signature extraction
- Form Request analysis with rules() field name extraction
- Complexity metrics on controllers (method count, lines of code)
- JSON, Markdown, Mermaid (.mmd), and HTML report generation
- Self-contained HTML dashboard powered by Bootstrap 5
- Configurable scan paths via a publishable config file
- `--path` option on all commands to override the scan base path

## Requirements

- PHP ^7.4 | ^8.0 | ^8.1 | ^8.2 | ^8.3 | ^8.4
- Laravel 8, 9, 10, 11, or 12

## Table of Contents

1. [Installation](#1-installation)
2. [Configuration](#2-configuration)
3. [Commands](#3-commands)
   - [xray:scan](#xrayscan)
   - [xray:architecture](#xrayarchitecture)
   - [xray:deadcode](#xraydeadcode)
   - [xray:report](#xrayreport)
4. [Output Formats](#4-output-formats)
5. [Understanding the Results](#5-understanding-the-results)
6. [Advanced Usage](#6-advanced-usage)
7. [Troubleshooting](#7-troubleshooting)
8. [Contributing](#8-contributing)
9. [License](#9-license)

---

## 1. Installation

### Step 1 — Require the package

```bash
composer require larapack/laravel-xray
```

### Step 2 — Auto-discovery (Laravel 5.5 and above)

Laravel automatically discovers and registers the service provider via the `extra.laravel` section in the package's `composer.json`. No manual registration is needed in most setups.

You can verify discovery ran successfully with:

```bash
php artisan package:discover
```

### Step 3 — Manual registration (optional)

If your application has auto-discovery disabled, open `config/app.php` and add the provider to the `providers` array:

```php
'providers' => [
    // ...
    Larapack\Xray\LaravelXrayServiceProvider::class,
],
```

### Step 4 — Publish the configuration file

```bash
php artisan vendor:publish --tag=xray-config
```

This copies `config/xray.php` into your application's `config/` directory so you can customize scan paths and output settings.

---

## 2. Configuration

After publishing, open `config/xray.php`. The file contains three sections.

```php
<?php

return [

    'paths' => [
        'controllers'   => app_path('Http/Controllers'),
        'models'        => app_path('Models'),
        'services'      => app_path('Services'),
        'repositories'  => app_path('Repositories'),
        'views'         => resource_path('views'),
        'routes'        => base_path('routes'),
        'middleware'    => app_path('Http/Middleware'),
        'form_requests' => app_path('Http/Requests'),
    ],

    'output_path' => storage_path('app/project-xray'),

    'ignore' => [
        'Controller.php',
    ],
];
```

### Path Keys

| Key | Default Location | Purpose |
|-----|-----------------|---------|
| `controllers` | `app/Http/Controllers` | PHP files to analyze as controllers |
| `models` | `app/Models` | PHP files to analyze as Eloquent models |
| `services` | `app/Services` | Service class files |
| `repositories` | `app/Repositories` | Repository class files |
| `views` | `resources/views` | Blade template files |
| `routes` | `routes/` | PHP route files (web.php, api.php, etc.) |
| `middleware` | `app/Http/Middleware` | Middleware class files |
| `form_requests` | `app/Http/Requests` | Form Request class files |

### `output_path`

The directory where all generated report files are saved. Defaults to `storage/app/project-xray`. The directory is created automatically if it does not exist.

### `ignore`

An array of file basenames (not full paths) to exclude from all analyzers. By default, `Controller.php` (the Laravel base controller) is ignored so it does not appear in dead code results or dependency trees.

### Customizing for Non-Standard Structures

If your project uses a non-standard layout, update the paths to match. For example, a Domain-Driven or modular structure:

```php
'paths' => [
    'controllers'   => base_path('src/Http/Controllers'),
    'models'        => base_path('src/Domain/Models'),
    'services'      => base_path('src/Domain/Services'),
    'repositories'  => base_path('src/Domain/Repositories'),
    'views'         => resource_path('views'),
    'routes'        => base_path('routes'),
    'middleware'    => base_path('src/Http/Middleware'),
    'form_requests' => base_path('src/Http/Requests'),
],
```

Each value must be an absolute directory path. You can use Laravel's path helpers (`app_path()`, `base_path()`, `resource_path()`, `storage_path()`) or plain strings.

---

## 3. Commands

All four commands share a common `--path=` option that overrides the base path used when resolving the configured scan directories. This is useful when scanning a different project root without changing the config file.

---

### xray:scan

Runs a full project health scan across all component types, prints a summary to the terminal, and always saves an HTML report to the configured `output_path`.

```bash
php artisan xray:scan
php artisan xray:scan --json           # Output full result as JSON to stdout
php artisan xray:scan --save           # Also save JSON and Markdown reports
php artisan xray:scan --path=/custom   # Override the base scan path
```

**Options**

| Option | Description |
|--------|-------------|
| `--json` | Print the full scan result as pretty-printed JSON to stdout after the summary |
| `--save` | Additionally write `scan-report.json` and `scan-report.md` to the configured `output_path` |
| `--path=` | Override the base path for all scan directory resolution |

> **Note:** `xray-report.html` is always generated and saved on every scan run, regardless of `--save`.

**Example console output**

```
Scanning project...

===================================
  Project X-Ray Summary
===================================

  Components
  ---------------------------------
  Controllers:   5
  Models:        8
  Services:      3
  Repositories:  2
  Routes:        12
  Views:         14

  Dead Code
  ---------------------------------
  Controllers:   0
  Models:        1
  Views:         2
  Services:      0

   3 potential dead code item(s) found.

  Scanned at: 2026-04-23T10:00:00+00:00

  Architecture Layers
  ---------------------------------
  Controller -> Service -> Repository -> Model

Scan complete!
```

The HTML report path is always shown after the scan:

```
Reports saved:
  - /path/to/storage/app/project-xray/xray-report.html
```

When `--save` is also passed, JSON and Markdown reports are added:

```
Reports saved:
  - /path/to/storage/app/project-xray/xray-report.html
  - /path/to/storage/app/project-xray/scan-report.json
  - /path/to/storage/app/project-xray/scan-report.md
```

---

### xray:architecture

Analyzes and visualizes the dependency tree for every controller in the project using box-drawing characters.

```bash
php artisan xray:architecture
php artisan xray:architecture --json     # Output architecture as JSON
php artisan xray:architecture --mermaid  # Generate and display a Mermaid diagram
php artisan xray:architecture --save     # Save JSON and Mermaid diagram files
php artisan xray:architecture --mermaid --save
php artisan xray:architecture --path=/custom
```

**Options**

| Option | Description |
|--------|-------------|
| `--json` | Print the architecture data (trees and layers) as JSON to stdout |
| `--mermaid` | Generate the Mermaid diagram and print it to the terminal (does not save unless `--save` is also passed) |
| `--save` | Save `architecture.json` and `architecture.mmd` to the configured `output_path` |
| `--path=` | Override the base path for scan directory resolution |

> **Note:** `xray:architecture` only runs the class analyzers (controllers, models, services, repositories). It skips routes, views, middleware, form requests, and dead code detection — making it faster when you only need the dependency map.

**Example terminal output**

```
===================================
  Architecture - Dependency Trees
===================================

  UserController
  ├── UserService
  │   └── UserRepository
  └── MailService

  OrderController
  └── OrderService
      ├── OrderRepository
      └── PaymentGateway

  Detected Layers
  ---------------------------------
  Controller -> Service -> Repository
```

**Example Mermaid file (`architecture.mmd`)**

```
%% Flowchart - Dependency Graph
graph TD
    UserController --> UserService
    UserService --> UserRepository
    UserController --> MailService

%% Class Diagram - Dependency Structure
classDiagram
    UserController --> UserService
    UserService --> UserRepository
    UserController --> MailService
```

Render `.mmd` files on GitHub, GitLab, [mermaid.live](https://mermaid.live), or any Mermaid-compatible tool.

---

### xray:deadcode

Scans for potentially unused controllers, models, views, and services.

```bash
php artisan xray:deadcode
php artisan xray:deadcode --json   # Output dead code as JSON
php artisan xray:deadcode --save   # Save JSON dead code report
php artisan xray:deadcode --path=/custom
```

**Options**

| Option | Description |
|--------|-------------|
| `--json` | Print the dead code result as JSON to stdout |
| `--save` | Save `deadcode.json` to the configured `output_path` |
| `--path=` | Override the base path for scan directory resolution |

**Example output (issues found)**

```
===================================
  Dead Code Analysis
===================================

  Unused Models
  ---------------------------------
  !  Tag (app/Models/Tag.php)

  Unused Views
  ---------------------------------
  !  admin.dashboard (resources/views/admin/dashboard.blade.php)
  !  emails.welcome (resources/views/emails/welcome.blade.php)

Total dead code items found: 3
  Controllers: 0, Models: 1, Views: 2, Services: 0
```

> **Important:** Dead code detection is heuristic. Always verify each item manually before deleting. See [Understanding the Results](#dead-code) for details on false positives.

---

### xray:report

Generates and saves one or more report files without interactive terminal output. Use this for CI/CD pipelines or scheduled tasks.

```bash
php artisan xray:report                    # Generate all formats (default)
php artisan xray:report --format=json      # JSON only
php artisan xray:report --format=markdown  # Markdown only
php artisan xray:report --format=mermaid   # Mermaid diagram only
php artisan xray:report --format=html      # HTML dashboard only
php artisan xray:report --format=all       # All formats explicitly
php artisan xray:report --path=/custom
```

**Files generated per format**

| Format | Files Written | Description |
|--------|--------------|-------------|
| `json` | `scan-report.json`, `architecture.json`, `deadcode.json` | Three separate JSON files |
| `markdown` | `scan-report.md` | Single Markdown report |
| `mermaid` | `architecture.mmd` | Mermaid diagram file |
| `html` | `xray-report.html` | Self-contained HTML dashboard |
| `all` | All of the above | Every format in a single run |

---

## 4. Output Formats

### Console Output

The terminal output uses Symfony Console styled tags:

- Green `<info>` — counts, highlights, success messages
- Yellow `<comment>` — section headers and separators
- Red `<error>` — dead code warnings and failure messages

Dependency trees use Unicode box-drawing characters (`├──`, `└──`, `│`) for visual indentation.

---

### JSON Output (`scan-report.json`)

The full scan report JSON contains every piece of data the tool collects:

```json
{
  "controllers": [
    {
      "file": "/path/to/app/Http/Controllers/UserController.php",
      "namespace": "App\\Http\\Controllers",
      "class": "UserController",
      "fqcn": "App\\Http\\Controllers\\UserController",
      "uses": { "UserService": "App\\Services\\UserService" },
      "constructor_dependencies": [
        { "type": "UserService", "name": "userService" }
      ],
      "methods": [
        { "name": "index", "visibility": "public" },
        { "name": "show", "visibility": "public" }
      ],
      "content": "<?php ...",
      "method_count": 2,
      "loc": 45
    }
  ],
  "models": [
    {
      "class": "User",
      "relationships": [
        { "type": "hasMany", "related": "Post", "method": "posts" }
      ]
    }
  ],
  "routes": [
    {
      "method": "GET",
      "uri": "/users",
      "controller": "UserController",
      "controller_fqcn": "App\\Http\\Controllers\\UserController",
      "action": "index",
      "name": "users.index",
      "file": "/path/to/routes/web.php"
    }
  ],
  "middleware": [
    {
      "class": "Authenticate",
      "handle_parameters": ["request", "next"]
    }
  ],
  "form_requests": [
    {
      "class": "StoreUserRequest",
      "rules": ["name", "email", "password"]
    }
  ],
  "architecture": {
    "trees": {
      "App\\Http\\Controllers\\UserController": {
        "App\\Services\\UserService": {
          "App\\Repositories\\UserRepository": {}
        }
      }
    },
    "layers": ["Controller", "Service", "Repository"]
  },
  "dead_code": {
    "controllers": [],
    "models": [{ "class": "Tag", "file": "/path/to/app/Models/Tag.php" }],
    "views": [],
    "services": []
  },
  "summary": {
    "total_controllers": 5,
    "total_models": 8,
    "total_services": 3,
    "total_repositories": 2,
    "total_routes": 12,
    "total_views": 14,
    "total_middleware": 4,
    "total_form_requests": 3,
    "dead_controllers": 0,
    "dead_models": 1,
    "dead_views": 0,
    "dead_services": 0
  },
  "scanned_at": "2026-04-23T10:00:00+00:00"
}
```

`architecture.json` contains only the `trees` and `layers` keys.
`deadcode.json` contains only the `dead_code` object.

---

### Markdown Output (`scan-report.md`)

Sections included: summary table, architecture dependency trees, dead code lists, controller details, model relationships, and a routes table. Works well when committed to a repository or shared in a pull request.

---

### Mermaid Diagram (`architecture.mmd`)

Contains two diagram blocks — a `graph TD` flowchart and a `classDiagram` — both using the same dependency edges. Duplicate edges are automatically removed. Render in GitHub, GitLab, [mermaid.live](https://mermaid.live), VS Code, Docusaurus, Notion, or Obsidian.

---

### HTML Report (`xray-report.html`)

A self-contained file with no local asset dependencies (loads Bootstrap 5 from CDN). Open directly in any browser — no server required.

Sections displayed:
- Summary stat cards (Controllers, Models, Routes, Views, Services, Dead Code)
- Dead code panel with file paths
- Routes table with HTTP method badges
- Architecture dependency tree as a nested list
- Model relationships table

---

## 5. Understanding the Results

### Controllers

| Field | Type | Description |
|-------|------|-------------|
| `file` | string | Absolute path to the PHP file |
| `namespace` | string | PHP namespace declaration |
| `class` | string | Short class name (e.g. `UserController`) |
| `fqcn` | string | Fully qualified class name |
| `uses` | object | Map of short name → FQCN for each `use` statement |
| `constructor_dependencies` | array | Each has `type` (class name) and `name` (variable name) |
| `methods` | array | Each has `name` and `visibility` |
| `method_count` | int | Total number of methods |
| `loc` | int | Lines of code in the file |

### Models

All controller fields plus:

| Field | Type | Description |
|-------|------|-------------|
| `relationships` | array | Each has `type` (e.g. `hasMany`), `related` (short model name), `method` (method name) |

Detected types: `hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, `hasOneThrough`, `hasManyThrough`, `morphTo`, `morphOne`, `morphMany`, `morphToMany`, `morphedByMany`.

### Routes

| Field | Type | Description |
|-------|------|-------------|
| `method` | string | HTTP method (e.g. `GET`, `POST`) |
| `uri` | string | Route URI pattern |
| `controller` | string | Short controller class name |
| `controller_fqcn` | string | Fully qualified controller class name |
| `action` | string | Controller method name |
| `name` | string | Named route value from `->name()`, or empty string |
| `file` | string | Absolute path to the route file |

`Route::resource()` and `Route::apiResource()` are automatically expanded to their individual actions.

### Middleware

All standard fields plus `handle_parameters` — parameter variable names from the `handle()` method signature.

### Form Requests

All standard fields plus `rules` — field names (array keys) extracted from the `rules()` method body.

### Architecture Trees

The `trees` key is a nested object where keys are FQCNs and values are the same nested structure representing constructor-injected dependencies. Trees are built only for controllers. Circular dependencies are detected and stopped.

**Detected layers** (in dependency order): `Controller`, `Service`, `Repository`, `Model`, `Event`, `Job`, `Mail`, `Notification`, `Policy`, `Middleware`.

### Dead Code

| Type | Detection Logic |
|------|----------------|
| Controllers | Short class name not found in the `controller` field of any parsed route |
| Models | Short class name not found in the content of any other scanned class or route file |
| Views | Dot-notation name not found via `view()`, `View::make()`, `@include()`, `@extends()`, or `@component()` in controllers or other views |
| Services / Repos | Short class name not found as a constructor dependency type in any class, and not found as a substring in any other class's content |

**Common false positives:**
- A model used only via `DB::table()` or raw queries
- A view name built dynamically (e.g. `view('emails.' . $template)`)
- A service bound to an interface and injected by interface type hint
- Code in directories outside the configured scan paths

Always verify reported items manually before deleting any code.

---

## 6. Advanced Usage

### Scanning a Specific Directory

```bash
php artisan xray:scan --path=/var/www/another-project
php artisan xray:architecture --path=/var/www/another-project
php artisan xray:deadcode --path=/var/www/another-project
php artisan xray:report --path=/var/www/another-project
```

### Customizing the Ignore List

```php
// config/xray.php
'ignore' => [
    'Controller.php',       // Laravel base controller (default)
    'BaseRepository.php',   // Abstract base repository
    'BaseService.php',      // Abstract base service
    'BaseFormRequest.php',  // Shared form request base class
],
```

### CI/CD Integration (GitHub Actions)

```yaml
- name: Generate X-Ray reports
  run: php artisan xray:report --format=all

- name: Upload X-Ray reports
  uses: actions/upload-artifact@v3
  with:
    name: xray-reports
    path: storage/app/project-xray/
```

For a quick health check (HTML report is always saved automatically):

```bash
php artisan xray:scan
```

### Scheduling Automated Reports

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('xray:report --format=all')->weekly();
}
```

### Non-Standard / Modular Project Structure

```php
// config/xray.php
'paths' => [
    'controllers'   => app_path('Modules/Http/Controllers'),
    'models'        => app_path('Modules/Models'),
    'services'      => app_path('Modules/Services'),
    'repositories'  => app_path('Modules/Repositories'),
    'views'         => resource_path('views'),
    'routes'        => base_path('routes'),
    'middleware'    => app_path('Http/Middleware'),
    'form_requests' => app_path('Modules/Http/Requests'),
],
```

Each path must be an absolute directory. All subdirectories are scanned automatically via `RecursiveDirectoryIterator`.

---

## 7. Troubleshooting

| Problem | Likely Cause | Solution |
|---------|-------------|---------|
| "No controllers detected" | `controllers` path in config does not exist or is wrong | Run `php artisan tinker` → `config('xray.paths.controllers')` to inspect the resolved value |
| Command not found (`xray:scan`) | Service provider not registered | Run `php artisan package:discover` or add `LaravelXrayServiceProvider` to `config/app.php` |
| False positive dead code | String-based detection has limitations | Verify manually; add confirmed false positives to the `ignore` list |
| Empty Mermaid diagram | No controllers have typed constructor dependencies | Add typed service parameters to controllers to produce a populated diagram |
| Report files not saved | `output_path` is not writable | Check permissions: `chmod -R 775 storage/` (Linux/macOS) |
| `--path=` has no effect | Path is not absolute or sub-paths don't exist under it | Supply an absolute path; ensure `Http/Controllers` etc. exist beneath it |
| Scan fails with a PHP error | Syntax error in a scanned file | Fix the syntax error — X-Ray uses `token_get_all()` which requires valid PHP syntax |
| Files in subdirectories not detected | Wrong parent directory configured | Verify the configured path is the correct parent; subdirectories are scanned recursively |

---

## 8. Contributing

Contributions are welcome. Please open an issue to discuss any change before submitting a pull request.

1. Fork the repository
2. Create a feature branch
3. Add tests covering your change
4. Run `./vendor/bin/phpunit` to ensure all tests pass
5. Submit a pull request

---

## 9. License

Laravel X-Ray is open-sourced software licensed under the [MIT license](LICENSE).
