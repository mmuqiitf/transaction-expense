export type CategoryType = 'income' | 'expense';

export interface Category {
    id: number;
    name: string;
    type: CategoryType;
    chart_of_accounts_count?: number;
}

export interface ChartOfAccount {
    id: number;
    code: string;
    name: string;
    category: Category;
    transactions_count?: number;
}

export interface Transaction {
    id: number;
    transaction_date: string;
    amount: number;
    description: string | null;
    chart_of_account: ChartOfAccount;
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

export interface PaginationLink {
    active: boolean;
    label: string;
    url: string | null;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
}

export interface SelectOption<T extends string | number = string> {
    value: T;
    label: string;
}

export interface ProfitLossMonth {
    key: string;
    label: string;
}

export interface ProfitLossRow {
    id: number;
    name: string;
    type: CategoryType;
    amounts: Record<string, number>;
    total: number;
}

export interface ProfitLossReport {
    from: string;
    to: string;
    months: ProfitLossMonth[];
    rows: ProfitLossRow[];
    totals: {
        income: Record<string, number>;
        expense: Record<string, number>;
    };
    net_income: Record<string, number>;
}

export interface AuditLogUser {
    id: number;
    name: string;
    email: string;
}

export interface AuditLog {
    id: number;
    log_name: string | null;
    description: string;
    event: string | null;
    subject_type: string | null;
    subject_type_name: string | null;
    subject_id: number | null;
    causer: AuditLogUser | null;
    properties: {
        attributes?: Record<string, unknown>;
        old?: Record<string, unknown>;
        [key: string]: unknown;
    };
    created_at: string;
}
