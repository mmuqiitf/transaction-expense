# Transaction Expense Context

This context defines the language used by the personal income and expense tracker.

## Accounting vocabulary

**Category**:
A grouping used to classify related chart-of-account entries, such as Salary or Transport Expense, with an income or expense classification.
_Avoid_: Tag, label

**Chart of Accounts (COA)**:
A named account identified by a code and assigned to exactly one category, such as `401` Gaji Karyawan.
_Avoid_: Category, ledger

**Transaction**:
A dated financial entry assigned to one COA, with a non-negative amount in Indonesian Rupiah and an optional description.
_Avoid_: Record, journal entry

**Classification**:
The account-level distinction between income and expense that determines how a transaction contributes to the profit/loss report.
_Avoid_: Transaction type

**Shared Ledger**:
The application-wide set of categories, COA entries, and transactions that authenticated users can view and manage together.
_Avoid_: Personal ledger, private workspace

**Income**:
Money received and included in total income for the period.
_Avoid_: Credit, revenue (unless the context specifically refers to accounting terminology)

**Expense**:
Money spent and included in total expenses for the period.
_Avoid_: Debit, cost (unless the context specifically refers to accounting terminology)

**Profit/Loss Report**:
A period-based summary that groups transaction amounts by category and month, then presents total income, total expense, and net income.
_Avoid_: Dashboard, financial statement

**Demo Account**:
A pre-seeded login with sample categories, COA entries, transactions, and report data so a new user can explore the shared ledger immediately.
_Avoid_: Dummy user, test account
