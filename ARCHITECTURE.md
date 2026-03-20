# Architecture: thirtybees

## Purpose

Thirty Bees — a community-maintained PHP e-commerce platform forked from PrestaShop 1.6. Provides a full online store with catalog management, cart and checkout, order processing, customer accounts, multi-language/multi-currency support, and a module/theme extension system.

## Directory Structure

```
classes/              - Core domain model: Cart, Order, Product, Customer, Category, etc.
controllers/          - Front-office and back-office (admin) controllers
admin-dev/            - Admin panel PHP files and templates
modules/              - Bundled modules (payment, shipping, analytics, etc.)
Adapter/              - Bridge classes adapting legacy code to Symfony-style interfaces
Core/                 - Refactored core services using interfaces and DI
config/               - Platform configuration files
themes/               - Front-end theme files (templates, CSS, JS)
cache/                - File-based cache directory
override/             - Drop-in class overrides for customisation without patching core
```

## Key Design Decisions

- **Legacy MVC pattern**: Controllers dispatch to Smarty-rendered templates; no full framework dependency — the platform is its own framework.
- **ObjectModel base class**: All domain entities extend `Object_Model`, which provides generic CRUD operations, validation, multi-language field handling, and shop association.
- **Module hook system**: Modules register for named hooks (e.g., `displayHome`, `actionCartSave`). The core calls `Hook::exec('hookName')` at key points, collecting and concatenating module output.
- **Class override mechanism**: Merchants can replace any core class by placing a file in `override/classes/`, which is merged at bootstrap. This avoids editing core files directly.
- **Adapter/Core refactoring**: New code in `Core/` uses interfaces and dependency injection; `Adapter/` provides compatibility shims bridging legacy static calls to the new layer.

## Extension Points

- **Modules**: Implement `Module` and register hooks to add storefront features, payment gateways, shipping carriers, or admin pages.
- **Class overrides**: Place files in `override/classes/` to extend or replace any core class.
- **Themes**: Create a theme directory with Smarty `.tpl` templates to change the storefront appearance.
- **Hooks**: Call `Hook::exec()` with a custom hook name to define new extension points in custom code.

## Dependency Flow

```
HTTP Request
  └─> Dispatcher — route to Front_Controller subclass
        └─> Controller::run()
              └─> ObjectModel subclass (Cart, Product, ...) — domain logic & DB
              └─> Hook::exec('displayXxx') — collect module output
              └─> Smarty → render .tpl template → HTTP response
```
