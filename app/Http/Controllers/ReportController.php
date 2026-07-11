<?php

namespace App\Http\Controllers;

use App\Services\BillItemRevenueGrouper;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Visit;
use App\Models\Doctor;
use App\Models\BillItem;
use App\Models\Service;
use App\Models\InvestigationOrder;
use App\Models\Investigation;
use App\Models\LabResult;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Account;
use App\Models\Medicine;
use App\Models\InventoryTransaction;
use App\Models\Appointment;
use App\Models\Admission;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Patient;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dailyCashRegister(Request $request)
    {
        $startDate = $request->input('start_date', today()->format('Y-m-d'));
        $endDate   = $request->input('end_date', today()->format('Y-m-d'));

        // ── Cash Inflows (Payments received from patients) ────────────────────
        $payments = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->with('bill.patient')
            ->orderBy('payment_date')
            ->orderBy('created_at')
            ->get();

        // ── Cash Outflows (journal entries where expense accounts are debited) ──
        // Exclude "Discounts Given" (5200) — discounts are accounting contra-entries,
        // not actual cash leaving the business. They should not affect the cash register.
        $expenseAccountIds = Account::where('type', 'expense')
            ->where('code', '!=', '5200')
            ->pluck('id');

        $outflowLines = \App\Models\JournalEntryLine::whereIn('account_id', $expenseAccountIds)
            ->where('debit', '>', 0)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$startDate, $endDate]))
            ->with(['journalEntry', 'account'])
            ->get();

        $totalOutflows = (float) $outflowLines->sum('debit');

        // ── Bills created in the period (for reference) ──────────────────────
        $bills = Bill::whereBetween('bill_date', [$startDate, $endDate])
            ->with('patient', 'payments')
            ->orderBy('bill_date')
            ->get();

        // ── Opening balance (cash accounts balance before start date) ─────────
        $cashAccounts = Account::where('type', 'asset')
            ->where(function ($q) {
                $q->where('code', 'like', '11%')
                  ->orWhere('code', '1100')
                  ->orWhere('code', '1110');
            })->active()->get();

        $openingBalance = $cashAccounts->sum(function ($account) use ($startDate) {
            return $account->getBalance(null, \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d'));
        });

        // ── Calculate summaries ───────────────────────────────────────────────
        $totalInflows  = $payments->sum('amount');
        $closingBalance = $openingBalance + $totalInflows - $totalOutflows;

        $summary = [
            'opening_balance'    => $openingBalance,
            'total_inflows'      => $totalInflows,
            'total_outflows'     => $totalOutflows,
            'closing_balance'    => $closingBalance,
            'total_bills'        => $bills->count(),
            'total_billed'       => $bills->sum('total_amount'),
            'total_collected'    => $totalInflows,
            'total_outstanding'  => $bills->sum(fn($b) => max(0, $b->total_amount - $b->paid_amount)),
            'total_discount'     => $bills->sum('discount_amount'),
            'patient_credits'    => $bills->sum(fn($b) => max(0, $b->paid_amount - $b->total_amount)),
            'cash_payments'      => $payments->where('payment_method', 'cash')->sum('amount'),
            'card_payments'      => $payments->where('payment_method', 'card')->sum('amount'),
            'insurance_payments' => $payments->where('payment_method', 'insurance')->sum('amount'),
            'other_payments'     => $payments->whereNotIn('payment_method', ['cash', 'card', 'insurance'])->sum('amount'),
        ];

        return view('admin.reports.daily-cash-register', compact(
            'payments', 'outflowLines', 'bills', 'summary', 'startDate', 'endDate'
        ));
    }

    public function patientVisits(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $doctorId = $request->input('doctor_id');
        $status = $request->input('status');

        // Build query
        $query = Visit::whereBetween('visit_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['patient', 'doctor']);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $visits = $query->orderBy('visit_datetime', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $stats = [
            'total_visits' => $visits->count(),
            'completed' => $visits->where('status', 'completed')->count(),
            'in_progress' => $visits->where('status', 'in_progress')->count(),
            'cancelled' => $visits->where('status', 'cancelled')->count(),
            'unique_patients' => $visits->pluck('patient_id')->unique()->count(),
            'new_patients' => $visits->filter(function($visit) {
                return Visit::where('patient_id', $visit->patient_id)
                    ->where('id', '<', $visit->id)
                    ->count() === 0;
            })->count(),
        ];

        // Doctor-wise breakdown
        $doctorStats = $visits->groupBy('doctor_id')->map(function($doctorVisits) {
            return [
                'doctor' => $doctorVisits->first()->doctor,
                'count' => $doctorVisits->count(),
                'completed' => $doctorVisits->where('status', 'completed')->count(),
            ];
        })->sortByDesc('count');

        // Daily trend
        $dailyTrend = $visits->groupBy(function($visit) {
            return $visit->visit_datetime->format('Y-m-d');
        })->map(function($dayVisits) {
            return $dayVisits->count();
        })->sortKeys();

        $doctors = Doctor::orderBy('name')->get();

        return view('admin.reports.patient-visits', compact(
            'visits', 'stats', 'doctorStats', 'dailyTrend', 'doctors',
            'startDate', 'endDate', 'doctorId', 'status'
        ));
    }

    public function revenue(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'service');

        // Get bills within date range (using bill_date — the business date)
        $bills = Bill::whereBetween('bill_date', [$startDate, $endDate])
            ->with(['billItems.service', 'patient', 'visit.doctor'])
            ->get();

        // Totals must reconcile: revenue = collected_applied + outstanding
        // Collected (cash) can exceed bill totals due to overpayments/advances.
        // "Collected applied" is capped at each bill total so the cards stay consistent.
        $totalRevenue = (float) $bills->sum('total_amount');
        $totalPaid = (float) $bills->sum('paid_amount');
        $totalOutstanding = (float) $bills->sum(fn($b) => max(0, (float) $b->total_amount - (float) $b->paid_amount));
        $totalOverpaid = (float) $bills->sum(fn($b) => max(0, (float) $b->paid_amount - (float) $b->total_amount));
        $collectedApplied = $totalRevenue - $totalOutstanding; // equals sum(min(paid, total))

        $totals = [
            'total_revenue' => $totalRevenue,
            'total_collected' => $collectedApplied,
            'total_collected_cash' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'total_overpaid' => $totalOverpaid,
            'total_bills' => $bills->count(),
        ];

        // Revenue by service / investigation (group linked services and lab lines correctly)
        $investigationsByName = BillItemRevenueGrouper::investigationsByName();

        $billItems = BillItem::whereHas('bill', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('bill_date', [$startDate, $endDate]);
            })
            ->with(['service', 'investigation'])
            ->get();

        $serviceRevenue = $billItems
            ->groupBy(fn (BillItem $item) => BillItemRevenueGrouper::groupKey($item, $investigationsByName))
            ->map(function ($items) use ($investigationsByName) {
                $sample = $items->first();

                return [
                    'service' => BillItemRevenueGrouper::groupLabel($sample, $investigationsByName),
                    'is_investigation' => BillItemRevenueGrouper::isInvestigation($sample, $investigationsByName),
                    'quantity' => $items->sum('quantity'),
                    'revenue' => $items->sum('total_price'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // Revenue by doctor — full bill totals attributed to the visit doctor
        $doctorRevenue = $bills->filter(function ($bill) {
                return $bill->visit && $bill->visit->doctor;
            })
            ->groupBy('visit.doctor_id')
            ->map(function ($doctorBills) {
                $doctor = $doctorBills->first()->visit->doctor;

                return [
                    'doctor' => $doctor,
                    'bills' => $doctorBills->count(),
                    'revenue' => (float) $doctorBills->sum('total_amount'),
                    'collected' => (float) $doctorBills->sum('paid_amount'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // Doctor share comparison:
        // - share_earned: share_amount on bills whose bill_date is inside the range
        // - share_collected: allocation ledger entries created inside the range (payment events)
        $billIdsInRange = $bills->pluck('id');

        $shareEarnedByDoctor = DoctorShareItem::query()
            ->active()
            ->whereIn('bill_id', $billIdsInRange)
            ->selectRaw('doctor_id, SUM(share_amount) as share_earned')
            ->groupBy('doctor_id')
            ->pluck('share_earned', 'doctor_id');

        $shareCollectedByDoctor = DoctorShareAllocation::query()
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('doctor_id, SUM(amount) as share_collected')
            ->groupBy('doctor_id')
            ->pluck('share_collected', 'doctor_id');

        // Attach share figures to each doctor row for side-by-side comparison
        $doctorRevenue = $doctorRevenue->map(function ($row) use ($shareEarnedByDoctor, $shareCollectedByDoctor) {
            $doctorId = $row['doctor']->id;
            $row['share_earned'] = (float) ($shareEarnedByDoctor[$doctorId] ?? 0);
            $row['share_collected'] = (float) ($shareCollectedByDoctor[$doctorId] ?? 0);
            return $row;
        });

        // Daily revenue trend
        $dailyRevenue = $bills->groupBy(function($bill) {
                return $bill->bill_date->format('Y-m-d');
            })
            ->map(function($dayBills) {
                return [
                    'revenue' => $dayBills->sum('total_amount'),
                    'collected' => $dayBills->sum('paid_amount'),
                    'bills' => $dayBills->count(),
                ];
            })
            ->sortKeys();

        // Monthly comparison (if date range spans multiple months)
        $monthlyRevenue = $bills->groupBy(function($bill) {
                return $bill->bill_date->format('Y-m');
            })
            ->map(function($monthBills) {
                return [
                    'revenue' => $monthBills->sum('total_amount'),
                    'collected' => $monthBills->sum('paid_amount'),
                    'bills' => $monthBills->count(),
                ];
            })
            ->sortKeys();

        return view('admin.reports.revenue', compact(
            'totals', 'serviceRevenue', 'doctorRevenue', 'dailyRevenue', 'monthlyRevenue',
            'startDate', 'endDate', 'groupBy'
        ));
    }


    public function outstandingBills(Request $request)
    {
        $agingPeriod = $request->input('aging', 'all'); // all, 30, 60, 90

        // Get bills with outstanding amounts
        $query = Bill::where('status', '!=', 'paid')
            ->with(['patient', 'payments'])
            ->orderBy('created_at', 'desc');

        // Apply aging filter
        if ($agingPeriod !== 'all') {
            $days = (int) $agingPeriod;
            $query->where('created_at', '<=', now()->subDays($days));
        }

        $bills = $query->get();

        // Calculate aging buckets
        $agingAnalysis = [
            '0-30' => [
                'count' => 0,
                'amount' => 0,
                'bills' => collect(),
            ],
            '31-60' => [
                'count' => 0,
                'amount' => 0,
                'bills' => collect(),
            ],
            '61-90' => [
                'count' => 0,
                'amount' => 0,
                'bills' => collect(),
            ],
            '90+' => [
                'count' => 0,
                'amount' => 0,
                'bills' => collect(),
            ],
        ];

        foreach ($bills as $bill) {
            $daysOld = $bill->created_at->diffInDays(now());
            $outstanding = $bill->total_amount - $bill->paid_amount;

            if ($daysOld <= 30) {
                $agingAnalysis['0-30']['count']++;
                $agingAnalysis['0-30']['amount'] += $outstanding;
                $agingAnalysis['0-30']['bills']->push($bill);
            } elseif ($daysOld <= 60) {
                $agingAnalysis['31-60']['count']++;
                $agingAnalysis['31-60']['amount'] += $outstanding;
                $agingAnalysis['31-60']['bills']->push($bill);
            } elseif ($daysOld <= 90) {
                $agingAnalysis['61-90']['count']++;
                $agingAnalysis['61-90']['amount'] += $outstanding;
                $agingAnalysis['61-90']['bills']->push($bill);
            } else {
                $agingAnalysis['90+']['count']++;
                $agingAnalysis['90+']['amount'] += $outstanding;
                $agingAnalysis['90+']['bills']->push($bill);
            }
        }

        // Summary statistics
        $summary = [
            'total_outstanding' => $bills->sum(function($bill) {
                return $bill->total_amount - $bill->paid_amount;
            }),
            'total_bills' => $bills->count(),
            'partially_paid' => $bills->where('status', 'partial')->count(),
            'unpaid' => $bills->where('status', 'pending')->count(),
        ];

        // Patient-wise outstanding
        $patientOutstanding = $bills->groupBy('patient_id')
            ->map(function($patientBills) {
                $patient = $patientBills->first()->patient;
                $outstanding = $patientBills->sum(function($bill) {
                    return $bill->total_amount - $bill->paid_amount;
                });
                return [
                    'patient' => $patient,
                    'bills' => $patientBills->count(),
                    'outstanding' => $outstanding,
                    'oldest_bill' => $patientBills->sortBy('created_at')->first(),
                ];
            })
            ->sortByDesc('outstanding')
            ->take(10);

        return view('admin.reports.outstanding-bills', compact(
            'bills', 'agingAnalysis', 'summary', 'patientOutstanding', 'agingPeriod'
        ));
    }


    public function labTests(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', today()->format('Y-m-d'));
        $testType  = $request->input('test_type');

        // Get investigation orders within date range with items
        $query = InvestigationOrder::whereBetween('ordered_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['items.investigation', 'patient', 'doctor']);

        $orders = $query->get();

        // Filter by test type if specified
        if ($testType) {
            $orders = $orders->filter(function ($order) use ($testType) {
                return $order->items->contains(function ($item) use ($testType) {
                    $cat = $item->investigation?->category;
                    if ($testType === 'lab') {
                        return in_array($cat, ['hematology', 'biochemistry', 'microbiology', 'immunology', 'histopathology', 'molecular']);
                    }
                    return in_array($cat, ['x-ray', 'ultrasound', 'ct-scan', 'mri', 'cardiac-diagnostics']);
                });
            });
        }

        // Status mapping — use order-level status
        $stats = [
            'total_orders'     => $orders->count(),
            'completed'        => $orders->whereIn('status', ['reported', 'verified'])->count(),
            'pending'          => $orders->where('status', 'ordered')->count(),
            'sample_collected' => $orders->where('status', 'collected')->count(),
            'in_progress'      => $orders->where('status', 'testing')->count(),
            'lab_tests'        => $orders->filter(fn($o) => $o->items->contains(fn($i) =>
                in_array($i->investigation?->category, ['hematology', 'biochemistry', 'microbiology', 'immunology', 'histopathology', 'molecular'])
            ))->count(),
            'radiology_tests'  => $orders->filter(fn($o) => $o->items->contains(fn($i) =>
                in_array($i->investigation?->category, ['x-ray', 'ultrasound', 'ct-scan', 'mri', 'cardiac-diagnostics'])
            ))->count(),
        ];

        // Investigation-wise breakdown (from items, not order-level investigation_id)
        $allItems = $orders->flatMap->items;

        $testBreakdown = $allItems->groupBy('investigation_id')
            ->map(function ($items) {
                $investigation = $items->first()?->investigation;
                return [
                    'investigation' => $investigation,
                    'count'         => $items->count(),
                    'completed'     => $items->whereIn('status', ['verified', 'reported'])->count(),
                    'pending'       => $items->whereIn('status', ['ordered', 'collected', 'testing'])->count(),
                ];
            })
            ->filter(fn($r) => $r['investigation'] !== null)
            ->sortByDesc('count')
            ->values();

        // Doctor-wise orders
        $doctorOrders = $orders->filter(fn($o) => $o->doctor)
            ->groupBy('doctor_id')
            ->map(function ($doctorOrders) {
                return [
                    'doctor'    => $doctorOrders->first()->doctor,
                    'orders'    => $doctorOrders->count(),
                    'completed' => $doctorOrders->whereIn('status', ['reported', 'verified'])->count(),
                ];
            })
            ->sortByDesc('orders')
            ->values();

        // Daily trend
        $dailyTrend = $orders->groupBy(fn($o) => $o->ordered_at?->format('Y-m-d') ?? $o->created_at->format('Y-m-d'))
            ->map(function ($dayOrders) {
                return [
                    'total'     => $dayOrders->count(),
                    'completed' => $dayOrders->whereIn('status', ['reported', 'verified'])->count(),
                ];
            })
            ->sortKeys();

        // Average turnaround time (ordered_at → result reported_at)
        $completedOrders = $orders->whereIn('status', ['reported', 'verified'])
            ->filter(fn($o) => $o->ordered_at && $o->result?->reported_at);

        $avgTurnaroundTime = $completedOrders->count() > 0
            ? $completedOrders->avg(fn($o) => $o->ordered_at->diffInHours($o->result->reported_at))
            : 0;

        return view('admin.reports.lab-tests', compact(
            'orders', 'stats', 'testBreakdown', 'doctorOrders', 'dailyTrend', 'avgTurnaroundTime',
            'startDate', 'endDate', 'testType'
        ));
    }

    public function medicineSales(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $medicineId = $request->input('medicine_id');

        // Get prescriptions within date range
        $query = Prescription::whereBetween('created_at', [$startDate, $endDate])
            ->with(['items.medicine.brand', 'items.medicine.category', 'visit.patient', 'visit.doctor']);

        $prescriptions = $query->get();

        // Get all prescription items
        $items = $prescriptions->flatMap(function($prescription) {
            return $prescription->items;
        });

        if ($medicineId) {
            $items = $items->where('medicine_id', $medicineId);
        }

        // Calculate statistics
        $stats = [
            'total_prescriptions' => $prescriptions->count(),
            'total_items' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'unique_medicines' => $items->pluck('medicine_id')->unique()->count(),
        ];

        // Medicine-wise breakdown
        $medicineBreakdown = $items->groupBy('medicine_id')
            ->map(function($medicineItems) {
                $medicine = $medicineItems->first()->medicine;
                return [
                    'medicine' => $medicine,
                    'quantity' => $medicineItems->sum('quantity'),
                    'prescriptions' => $medicineItems->count(),
                ];
            })
            ->sortByDesc('quantity')
            ->values();

        // Category-wise breakdown
        $categoryBreakdown = $items->filter(function($item) {
                return $item->medicine && $item->medicine->category;
            })
            ->groupBy('medicine.category_id')
            ->map(function($categoryItems) {
                $category = $categoryItems->first()->medicine->category;
                return [
                    'category' => $category,
                    'quantity' => $categoryItems->sum('quantity'),
                    'items' => $categoryItems->count(),
                ];
            })
            ->sortByDesc('quantity')
            ->values();

        // Brand-wise breakdown
        $brandBreakdown = $items->filter(function($item) {
                return $item->medicine && $item->medicine->brand;
            })
            ->groupBy('medicine.brand_id')
            ->map(function($brandItems) {
                $brand = $brandItems->first()->medicine->brand;
                return [
                    'brand' => $brand,
                    'quantity' => $brandItems->sum('quantity'),
                    'items' => $brandItems->count(),
                ];
            })
            ->sortByDesc('quantity')
            ->values();

        // Doctor-wise prescriptions
        $doctorPrescriptions = $prescriptions->filter(function($prescription) {
                return $prescription->visit && $prescription->visit->doctor;
            })
            ->groupBy('visit.doctor_id')
            ->map(function($doctorPrescriptions) {
                $doctor = $doctorPrescriptions->first()->visit->doctor;
                $items = $doctorPrescriptions->flatMap(function($p) { return $p->items; });
                return [
                    'doctor' => $doctor,
                    'prescriptions' => $doctorPrescriptions->count(),
                    'items' => $items->count(),
                    'quantity' => $items->sum('quantity'),
                ];
            })
            ->sortByDesc('prescriptions')
            ->values();

        // Daily trend
        $dailyTrend = $prescriptions->groupBy(function($prescription) {
                return $prescription->created_at->format('Y-m-d');
            })
            ->map(function($dayPrescriptions) {
                $dayItems = $dayPrescriptions->flatMap(function($p) { return $p->items; });
                return [
                    'prescriptions' => $dayPrescriptions->count(),
                    'quantity' => $dayItems->sum('quantity'),
                ];
            })
            ->sortKeys();

        $medicines = Medicine::where('status', 'active')->orderBy('name')->get();

        return view('admin.reports.medicine-sales', compact(
            'prescriptions', 'stats', 'medicineBreakdown', 'categoryBreakdown', 'brandBreakdown',
            'doctorPrescriptions', 'dailyTrend', 'medicines', 'startDate', 'endDate', 'medicineId'
        ));
    }

    public function inventoryStatus(Request $request)
    {
        $categoryId = $request->input('category_id');
        $stockStatus = $request->input('stock_status'); // all, low, out

        // Get all medicines with manage_stock enabled
        $query = Medicine::where('manage_stock', true)
            ->with(['category', 'brand', 'baseUnit', 'dispensingUnit']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $medicines = $query->get();

        // Calculate stock levels for each medicine
        $inventory = $medicines->map(function($medicine) {
            $currentStock = $medicine->getCurrentStock();
            $stockInUnit = $medicine->dispensing_unit_id
                ? $medicine->getCurrentStockInUnit($medicine->dispensing_unit_id)
                : $currentStock;
            $isLowStock = $medicine->isLowStock();
            $isOutOfStock = $currentStock <= 0;

            // Get recent transactions
            $recentTransactions = InventoryTransaction::where('medicine_id', $medicine->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Calculate stock value (if you have cost price)
            $stockValue = 0;

            return [
                'medicine' => $medicine,
                'current_stock' => $currentStock,
                'stock_in_unit' => $stockInUnit,
                'reorder_level' => $medicine->reorder_level,
                'is_low_stock' => $isLowStock,
                'is_out_of_stock' => $isOutOfStock,
                'recent_transactions' => $recentTransactions,
                'stock_value' => $stockValue,
            ];
        });

        // Apply stock status filter
        if ($stockStatus === 'low') {
            $inventory = $inventory->filter(function($item) {
                return $item['is_low_stock'] && !$item['is_out_of_stock'];
            });
        } elseif ($stockStatus === 'out') {
            $inventory = $inventory->filter(function($item) {
                return $item['is_out_of_stock'];
            });
        }

        // Calculate statistics
        $stats = [
            'total_medicines' => $medicines->count(),
            'low_stock' => $inventory->filter(fn($i) => $i['is_low_stock'] && !$i['is_out_of_stock'])->count(),
            'out_of_stock' => $inventory->filter(fn($i) => $i['is_out_of_stock'])->count(),
            'adequate_stock' => $inventory->filter(fn($i) => !$i['is_low_stock'] && !$i['is_out_of_stock'])->count(),
        ];

        // Category-wise stock status
        $categoryStats = $inventory->groupBy('medicine.category_id')
            ->map(function($categoryItems) {
                $category = $categoryItems->first()['medicine']->category;
                return [
                    'category' => $category,
                    'total' => $categoryItems->count(),
                    'low_stock' => $categoryItems->filter(fn($i) => $i['is_low_stock'])->count(),
                    'out_of_stock' => $categoryItems->filter(fn($i) => $i['is_out_of_stock'])->count(),
                ];
            })
            ->values();

        $categories = \App\Models\MedicineCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.reports.inventory-status', compact(
            'inventory', 'stats', 'categoryStats', 'categories', 'categoryId', 'stockStatus'
        ));
    }

    public function expiryReport(Request $request)
    {
        $days = $request->input('days', 90); // Default to 90 days
        $categoryId = $request->input('category_id');

        $expiryDate = now()->addDays($days);

        // Get inventory transactions with expiry dates
        $query = InventoryTransaction::where('type', 'stock_in')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $expiryDate)
            ->where('quantity', '>', 0) // Only items still in stock
            ->with(['medicine.category', 'medicine.brand', 'medicine.baseUnit']);

        if ($categoryId) {
            $query->whereHas('medicine', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $transactions = $query->orderBy('expiry_date')->get();

        // Group by expiry status
        $expired = $transactions->filter(function($t) {
            return $t->expiry_date < now();
        });

        $expiringSoon = $transactions->filter(function($t) {
            return $t->expiry_date >= now() && $t->expiry_date <= now()->addDays(30);
        });

        $expiringLater = $transactions->filter(function($t) {
            return $t->expiry_date > now()->addDays(30);
        });

        // Calculate statistics
        $stats = [
            'total_items' => $transactions->count(),
            'expired' => $expired->count(),
            'expiring_30_days' => $expiringSoon->count(),
            'expiring_later' => $expiringLater->count(),
            'total_quantity_expired' => $expired->sum('quantity'),
            'total_quantity_expiring' => $expiringSoon->sum('quantity'),
        ];

        // Medicine-wise expiry breakdown
        $medicineExpiry = $transactions->groupBy('medicine_id')
            ->map(function($medicineTransactions) {
                $medicine = $medicineTransactions->first()->medicine;
                $expired = $medicineTransactions->filter(fn($t) => $t->expiry_date < now());
                $expiringSoon = $medicineTransactions->filter(fn($t) => $t->expiry_date >= now() && $t->expiry_date <= now()->addDays(30));

                return [
                    'medicine' => $medicine,
                    'total_batches' => $medicineTransactions->count(),
                    'expired_batches' => $expired->count(),
                    'expiring_soon_batches' => $expiringSoon->count(),
                    'expired_quantity' => $expired->sum('quantity'),
                    'expiring_quantity' => $expiringSoon->sum('quantity'),
                    'earliest_expiry' => $medicineTransactions->min('expiry_date'),
                ];
            })
            ->sortBy('earliest_expiry')
            ->values();

        // Category-wise expiry
        $categoryExpiry = $transactions->filter(function($t) {
                return $t->medicine && $t->medicine->category;
            })
            ->groupBy('medicine.category_id')
            ->map(function($categoryTransactions) {
                $category = $categoryTransactions->first()->medicine->category;
                $expired = $categoryTransactions->filter(fn($t) => $t->expiry_date < now());
                $expiringSoon = $categoryTransactions->filter(fn($t) => $t->expiry_date >= now() && $t->expiry_date <= now()->addDays(30));

                return [
                    'category' => $category,
                    'total' => $categoryTransactions->count(),
                    'expired' => $expired->count(),
                    'expiring_soon' => $expiringSoon->count(),
                ];
            })
            ->values();

        $categories = \App\Models\MedicineCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.reports.expiry-report', compact(
            'transactions', 'expired', 'expiringSoon', 'expiringLater', 'stats',
            'medicineExpiry', 'categoryExpiry', 'categories', 'days', 'categoryId'
        ));
    }

    public function doctorPerformance(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $doctorId = $request->input('doctor_id');

        // Get doctors with their performance data
        $query = Doctor::with(['department']);

        if ($doctorId) {
            $query->where('id', $doctorId);
        }

        $doctors = $query->get();

        // Calculate performance metrics for each doctor
        $performance = $doctors->map(function($doctor) use ($startDate, $endDate) {
            // Get visits
            $visits = Visit::where('doctor_id', $doctor->id)
                ->whereBetween('visit_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();

            // Get appointments
            $appointments = \App\Models\Appointment::where('doctor_id', $doctor->id)
                ->whereBetween('appointment_datetime', [$startDate, $endDate])
                ->get();

            // Get prescriptions
            $prescriptions = Prescription::whereHas('visit', function($q) use ($doctor, $startDate, $endDate) {
                $q->where('doctor_id', $doctor->id)
                  ->whereBetween('visit_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })->get();

            // Get investigation orders
            $investigationOrders = InvestigationOrder::where('doctor_id', $doctor->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            // Get bills generated from doctor's visits
            $bills = Bill::whereHas('visit', function($q) use ($doctor, $startDate, $endDate) {
                $q->where('doctor_id', $doctor->id)
                  ->whereBetween('visit_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })->get();

            // Calculate metrics
            $totalVisits = $visits->count();
            $completedVisits = $visits->where('status', 'completed')->count();
            $totalAppointments = $appointments->count();
            $completedAppointments = $appointments->where('status', 'completed')->count();
            $cancelledAppointments = $appointments->where('status', 'cancelled')->count();

            $totalRevenue = $bills->sum('total_amount');
            $totalCollected = $bills->sum('paid_amount');

            $avgVisitsPerDay = $totalVisits > 0 ? $totalVisits / max(1, now()->parse($startDate)->diffInDays(now()->parse($endDate)) + 1) : 0;

            return [
                'doctor' => $doctor,
                'total_visits' => $totalVisits,
                'completed_visits' => $completedVisits,
                'completion_rate' => $totalVisits > 0 ? ($completedVisits / $totalVisits * 100) : 0,
                'total_appointments' => $totalAppointments,
                'completed_appointments' => $completedAppointments,
                'cancelled_appointments' => $cancelledAppointments,
                'appointment_completion_rate' => $totalAppointments > 0 ? ($completedAppointments / $totalAppointments * 100) : 0,
                'total_prescriptions' => $prescriptions->count(),
                'total_investigations' => $investigationOrders->count(),
                'total_revenue' => $totalRevenue,
                'total_collected' => $totalCollected,
                'avg_visits_per_day' => $avgVisitsPerDay,
                'unique_patients' => $visits->pluck('patient_id')->unique()->count(),
            ];
        })->sortByDesc('total_visits');

        // Overall statistics
        $stats = [
            'total_doctors' => $doctors->count(),
            'total_visits' => $performance->sum('total_visits'),
            'total_revenue' => $performance->sum('total_revenue'),
            'total_prescriptions' => $performance->sum('total_prescriptions'),
            'total_investigations' => $performance->sum('total_investigations'),
            'avg_visits_per_doctor' => $doctors->count() > 0 ? $performance->sum('total_visits') / $doctors->count() : 0,
        ];

        // Department-wise performance
        $departmentPerformance = $performance->filter(function($p) {
                return $p['doctor']->department;
            })
            ->groupBy('doctor.department_id')
            ->map(function($deptDoctors) {
                $department = $deptDoctors->first()['doctor']->department;
                return [
                    'department' => $department,
                    'doctors' => $deptDoctors->count(),
                    'visits' => $deptDoctors->sum('total_visits'),
                    'revenue' => $deptDoctors->sum('total_revenue'),
                    'prescriptions' => $deptDoctors->sum('total_prescriptions'),
                ];
            })
            ->sortByDesc('visits')
            ->values();

        $allDoctors = Doctor::orderBy('name')->get();

        return view('admin.reports.doctor-performance', compact(
            'performance', 'stats', 'departmentPerformance', 'allDoctors',
            'startDate', 'endDate', 'doctorId'
        ));
    }

    public function appointmentStatistics(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $doctorId = $request->input('doctor_id');
        $status = $request->input('status');

        // Get appointments within date range
        $query = Appointment::whereBetween('appointment_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['patient', 'doctor']);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $appointments = $query->orderBy('appointment_datetime', 'desc')->get();

        // Calculate statistics
        $stats = [
            'total_appointments' => $appointments->count(),
            'scheduled' => $appointments->where('status', 'scheduled')->count(),
            'completed' => $appointments->where('status', 'completed')->count(),
            'cancelled' => $appointments->where('status', 'cancelled')->count(),
            'no_show' => $appointments->where('status', 'no_show')->count(),
            'completion_rate' => $appointments->count() > 0 ? ($appointments->where('status', 'completed')->count() / $appointments->count() * 100) : 0,
            'cancellation_rate' => $appointments->count() > 0 ? ($appointments->where('status', 'cancelled')->count() / $appointments->count() * 100) : 0,
            'no_show_rate' => $appointments->count() > 0 ? ($appointments->where('status', 'no_show')->count() / $appointments->count() * 100) : 0,
        ];

        // Doctor-wise appointment breakdown
        $doctorAppointments = $appointments->groupBy('doctor_id')
            ->map(function($doctorAppts) {
                $doctor = $doctorAppts->first()->doctor;
                return [
                    'doctor' => $doctor,
                    'total' => $doctorAppts->count(),
                    'scheduled' => $doctorAppts->where('status', 'scheduled')->count(),
                    'completed' => $doctorAppts->where('status', 'completed')->count(),
                    'cancelled' => $doctorAppts->where('status', 'cancelled')->count(),
                    'no_show' => $doctorAppts->where('status', 'no_show')->count(),
                    'completion_rate' => $doctorAppts->count() > 0 ? ($doctorAppts->where('status', 'completed')->count() / $doctorAppts->count() * 100) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Daily appointment trend
        $dailyTrend = $appointments->groupBy(function($appointment) {
                return \Carbon\Carbon::parse($appointment->appointment_datetime)->format('Y-m-d');
            })
            ->map(function($dayAppts) {
                return [
                    'total' => $dayAppts->count(),
                    'scheduled' => $dayAppts->where('status', 'scheduled')->count(),
                    'completed' => $dayAppts->where('status', 'completed')->count(),
                    'cancelled' => $dayAppts->where('status', 'cancelled')->count(),
                ];
            })
            ->sortKeys();

        // Time slot analysis (hour of day)
        $timeSlotAnalysis = $appointments->groupBy(function($appointment) {
                return \Carbon\Carbon::parse($appointment->appointment_datetime)->format('H');
            })
            ->map(function($hourAppts, $hour) {
                return [
                    'hour' => $hour,
                    'time_label' => \Carbon\Carbon::createFromFormat('H', $hour)->format('h A'),
                    'count' => $hourAppts->count(),
                    'completed' => $hourAppts->where('status', 'completed')->count(),
                ];
            })
            ->sortBy('hour')
            ->values();

        // Day of week analysis
        $dayOfWeekAnalysis = $appointments->groupBy(function($appointment) {
                return \Carbon\Carbon::parse($appointment->appointment_datetime)->dayOfWeek;
            })
            ->map(function($dayAppts, $dayNum) {
                return [
                    'day' => \Carbon\Carbon::create()->dayOfWeek($dayNum)->format('l'),
                    'count' => $dayAppts->count(),
                    'completed' => $dayAppts->where('status', 'completed')->count(),
                ];
            })
            ->sortKeys()
            ->values();

        // Cancellation reasons (if you have a reason field)
        $cancellationReasons = $appointments->where('status', 'cancelled')
            ->groupBy('cancellation_reason')
            ->map(function($reasonAppts, $reason) {
                return [
                    'reason' => $reason ?: 'Not specified',
                    'count' => $reasonAppts->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $doctors = Doctor::orderBy('name')->get();

        return view('admin.reports.appointment-statistics', compact(
            'appointments', 'stats', 'doctorAppointments', 'dailyTrend', 'timeSlotAnalysis',
            'dayOfWeekAnalysis', 'cancellationReasons', 'doctors', 'startDate', 'endDate', 'doctorId', 'status'
        ));
    }

    public function ipdReport(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $wardId = $request->input('ward_id');
        $status = $request->input('status');

        // Get admissions within date range
        $query = Admission::whereBetween('admission_date', [$startDate, $endDate])
            ->with(['patient', 'bed.ward', 'visit.doctor']);

        if ($wardId) {
            $query->whereHas('bed', function($q) use ($wardId) {
                $q->where('ward_id', $wardId);
            });
        }

        if ($status === 'active') {
            $query->whereNull('discharge_date');
        } elseif ($status === 'discharged') {
            $query->whereNotNull('discharge_date');
        }

        $admissions = $query->orderBy('admission_date', 'desc')->get();

        // Calculate statistics
        $activeAdmissions = Admission::whereNull('discharge_date')->count();
        $dischargedInPeriod = $admissions->whereNotNull('discharge_date')->count();

        $stats = [
            'total_admissions' => $admissions->count(),
            'active_admissions' => $activeAdmissions,
            'discharged' => $dischargedInPeriod,
            'avg_length_of_stay' => 0,
            'total_bed_days' => 0,
            'bed_occupancy_rate' => 0,
        ];

        // Calculate average length of stay
        $dischargedAdmissions = $admissions->whereNotNull('discharge_date');
        if ($dischargedAdmissions->count() > 0) {
            $totalDays = $dischargedAdmissions->sum(function($admission) {
                return \Carbon\Carbon::parse($admission->admission_date)
                    ->diffInDays(\Carbon\Carbon::parse($admission->discharge_date));
            });
            $stats['avg_length_of_stay'] = $totalDays / $dischargedAdmissions->count();
        }

        // Calculate total bed days
        $stats['total_bed_days'] = $admissions->sum(function($admission) {
            $endDate = $admission->discharge_date ?? now();
            return \Carbon\Carbon::parse($admission->admission_date)->diffInDays($endDate);
        });

        // Calculate bed occupancy rate
        $totalBeds = Bed::where('status', 'available')->orWhere('status', 'occupied')->count();
        if ($totalBeds > 0) {
            $stats['bed_occupancy_rate'] = ($activeAdmissions / $totalBeds) * 100;
        }

        // Ward-wise statistics
        $wardStats = Ward::with(['beds'])->get()->map(function($ward) use ($startDate, $endDate) {
            $wardAdmissions = Admission::whereHas('bed', function($q) use ($ward) {
                    $q->where('ward_id', $ward->id);
                })
                ->whereBetween('admission_date', [$startDate, $endDate])
                ->get();

            $activeInWard = Admission::whereHas('bed', function($q) use ($ward) {
                    $q->where('ward_id', $ward->id);
                })
                ->whereNull('discharge_date')
                ->count();

            $totalBeds = $ward->beds->count();
            $occupancyRate = $totalBeds > 0 ? ($activeInWard / $totalBeds * 100) : 0;

            return [
                'ward' => $ward,
                'total_beds' => $totalBeds,
                'occupied_beds' => $activeInWard,
                'available_beds' => $totalBeds - $activeInWard,
                'admissions' => $wardAdmissions->count(),
                'occupancy_rate' => $occupancyRate,
            ];
        })->sortByDesc('admissions');

        // Daily admission trend
        $dailyTrend = $admissions->groupBy(function($admission) {
                return \Carbon\Carbon::parse($admission->admission_date)->format('Y-m-d');
            })
            ->map(function($dayAdmissions) {
                return [
                    'admissions' => $dayAdmissions->count(),
                    'discharges' => $dayAdmissions->whereNotNull('discharge_date')->count(),
                ];
            })
            ->sortKeys();

        // Diagnosis-wise admissions (if you have diagnosis data)
        $diagnosisStats = $admissions->filter(function($admission) {
                return $admission->visit && $admission->visit->consultation;
            })
            ->groupBy(function($admission) {
                return $admission->visit->consultation->diagnosis ?? 'Not specified';
            })
            ->map(function($diagnosisAdmissions, $diagnosis) {
                return [
                    'diagnosis' => $diagnosis,
                    'count' => $diagnosisAdmissions->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        // Doctor-wise admissions
        $doctorStats = $admissions->filter(function($admission) {
                return $admission->visit && $admission->visit->doctor;
            })
            ->groupBy('visit.doctor_id')
            ->map(function($doctorAdmissions) {
                $doctor = $doctorAdmissions->first()->visit->doctor;
                return [
                    'doctor' => $doctor,
                    'admissions' => $doctorAdmissions->count(),
                    'active' => $doctorAdmissions->whereNull('discharge_date')->count(),
                    'discharged' => $doctorAdmissions->whereNotNull('discharge_date')->count(),
                ];
            })
            ->sortByDesc('admissions')
            ->values();

        $wards = Ward::orderBy('name')->get();

        return view('admin.reports.ipd-report', compact(
            'admissions', 'stats', 'wardStats', 'dailyTrend', 'diagnosisStats', 'doctorStats',
            'wards', 'startDate', 'endDate', 'wardId', 'status'
        ));
    }

    public function departmentPerformance(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $departmentId = $request->input('department_id');

        // Get departments with their performance data
        $query = Department::with(['doctors']);

        if ($departmentId) {
            $query->where('id', $departmentId);
        }

        $departments = $query->get();

        // Calculate performance metrics for each department
        $performance = $departments->map(function($department) use ($startDate, $endDate) {
            // Get doctors in this department
            $doctorIds = $department->doctors->pluck('id');

            // Get visits
            $visits = Visit::whereIn('doctor_id', $doctorIds)
                ->whereBetween('visit_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();

            // Get appointments
            $appointments = Appointment::whereIn('doctor_id', $doctorIds)
                ->whereBetween('appointment_datetime', [$startDate, $endDate])
                ->get();

            // Get bills
            $bills = Bill::whereHas('visit', function($q) use ($doctorIds, $startDate, $endDate) {
                $q->whereIn('doctor_id', $doctorIds)
                  ->whereBetween('visit_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })->get();

            // Get prescriptions
            $prescriptions = Prescription::whereHas('visit', function($q) use ($doctorIds, $startDate, $endDate) {
                $q->whereIn('doctor_id', $doctorIds)
                  ->whereBetween('visit_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })->get();

            // Get investigation orders
            $investigationOrders = InvestigationOrder::whereIn('doctor_id', $doctorIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            // Calculate metrics
            $totalRevenue = $bills->sum('total_amount');
            $totalCollected = $bills->sum('paid_amount');
            $totalVisits = $visits->count();
            $completedVisits = $visits->where('status', 'completed')->count();

            return [
                'department' => $department,
                'doctors_count' => $department->doctors->count(),
                'total_visits' => $totalVisits,
                'completed_visits' => $completedVisits,
                'completion_rate' => $totalVisits > 0 ? ($completedVisits / $totalVisits * 100) : 0,
                'total_appointments' => $appointments->count(),
                'completed_appointments' => $appointments->where('status', 'completed')->count(),
                'total_prescriptions' => $prescriptions->count(),
                'total_investigations' => $investigationOrders->count(),
                'total_revenue' => $totalRevenue,
                'total_collected' => $totalCollected,
                'unique_patients' => $visits->pluck('patient_id')->unique()->count(),
                'avg_visits_per_doctor' => $department->doctors->count() > 0 ? $totalVisits / $department->doctors->count() : 0,
                'avg_revenue_per_doctor' => $department->doctors->count() > 0 ? $totalRevenue / $department->doctors->count() : 0,
            ];
        })->sortByDesc('total_revenue');

        // Overall statistics
        $stats = [
            'total_departments' => $departments->count(),
            'total_doctors' => $performance->sum('doctors_count'),
            'total_visits' => $performance->sum('total_visits'),
            'total_revenue' => $performance->sum('total_revenue'),
            'total_prescriptions' => $performance->sum('total_prescriptions'),
            'total_investigations' => $performance->sum('total_investigations'),
        ];

        // Revenue comparison
        $revenueComparison = $performance->map(function($perf) {
            return [
                'department' => $perf['department']->name,
                'revenue' => $perf['total_revenue'],
                'collected' => $perf['total_collected'],
            ];
        })->sortByDesc('revenue')->values();

        // Visit comparison
        $visitComparison = $performance->sortByDesc('total_visits')->map(function($perf) {
            return [
                'department' => $perf['department']->name,
                'visits' => $perf['total_visits'],
                'completed' => $perf['completed_visits'],
            ];
        })->values();

        // Efficiency metrics
        $efficiencyMetrics = $performance->map(function($perf) {
            return [
                'department' => $perf['department']->name,
                'avg_visits_per_doctor' => $perf['avg_visits_per_doctor'],
                'avg_revenue_per_doctor' => $perf['avg_revenue_per_doctor'],
                'completion_rate' => $perf['completion_rate'],
            ];
        })->sortByDesc('avg_revenue_per_doctor')->values();

        $allDepartments = Department::orderBy('name')->get();

        return view('admin.reports.department-performance', compact(
            'performance', 'stats', 'revenueComparison', 'visitComparison', 'efficiencyMetrics',
            'allDepartments', 'startDate', 'endDate', 'departmentId'
        ));
    }

    public function patientDemographics(Request $request)
    {
        $startDate = $request->input('start_date', today()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));

        // Get patients registered within date range
        $patients = Patient::whereBetween('created_at', [$startDate, $endDate])->get();
        $allPatients = Patient::all();

        // Calculate statistics
        $stats = [
            'total_patients' => $allPatients->count(),
            'new_patients' => $patients->count(),
            'male_patients' => $allPatients->where('gender', 'male')->count(),
            'female_patients' => $allPatients->where('gender', 'female')->count(),
            'other_gender' => $allPatients->whereNotIn('gender', ['male', 'female'])->count(),
        ];

        // Age distribution
        $ageGroups = [
            '0-10' => ['min' => 0, 'max' => 10, 'count' => 0],
            '11-20' => ['min' => 11, 'max' => 20, 'count' => 0],
            '21-30' => ['min' => 21, 'max' => 30, 'count' => 0],
            '31-40' => ['min' => 31, 'max' => 40, 'count' => 0],
            '41-50' => ['min' => 41, 'max' => 50, 'count' => 0],
            '51-60' => ['min' => 51, 'max' => 60, 'count' => 0],
            '61-70' => ['min' => 61, 'max' => 70, 'count' => 0],
            '71+' => ['min' => 71, 'max' => 999, 'count' => 0],
        ];

        foreach ($allPatients as $patient) {
            if ($patient->date_of_birth) {
                $age = \Carbon\Carbon::parse($patient->date_of_birth)->age;
                foreach ($ageGroups as $group => &$data) {
                    if ($age >= $data['min'] && $age <= $data['max']) {
                        $data['count']++;
                        break;
                    }
                }
            }
        }

        // Gender distribution by age group
        $genderAgeDistribution = [];
        foreach ($ageGroups as $group => $data) {
            $genderAgeDistribution[$group] = [
                'male' => 0,
                'female' => 0,
                'other' => 0,
            ];
        }

        foreach ($allPatients as $patient) {
            if ($patient->date_of_birth) {
                $age = \Carbon\Carbon::parse($patient->date_of_birth)->age;
                foreach ($ageGroups as $group => $data) {
                    if ($age >= $data['min'] && $age <= $data['max']) {
                        $gender = $patient->gender === 'male' ? 'male' : ($patient->gender === 'female' ? 'female' : 'other');
                        $genderAgeDistribution[$group][$gender]++;
                        break;
                    }
                }
            }
        }

        // Blood group distribution
        $bloodGroups = $allPatients->groupBy('blood_group')
            ->map(function($group, $bloodGroup) {
                return [
                    'blood_group' => $bloodGroup ?: 'Not specified',
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        // Monthly registration trend
        $monthlyTrend = $patients->groupBy(function($patient) {
                return \Carbon\Carbon::parse($patient->created_at)->format('Y-m');
            })
            ->map(function($monthPatients) {
                return [
                    'count' => $monthPatients->count(),
                    'male' => $monthPatients->where('gender', 'male')->count(),
                    'female' => $monthPatients->where('gender', 'female')->count(),
                ];
            })
            ->sortKeys();

        // Top areas/cities (if you have address field)
        $topAreas = $allPatients->filter(function($patient) {
                return !empty($patient->address);
            })
            ->groupBy('address')
            ->map(function($areaPatients, $address) {
                return [
                    'area' => $address,
                    'count' => $areaPatients->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        // Patient visit frequency
        $visitFrequency = $allPatients->map(function($patient) {
            $visitCount = Visit::where('patient_id', $patient->id)->count();
            return [
                'patient' => $patient,
                'visit_count' => $visitCount,
            ];
        })->sortByDesc('visit_count')->take(10)->values();

        return view('admin.reports.patient-demographics', compact(
            'stats', 'ageGroups', 'genderAgeDistribution', 'bloodGroups', 'monthlyTrend',
            'topAreas', 'visitFrequency', 'startDate', 'endDate'
        ));
    }
}
