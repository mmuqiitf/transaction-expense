# Use a shared ledger for accounting data

The application will store categories, chart-of-account entries, and transactions in one shared ledger rather than associating them with individual users. This matches the requested collaborative access model and keeps the demo account focused on providing an easy entry point; authorization still requires authentication for all ledger operations.

## Consequences

- All authenticated users see the same accounting data.
- CRUD operations must not add user ownership filters.
- The demo seeder must be idempotent so it can safely initialize the shared ledger.
- Per-user workspaces would require a future data migration and authorization boundary.
