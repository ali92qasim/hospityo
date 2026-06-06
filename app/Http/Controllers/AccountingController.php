<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\FiscalYear;
use App\Models\SubLedgerEntry;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    // ── Chart of Accounts ──────────────────────────

    public function chartOfAccounts()
    {
        $accounts = Account::with('parent')->orderBy('code')->get();
        $grouped = $accounts->groupBy('type');
        return view('admin.accounting.chart-of-accounts', compact('accounts', 'grouped'));
    }

    public function createAccount()
    {
        $parents = Account::active()->orderBy('code')->get();
        return view('admin.accounting.create-account', compact('parents'));
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:tenant.accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:tenant.accounts,id',
            'description' => 'nullable|string|max:500',
        ]);

        Account::create($request->only('code', 'name', 'type', 'parent_id', 'description'));
        return redirect()->route('accounting.chart-of-accounts')->with('success', 'Account created.');
    }

    public function editAccount(Account $account)
    {
        $parents = Account::active()
            ->where('id', '!=', $account->id)
            ->orderBy('code')
            ->get();

        return view('admin.accounting.edit-account', compact('account', 'parents'));
    }

    public function updateAccount(Request $request, Account $account)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:tenant.accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:tenant.accounts,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Prevent setting parent to self or to a child account
        if ($request->parent_id == $account->id) {
            return back()->withInput()->withErrors(['parent_id' => 'An account cannot be its own parent.']);
        }

        $account->update([
            'code'        => $request->code,
            'name'        => $request->name,
            'type'        => $request->type,
            'parent_id'   => $request->parent_id,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('accounting.chart-of-accounts')->with('success', 'Account updated.');
    }

    // ── General Ledger ─────────────────────────────

    public function generalLedger(Request $request)
    {
        $accountId = $request->input('account_id');
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $accounts = Account::active()->orderBy('code')->get();
        $entries = collect();
        $account = null;

        if ($accountId) {
            $account = Account::find($accountId);
            $entries = \App\Models\JournalEntryLine::where('account_id', $accountId)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$from, $to]))
                ->with('journalEntry')
                ->orderBy('created_at')
                ->get();
        }

        return view('admin.accounting.general-ledger', compact('accounts', 'entries', 'account', 'from', 'to'));
    }

    // ── Journal Entries ────────────────────────────

    public function journalEntries(Request $request)
    {
        $query = JournalEntry::with(['lines.account', 'createdBy'])->latest();

        if ($request->from) $query->where('entry_date', '>=', $request->from);
        if ($request->to) $query->where('entry_date', '<=', $request->to);
        if ($request->search) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('entry_number', 'like', $s)->orWhere('description', 'like', $s));
        }

        $entries = $query->paginate(20)->withQueryString();
        return view('admin.accounting.journal-entries', compact('entries'));
    }

    public function createJournalEntry()
    {
        $accounts = Account::active()->orderBy('code')->get();
        return view('admin.accounting.create-journal-entry', compact('accounts'));
    }

    public function storeJournalEntry(Request $request)
    {
        $request->validate([
            'entry_date'          => 'required|date',
            'description'         => 'required|string|max:500',
            'lines'               => 'required|array|min:2',
            'lines.*.account_id'  => 'required|exists:tenant.accounts,id',
            'lines.*.debit'       => 'nullable|numeric|min:0',
            'lines.*.credit'      => 'nullable|numeric|min:0',
            'lines.*.narration'   => 'nullable|string|max:255',
        ]);

        // Validate that total debits = total credits
        $totalDebit  = collect($request->lines)->sum(fn($l) => (float) ($l['debit'] ?? 0));
        $totalCredit = collect($request->lines)->sum(fn($l) => (float) ($l['credit'] ?? 0));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->withErrors([
                'lines' => 'Journal entry is not balanced. Total debits (' . number_format($totalDebit, 2) . ') must equal total credits (' . number_format($totalCredit, 2) . ').',
            ]);
        }

        if ($totalDebit == 0) {
            return back()->withInput()->withErrors([
                'lines' => 'Journal entry cannot have zero amounts.',
            ]);
        }

        try {
            \Illuminate\Support\Facades\DB::connection('tenant')->transaction(function () use ($request) {
                $entry = JournalEntry::create([
                    'entry_date'  => $request->entry_date,
                    'description' => $request->description,
                    'created_by'  => auth()->id(),
                    'is_auto'     => false,
                ]);

                foreach ($request->lines as $line) {
                    $debit  = (float) ($line['debit'] ?? 0);
                    $credit = (float) ($line['credit'] ?? 0);

                    // Skip lines with no amount
                    if ($debit == 0 && $credit == 0) {
                        continue;
                    }

                    $entry->lines()->create([
                        'account_id' => $line['account_id'],
                        'debit'      => $debit,
                        'credit'     => $credit,
                        'narration'  => $line['narration'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Accounting] Journal entry creation failed', [
                'error' => $e->getMessage(),
            ]);
            return back()->withInput()->with('error', 'Failed to create journal entry. Please try again.');
        }

        return redirect()->route('accounting.journal-entries')->with('success', 'Journal entry created successfully.');
    }

    public function editJournalEntry(JournalEntry $journalEntry)
    {
        // Only allow editing manual entries — auto entries are system-generated
        if ($journalEntry->is_auto) {
            return back()->with('error', 'Auto-generated journal entries cannot be edited.');
        }

        $journalEntry->load('lines.account');
        $accounts = Account::active()->orderBy('code')->get();

        return view('admin.accounting.edit-journal-entry', compact('journalEntry', 'accounts'));
    }

    public function updateJournalEntry(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->is_auto) {
            return back()->with('error', 'Auto-generated journal entries cannot be edited.');
        }

        $request->validate([
            'entry_date'          => 'required|date',
            'description'         => 'required|string|max:500',
            'lines'               => 'required|array|min:2',
            'lines.*.account_id'  => 'required|exists:tenant.accounts,id',
            'lines.*.debit'       => 'nullable|numeric|min:0',
            'lines.*.credit'      => 'nullable|numeric|min:0',
            'lines.*.narration'   => 'nullable|string|max:255',
        ]);

        $totalDebit  = collect($request->lines)->sum(fn($l) => (float) ($l['debit'] ?? 0));
        $totalCredit = collect($request->lines)->sum(fn($l) => (float) ($l['credit'] ?? 0));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->withErrors([
                'lines' => 'Journal entry is not balanced. Total debits (' . number_format($totalDebit, 2) . ') must equal total credits (' . number_format($totalCredit, 2) . ').',
            ]);
        }

        if ($totalDebit == 0) {
            return back()->withInput()->withErrors([
                'lines' => 'Journal entry cannot have zero amounts.',
            ]);
        }

        try {
            \Illuminate\Support\Facades\DB::connection('tenant')->transaction(function () use ($request, $journalEntry) {
                $journalEntry->update([
                    'entry_date'  => $request->entry_date,
                    'description' => $request->description,
                ]);

                // Delete old lines and recreate
                $journalEntry->lines()->delete();

                foreach ($request->lines as $line) {
                    $debit  = (float) ($line['debit'] ?? 0);
                    $credit = (float) ($line['credit'] ?? 0);

                    if ($debit == 0 && $credit == 0) {
                        continue;
                    }

                    $journalEntry->lines()->create([
                        'account_id' => $line['account_id'],
                        'debit'      => $debit,
                        'credit'     => $credit,
                        'narration'  => $line['narration'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Accounting] Journal entry update failed', [
                'entry_id' => $journalEntry->id,
                'error'    => $e->getMessage(),
            ]);
            return back()->withInput()->with('error', 'Failed to update journal entry. Please try again.');
        }

        return redirect()->route('accounting.journal-entries')->with('success', 'Journal entry updated successfully.');
    }

    // ── Sub-Ledgers ────────────────────────────────

    public function patientLedger(Request $request)
    {
        $patientId = $request->input('patient_id');
        $patients = \App\Models\Patient::orderBy('name')->get();
        $entries = collect();

        if ($patientId) {
            $entries = SubLedgerEntry::where('ledger_type', 'patient')
                ->where('ledger_id', $patientId)
                ->with('journalEntry')
                ->orderBy('created_at')
                ->get();
        }

        return view('admin.accounting.patient-ledger', compact('patients', 'entries', 'patientId'));
    }

    public function vendorLedger(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $entries = collect();

        if ($supplierId) {
            $entries = SubLedgerEntry::where('ledger_type', 'vendor')
                ->where('ledger_id', $supplierId)
                ->with('journalEntry')
                ->orderBy('created_at')
                ->get();
        }

        return view('admin.accounting.vendor-ledger', compact('suppliers', 'entries', 'supplierId'));
    }

    // ── Financial Reports ──────────────────────────

    public function profitAndLoss(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $revenue = Account::ofType('revenue')->active()->get()->map(fn($a) => [
            'account' => $a, 'balance' => $a->getBalance($from, $to),
        ])->filter(fn($r) => $r['balance'] != 0);

        $expenses = Account::ofType('expense')->active()->get()->map(fn($a) => [
            'account' => $a, 'balance' => $a->getBalance($from, $to),
        ])->filter(fn($r) => $r['balance'] != 0);

        $totalRevenue = $revenue->sum('balance');
        $totalExpenses = $expenses->sum('balance');
        $netIncome = $totalRevenue - $totalExpenses;

        return view('admin.accounting.profit-loss', compact('revenue', 'expenses', 'totalRevenue', 'totalExpenses', 'netIncome', 'from', 'to'));
    }

    public function balanceSheet(Request $request)
    {
        $asOf = $request->input('as_of', now()->format('Y-m-d'));

        $assets = Account::ofType('asset')->active()->get()->map(fn($a) => [
            'account' => $a, 'balance' => $a->getBalance(null, $asOf),
        ])->filter(fn($r) => $r['balance'] != 0);

        $liabilities = Account::ofType('liability')->active()->get()->map(fn($a) => [
            'account' => $a, 'balance' => $a->getBalance(null, $asOf),
        ])->filter(fn($r) => $r['balance'] != 0);

        $equity = Account::ofType('equity')->active()->get()->map(fn($a) => [
            'account' => $a, 'balance' => $a->getBalance(null, $asOf),
        ])->filter(fn($r) => $r['balance'] != 0);

        // Retained earnings = total revenue - total expenses (all time up to date)
        $totalRevenue = Account::ofType('revenue')->active()->get()->sum(fn($a) => $a->getBalance(null, $asOf));
        $totalExpenses = Account::ofType('expense')->active()->get()->sum(fn($a) => $a->getBalance(null, $asOf));
        $retainedEarnings = $totalRevenue - $totalExpenses;

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance') + $retainedEarnings;

        return view('admin.accounting.balance-sheet', compact(
            'assets', 'liabilities', 'equity', 'retainedEarnings',
            'totalAssets', 'totalLiabilities', 'totalEquity', 'asOf'
        ));
    }
}
