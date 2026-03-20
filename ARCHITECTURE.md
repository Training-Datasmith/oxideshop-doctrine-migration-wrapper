# Architecture: oxideshop-doctrine-migration-wrapper

## Purpose

A thin wrapper around Doctrine Migrations for OXID eShop. Discovers and runs migrations from multiple paths (core shop + each installed module), resolving conflicts that arise when multiple Doctrine migration sets share a single migrations table.

## Directory Structure

```
src/
  Migrations.php                   — Entry point: runs migrations for the shop or a specific module
  Migrations_Builder.php           — Builds a configured Doctrine Application instance for a given path set
  Doctrine_Application_Builder.php — Constructs the Doctrine Console Application object
  Migration_Argument_Parser.php    — Parses CLI arguments passed to the migration runner
  Migration_Availability_Checker.php — Checks whether pending migrations exist before running
  Migrations_Path_Provider.php     — Discovers migration directories (shop + all active modules)
  Migrations_Path_Provider_Interface.php
  migrations-db.php                — Doctrine configuration file: returns database connection params
```

## Key Design Decisions

- **Multi-path migration discovery**: Each module can register its own `migrations/` directory; `MigrationsPathProvider` aggregates all paths so they are run in a single Doctrine pass
- **Isolation via separate tables**: When module migrations are run independently, they use a module-specific migrations table name to avoid collisions with shop migrations
- **CLI passthrough**: The wrapper passes unmodified arguments to the Doctrine Application, keeping Doctrine's own commands (`migrate`, `status`, `diff`) fully functional

## Extension Points

- Implement `Migrations_Path_Provider_Interface` to customize how migration paths are discovered
