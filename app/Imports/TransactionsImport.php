<?php

namespace App\Imports;

use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TransactionsImport implements ToCollection, WithHeadingRow
{
    private int $importedCount = 0;

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     *
     * @throws ValidationException
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => __('The uploaded file contains no transaction records.'),
            ]);
        }

        /** @var array<string, ChartOfAccount> $accounts */
        $accounts = ChartOfAccount::query()->get()->keyBy(fn (ChartOfAccount $account): string => trim((string) $account->code))->all();

        $transactionsToCreate = [];
        $rowNumber = 1; // Row 1 is heading row, rows in collection start at file row 2

        foreach ($rows as $row) {
            $rowNumber++;

            // Skip entirely empty rows
            $hasAnyValue = collect($row)->filter(fn ($val) => $val !== null && trim((string) $val) !== '')->isNotEmpty();
            if (! $hasAnyValue) {
                continue;
            }

            $rawDate = $row['date'] ?? $row['transaction_date'] ?? $row['tanggal'] ?? null;
            $rawCode = $row['coa_code'] ?? $row['account_code'] ?? $row['code'] ?? $row['coa'] ?? null;
            $rawAmount = $row['amount'] ?? $row['jumlah'] ?? null;
            $rawDebit = $row['debit'] ?? $row['debet'] ?? null;
            $rawCredit = $row['credit'] ?? $row['kredit'] ?? null;
            $rawDescription = $row['description'] ?? $row['deskripsi'] ?? $row['keterangan'] ?? $row['memo'] ?? null;

            if ($rawDate === null || trim((string) $rawDate) === '') {
                throw ValidationException::withMessages([
                    'file' => __('Row :row: Transaction date is required.', ['row' => $rowNumber]),
                ]);
            }

            $transactionDate = $this->parseDate($rawDate, $rowNumber);

            if ($rawCode === null || trim((string) $rawCode) === '') {
                throw ValidationException::withMessages([
                    'file' => __('Row :row: Chart of account code is required.', ['row' => $rowNumber]),
                ]);
            }

            $code = trim((string) $rawCode);
            if (! isset($accounts[$code])) {
                throw ValidationException::withMessages([
                    'file' => __("Row :row: Chart of account with code ':code' not found.", ['row' => $rowNumber, 'code' => $code]),
                ]);
            }

            $amount = $this->parseAmount($rawAmount, $rawDebit, $rawCredit, $rowNumber);

            $description = $rawDescription !== null && trim((string) $rawDescription) !== '' ? trim((string) $rawDescription) : null;

            $transactionsToCreate[] = [
                'chart_of_account_id' => $accounts[$code]->id,
                'transaction_date' => $transactionDate,
                'amount' => $amount,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($transactionsToCreate)) {
            throw ValidationException::withMessages([
                'file' => __('No valid transaction rows found in the file.'),
            ]);
        }

        foreach ($transactionsToCreate as $item) {
            Transaction::query()->create([
                'chart_of_account_id' => $item['chart_of_account_id'],
                'transaction_date' => $item['transaction_date'],
                'amount' => $item['amount'],
                'description' => $item['description'],
            ]);
            $this->importedCount++;
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    private function parseDate(mixed $rawDate, int $rowNumber): string
    {
        if (is_numeric($rawDate)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $rawDate)->format('Y-m-d');
            } catch (Exception) {
                // fallback to Carbon parse
            }
        }

        try {
            return CarbonImmutable::parse((string) $rawDate)->toDateString();
        } catch (Exception) {
            throw ValidationException::withMessages([
                'file' => __("Row :row: Invalid date format ':date'.", ['row' => $rowNumber, 'date' => (string) $rawDate]),
            ]);
        }
    }

    private function parseAmount(mixed $rawAmount, mixed $rawDebit, mixed $rawCredit, int $rowNumber): int
    {
        if ($rawAmount !== null && trim((string) $rawAmount) !== '') {
            $cleaned = $this->cleanNumber((string) $rawAmount);
            if ($cleaned > 0) {
                return $cleaned;
            }
        }

        $debit = $rawDebit !== null && trim((string) $rawDebit) !== '' ? $this->cleanNumber((string) $rawDebit) : 0;
        $credit = $rawCredit !== null && trim((string) $rawCredit) !== '' ? $this->cleanNumber((string) $rawCredit) : 0;

        if ($credit > 0) {
            return $credit;
        }

        if ($debit > 0) {
            return $debit;
        }

        throw ValidationException::withMessages([
            'file' => __('Row :row: Amount must be greater than zero.', ['row' => $rowNumber]),
        ]);
    }

    private function cleanNumber(string $value): int
    {
        $value = trim($value);
        // Remove currency symbols, commas, spaces
        $sanitized = preg_replace('/[^0-9]/', '', $value);

        return (int) $sanitized;
    }
}
