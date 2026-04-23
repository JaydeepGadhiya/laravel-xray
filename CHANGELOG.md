# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-04-23

### Added
- `xray:scan` command — full project health overview
- `xray:architecture` command — dependency tree visualization with Mermaid support
- `xray:deadcode` command — unused controllers, models, views, and services detection
- `xray:report` command — generate JSON, Markdown, Mermaid, and HTML reports
- ControllerAnalyzer — extracts controller classes, methods, and dependencies
- ModelAnalyzer — extracts models, relationships (hasOne, hasMany, belongsTo, etc.)
- RouteAnalyzer — parses web/api routes including resource and apiResource routes, named routes, and route groups
- ServiceAnalyzer — detects service and repository classes
- ViewAnalyzer — catalogs Blade and PHP views with dot-notation names
- DependencyAnalyzer — builds dependency trees and detects architectural layers
- MiddlewareAnalyzer — scans middleware classes and handle() signatures
- FormRequestAnalyzer — scans Form Request classes and extracts validation rules
- ConsoleReporter — styled terminal output with box-drawing tree visualization
- JsonReporter — machine-readable JSON output (full, architecture, dead code)
- MarkdownReporter — human-readable Markdown reports
- MermaidReporter — Mermaid flowchart and class diagram generation
- HtmlReporter — self-contained HTML dashboard
- Configurable scan paths via `config/xray.php`
- Complexity metrics (method count, LOC) on controllers
- `HasPhpFileScanner` trait — shared PHP file discovery logic across all analyzers
- `EnsuresOutputDirectory` trait — shared directory creation logic across all reporters
- `--path` option on all commands to override the base scan path
