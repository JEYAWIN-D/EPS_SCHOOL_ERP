<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\AdmissionKitConfig;
use App\Models\AdmissionInventoryIssue;
use App\Models\Classes;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseController extends Controller
{
    // ── Main Dashboard & Inventory Tabs ──────────────────────

    public function index(Request $request)
    {
        $activeTab  = $request->get('tab', 'academic'); // 'academic' (default) or 'general'
        $targetType = ($activeTab === 'general') ? 'general_operations' : 'academic';

        // Categories belonging strictly to the currently selected inventory tab
        $categories = InventoryCategory::active()
            ->whereHas('items', fn($q) => $q->where('inventory_type', $targetType))
            ->orderBy('name')
            ->get();

        $classes = Classes::active()->orderBy('sort_order')->orderBy('numeric_value')->get();

        $query = InventoryItem::with('category')
            ->when(!$request->show_inactive, fn($q) => $q->where('is_active', true));

        if ($activeTab === 'general') {
            $query->where('inventory_type', 'general_operations');
        } else {
            $query->where('inventory_type', 'academic');
        }

        // Filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $v = $request->search;
            $query->where(fn($q2) =>
                $q2->where('name', 'like', "%$v%")
                   ->orWhere('item_code', 'like', "%$v%")
                   ->orWhere('description', 'like', "%$v%")
            );
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->lowStock();
            } elseif ($request->stock_status === 'out') {
                $query->outOfStock();
            } elseif ($request->stock_status === 'in') {
                $query->where('current_stock', '>', 0);
            }
        }

        $items = $query->orderBy('name')->paginate(25)->withQueryString();

        // Dashboard Summary Metrics strictly scoped to active tab's inventory type
        $tabItemQuery     = InventoryItem::where('is_active', true)->where('inventory_type', $targetType);
        $totalItems       = (clone $tabItemQuery)->count();
        $totalStockQty    = (clone $tabItemQuery)->sum('current_stock');
        $lowStockCount    = (clone $tabItemQuery)->lowStock()->count();
        $outOfStockCount  = (clone $tabItemQuery)->outOfStock()->count();
        $academicValue    = InventoryItem::where('is_active', true)->academic()->sum(DB::raw('current_stock * GREATEST(purchase_cost, unit_price)'));
        $generalOpsValue  = InventoryItem::where('is_active', true)->generalOperations()->sum(DB::raw('current_stock * GREATEST(purchase_cost, unit_price)'));

        $stats = compact(
            'totalItems',
            'totalStockQty',
            'lowStockCount',
            'outOfStockCount',
            'academicValue',
            'generalOpsValue'
        );

        return view('warehouse.index', compact('items', 'categories', 'classes', 'activeTab', 'stats'));
    }

    // ── Item Master Management ───────────────────────────────

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'category_id'       => 'required|exists:inventory_categories,id',
            'inventory_type'    => 'required|in:academic,general_operations',
            'academic_category' => 'nullable|string|max:50',
            'name'              => 'required|string|max:255',
            'item_code'         => 'required|string|max:50|unique:inventory_items,item_code',
            'unit'              => 'required|string|max:30',
            'purchase_cost'     => 'required|numeric|min:0',
            'student_price'      => 'nullable|numeric|min:0',
            'reorder_level'     => 'required|integer|min:0',
            'current_stock'     => 'required|integer|min:0',
            'location'          => 'nullable|string|max:100',
            'supplier_name'     => 'nullable|string|max:150',
            'description'       => 'nullable|string',
            'invoice_file'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $validated['unit_price']   = $validated['purchase_cost'];
        $validated['student_price'] = $validated['student_price'] ?? 0.00;

        $invoicePath = null;
        if ($request->hasFile('invoice_file')) {
            $invoicePath = $request->file('invoice_file')->store('inventory-invoices', 'public');
        }

        DB::transaction(function () use ($validated, $invoicePath) {
            $itemData = $validated;
            unset($itemData['invoice_file']);
            $item = InventoryItem::create($itemData);

            // Record initial stock transaction if stock > 0 or invoice attached
            if ($item->current_stock > 0 || $invoicePath) {
                InventoryTransaction::create([
                    'transaction_code' => InventoryTransaction::generateTransactionCode('INIT'),
                    'item_id'          => $item->id,
                    'transaction_type' => 'stock_in',
                    'quantity'         => $item->current_stock,
                    'previous_stock'   => 0,
                    'quantity_changed' => $item->current_stock,
                    'new_stock'        => $item->current_stock,
                    'unit_cost'        => $item->purchase_cost,
                    'total_cost'       => $item->current_stock * $item->purchase_cost,
                    'reference_type'   => 'initial_stock',
                    'supplier_name'    => $item->supplier_name,
                    'invoice_path'     => $invoicePath,
                    'notes'            => $invoicePath ? 'Initial opening stock with purchase invoice document attached' : 'Initial opening stock upon item creation',
                    'performed_by'     => Auth::id(),
                ]);
            }
        });

        return back()->with('success', "Inventory item '{$request->name}' created successfully.");
    }

    public function updateItem(Request $request, int $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'category_id'       => 'required|exists:inventory_categories,id',
            'inventory_type'    => 'required|in:academic,general_operations',
            'academic_category' => 'nullable|string|max:50',
            'name'              => 'required|string|max:255',
            'item_code'         => "required|string|max:50|unique:inventory_items,item_code,$id",
            'unit'              => 'required|string|max:30',
            'purchase_cost'     => 'required|numeric|min:0',
            'student_price'      => 'nullable|numeric|min:0',
            'reorder_level'     => 'required|integer|min:0',
            'location'          => 'nullable|string|max:100',
            'supplier_name'     => 'nullable|string|max:150',
            'description'       => 'nullable|string',
            'is_active'         => 'boolean',
        ]);

        $validated['unit_price']   = $validated['purchase_cost'];
        $validated['student_price'] = $validated['student_price'] ?? 0.00;

        $item->update($validated);

        return back()->with('success', "Item '{$item->name}' updated successfully.");
    }

    // ── Stock In / Receiving Stock ───────────────────────────

    public function stockIn(Request $request)
    {
        $validated = $request->validate([
            'item_id'           => 'required|exists:inventory_items,id',
            'quantity_received' => 'required|integer|min:1',
            'unit_cost'         => 'required|numeric|min:0',
            'student_price'     => 'nullable|numeric|min:0',
            'supplier_name'     => 'nullable|string|max:150',
            'invoice_number'    => 'nullable|string|max:100',
            'invoice_date'      => 'nullable|date',
            'notes'             => 'nullable|string',
            'invoice_file'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $item = InventoryItem::findOrFail($validated['item_id']);

        $invoicePath = null;
        if ($request->hasFile('invoice_file')) {
            $invoicePath = $request->file('invoice_file')->store('inventory-invoices', 'public');
        }

        DB::transaction(function () use ($item, $validated, $invoicePath) {
            $prevStock  = $item->current_stock;
            $qty        = (int)$validated['quantity_received'];
            $newStock   = $prevStock + $qty;
            $unitCost   = (float)$validated['unit_cost'];
            $totalCost  = $qty * $unitCost;

            // Update item current stock, purchase cost & student price
            $itemUpdate = [
                'current_stock' => $newStock,
                'purchase_cost' => $unitCost,
                'unit_price'    => $unitCost,
            ];

            if (array_key_exists('student_price', $validated) && $validated['student_price'] !== null && $validated['student_price'] !== '') {
                $itemUpdate['student_price'] = (float)$validated['student_price'];
            }

            $item->update($itemUpdate);

            // Permanent audit transaction record
            InventoryTransaction::create([
                'transaction_code' => InventoryTransaction::generateTransactionCode('STKIN'),
                'item_id'          => $item->id,
                'transaction_type' => 'stock_in',
                'quantity'         => $qty,
                'previous_stock'   => $prevStock,
                'quantity_changed' => $qty,
                'new_stock'        => $newStock,
                'unit_cost'        => $unitCost,
                'total_cost'       => $totalCost,
                'reference_type'   => 'vendor_invoice',
                'invoice_number'   => $validated['invoice_number'] ?? null,
                'invoice_date'     => $validated['invoice_date'] ?? null,
                'supplier_name'    => $validated['supplier_name'] ?? $item->supplier_name ?? null,
                'invoice_path'     => $invoicePath,
                'notes'            => $validated['notes'] ?? null,
                'performed_by'     => Auth::id(),
            ]);
        });

        return back()->with('success', "Stock In recorded successfully! {$validated['quantity_received']} {$item->unit} added to '{$item->name}'. Current stock: " . ($item->current_stock));
    }

    // ── Manual Stock Out (Issue Stock) ───────────────────────

    public function stockOut(Request $request)
    {
        $validated = $request->validate([
            'item_id'        => 'required|exists:inventory_items,id',
            'quantity_issue' => 'required|integer|min:1',
            'issued_to'      => 'nullable|string|max:150',
            'purpose'        => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $item = InventoryItem::findOrFail($validated['item_id']);
        $qty  = (int)$validated['quantity_issue'];

        // Strict Stock Check: Do NOT allow stock to become negative
        if ($item->current_stock < $qty) {
            return back()->withInput()->with('error', "Insufficient stock. Available quantity: {$item->current_stock} {$item->unit}. Requested: {$qty}.");
        }

        DB::transaction(function () use ($item, $qty, $validated) {
            $prevStock = $item->current_stock;
            $newStock  = $prevStock - $qty;
            $unitCost  = $item->effective_purchase_cost;

            $item->update(['current_stock' => $newStock]);

            $notesText = trim(($validated['purpose'] ?? '') . ' ' . ($validated['issued_to'] ? "Issued To: " . $validated['issued_to'] : '') . ' ' . ($validated['notes'] ?? ''));

            InventoryTransaction::create([
                'transaction_code' => InventoryTransaction::generateTransactionCode('STKOUT'),
                'item_id'          => $item->id,
                'transaction_type' => 'manual_stock_out',
                'quantity'         => $qty,
                'previous_stock'   => $prevStock,
                'quantity_changed' => -$qty,
                'new_stock'        => $newStock,
                'unit_cost'        => $unitCost,
                'total_cost'       => $qty * $unitCost,
                'reference_type'   => 'manual_issue',
                'notes'            => $notesText,
                'performed_by'     => Auth::id(),
            ]);
        });

        return back()->with('success', "Stock Out of {$qty} {$item->unit} recorded for '{$item->name}'. Remaining stock: {$item->current_stock}.");
    }

    // ── Stock Count Adjustment ───────────────────────────────

    public function adjustment(Request $request)
    {
        $validated = $request->validate([
            'item_id'    => 'required|exists:inventory_items,id',
            'actual_qty' => 'required|integer|min:0',
            'reason'     => 'required|string|max:255',
            'notes'      => 'nullable|string',
        ]);

        $item      = InventoryItem::findOrFail($validated['item_id']);
        $actualQty = (int)$validated['actual_qty'];
        $prevStock = $item->current_stock;
        $diff      = $actualQty - $prevStock;

        if ($diff === 0) {
            return back()->with('info', 'No stock count change detected.');
        }

        DB::transaction(function () use ($item, $actualQty, $prevStock, $diff, $validated) {
            $item->update(['current_stock' => $actualQty]);
            $txnType = $diff > 0 ? 'adjustment_increase' : 'adjustment_decrease';
            $unitCost = $item->effective_purchase_cost;

            InventoryTransaction::create([
                'transaction_code' => InventoryTransaction::generateTransactionCode('ADJ'),
                'item_id'          => $item->id,
                'transaction_type' => $txnType,
                'quantity'         => abs($diff),
                'previous_stock'   => $prevStock,
                'quantity_changed' => $diff,
                'new_stock'        => $actualQty,
                'unit_cost'        => $unitCost,
                'total_cost'       => abs($diff) * $unitCost,
                'reference_type'   => 'stock_adjustment',
                'notes'            => "Adjustment Reason: {$validated['reason']}. " . ($validated['notes'] ?? ''),
                'performed_by'     => Auth::id(),
            ]);
        });

        return back()->with('success', "Stock count for '{$item->name}' adjusted from {$prevStock} to {$actualQty}.");
    }

    // ── Item Detail Page ─────────────────────────────────────

    public function showItem(int $id)
    {
        $item = InventoryItem::with(['category', 'transactions.performedBy'])->findOrFail($id);

        $stockInCount    = $item->transactions->where('transaction_type', 'stock_in')->sum('quantity');
        $stockOutCount   = $item->transactions->whereIn('transaction_type', ['manual_stock_out', 'admission_issue'])->sum('quantity');
        $admissionIssues = $item->transactions->where('transaction_type', 'admission_issue')->sum('quantity');
        $manualIssues    = $item->transactions->where('transaction_type', 'manual_stock_out')->sum('quantity');
        $returnsCount    = $item->transactions->where('transaction_type', 'return_reversal')->sum('quantity');

        $summary = compact('stockInCount', 'stockOutCount', 'admissionIssues', 'manualIssues', 'returnsCount');

        return view('warehouse.show', compact('item', 'summary'));
    }

    // ── Transaction History Page ─────────────────────────────

    public function transactions(Request $request)
    {
        $categories = InventoryCategory::active()->orderBy('name')->get();
        $items      = InventoryItem::orderBy('name')->get();

        $query = InventoryTransaction::with(['item.category', 'performedBy'])
            ->when($request->item_id, fn($q, $v) => $q->where('item_id', $v))
            ->when($request->type, fn($q, $v) => $q->where('transaction_type', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->search, fn($q, $v) => $q->where(function ($q2) use ($v) {
                $q2->where('transaction_code', 'like', "%$v%")
                   ->orWhere('invoice_number', 'like', "%$v%")
                   ->orWhere('supplier_name', 'like', "%$v%")
                   ->orWhere('notes', 'like', "%$v%");
            }))
            ->latest();

        $transactions = $query->paginate(30)->withQueryString();

        return view('warehouse.transactions', compact('transactions', 'categories', 'items'));
    }

    // ── Standard-Wise Admission Kit Configuration Editor ──────

    public function admissionKitConfig(Request $request)
    {
        $classes       = Classes::active()->orderBy('sort_order')->orderBy('numeric_value')->get();
        $academicYear  = AcademicYear::current();
        $academicItems = InventoryItem::active()->academic()->orderBy('name')->get();

        $selectedClassId = $request->get('class_id', $classes->first()?->id);
        $selectedClass   = $classes->firstWhere('id', $selectedClassId);

        $currentKits = AdmissionKitConfig::with('item')
            ->where('class_id', $selectedClassId)
            ->get()
            ->keyBy('item_id');

        return view('warehouse.admission-kit-config', compact(
            'classes', 'academicYear', 'academicItems', 'selectedClassId', 'selectedClass', 'currentKits'
        ));
    }

    public function saveKitConfig(Request $request)
    {
        $request->validate([
            'class_id'                       => 'required|exists:classes,id',
            'items'                          => 'nullable|array',
            'items.*.enabled'               => 'nullable',
            'items.*.default_quantity'      => 'nullable|integer|min:0',
            'items.*.student_charge'        => 'nullable|numeric|min:0',
            'items.*.allow_additional_qty' => 'nullable',
        ]);

        $classId      = $request->class_id;
        $academicYear = AcademicYear::current();

        DB::transaction(function () use ($classId, $academicYear, $request) {
            // Delete existing configs for class
            AdmissionKitConfig::where('class_id', $classId)->delete();

            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $itemId => $row) {
                    if (empty($row['enabled'])) {
                        continue;
                    }
                    $item = InventoryItem::find($itemId);
                    // Strictly enforce Academic Inventory items ONLY
                    if (!$item || !$item->is_active || $item->inventory_type !== 'academic') {
                        continue;
                    }

                    $qty = (int)($row['default_quantity'] ?? 1);
                    AdmissionKitConfig::create([
                        'class_id'             => $classId,
                        'item_id'              => $item->id,
                        'academic_year_id'     => $academicYear?->id,
                        'default_quantity'     => $qty > 0 ? $qty : 1,
                        'unit'                 => $item->unit ?? 'Piece',
                        'is_default_included'  => true,
                        'allow_additional_qty' => !empty($row['allow_additional_qty']),
                        'student_charge'       => (float)($row['student_charge'] ?? $item->student_price ?? 0),
                    ]);
                }
            }
        });

        return back()->with('success', 'Admission Kit Configuration updated for Class ' . Classes::find($classId)?->name);
    }

    // ── API Endpoint for Admission Form Kit Calculation ──────

    public function getKitForClass(int $classId)
    {
        $configs = AdmissionKitConfig::with('item')
            ->where('class_id', $classId)
            ->get();

        $itemsData = $configs->map(function ($cfg) {
            $item = $cfg->item;
            return [
                'config_id'            => $cfg->id,
                'item_id'              => $cfg->item_id,
                'name'                 => $item?->name ?? 'Unknown Item',
                'academic_category'    => $item?->academic_category ?? 'other_academic',
                'default_quantity'     => $cfg->default_quantity,
                'unit'                 => $cfg->unit,
                'available_stock'      => $item?->current_stock ?? 0,
                'is_default_included'  => $cfg->is_default_included,
                'allow_additional_qty' => $cfg->allow_additional_qty,
                'student_charge'       => (float)($cfg->student_charge > 0 ? $cfg->student_charge : ($item?->student_price ?? 0)),
            ];
        });

        return response()->json([
            'class_id' => $classId,
            'items'    => $itemsData,
        ]);
    }

    // ── Reports & CSV Export ─────────────────────────────────

    public function reports(Request $request)
    {
        $reportType = $request->get('type', 'stock'); // stock, stock_in, stock_out, low_stock, admission_issues
        $categories = InventoryCategory::active()->orderBy('name')->get();

        $data = [];
        if ($reportType === 'low_stock') {
            $data = InventoryItem::with('category')->lowStock()->orderBy('name')->get();
        } elseif ($reportType === 'stock_in') {
            $data = InventoryTransaction::with(['item.category', 'performedBy'])
                ->where('transaction_type', 'stock_in')
                ->latest()
                ->take(100)
                ->get();
        } elseif ($reportType === 'stock_out') {
            $data = InventoryTransaction::with(['item.category', 'performedBy'])
                ->whereIn('transaction_type', ['manual_stock_out', 'admission_issue'])
                ->latest()
                ->take(100)
                ->get();
        } elseif ($reportType === 'admission_issues') {
            $data = AdmissionInventoryIssue::with(['enquiry', 'student', 'item'])
                ->latest()
                ->paginate(30);
        } else { // Current Stock Report
            $data = InventoryItem::with('category')
                ->when($request->category_id, fn($q, $v) => $q->where('category_id', $v))
                ->orderBy('name')
                ->get();
        }

        if ($request->has('export') && $request->export === 'csv') {
            return $this->exportCsvReport($reportType, $data);
        }

        return view('warehouse.reports', compact('reportType', 'categories', 'data'));
    }

    private function exportCsvReport(string $type, $data): StreamedResponse
    {
        $filename = "warehouse_{$type}_report_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($type, $data) {
            $file = fopen('php://output', 'w');

            if ($type === 'admission_issues') {
                fputcsv($file, ['ID', 'Date', 'Student / Enquiry', 'Item Code', 'Item Name', 'Default Qty', 'Additional Qty', 'Total Qty', 'Add. Charge (INR)']);
                foreach ($data as $row) {
                    $targetName = $row->student ? ($row->student->first_name . ' ' . $row->student->last_name) : ($row->enquiry?->student_name ?? 'N/A');
                    fputcsv($file, [
                        $row->id,
                        $row->created_at->format('Y-m-d H:i'),
                        $targetName,
                        $row->item?->item_code ?? '',
                        $row->item?->name ?? '',
                        $row->default_quantity,
                        $row->additional_quantity,
                        $row->total_quantity,
                        $row->additional_charge,
                    ]);
                }
            } elseif (in_array($type, ['stock_in', 'stock_out'])) {
                fputcsv($file, ['Txn Code', 'Date/Time', 'Type', 'Item Code', 'Item Name', 'Qty', 'Prev Stock', 'New Stock', 'Unit Cost (INR)', 'Total Cost (INR)', 'Invoice/Supplier', 'Performed By']);
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->transaction_code,
                        $row->created_at->format('Y-m-d H:i'),
                        ucwords(str_replace('_', ' ', $row->transaction_type)),
                        $row->item?->item_code ?? '',
                        $row->item?->name ?? '',
                        $row->quantity,
                        $row->previous_stock,
                        $row->new_stock,
                        $row->unit_cost,
                        $row->total_cost,
                        $row->supplier_name ?: $row->invoice_number ?: '',
                        $row->performedBy?->name ?? 'System',
                    ]);
                }
            } else { // Current Stock / Low Stock
                fputcsv($file, ['Item Code', 'Item Name', 'Type', 'Category', 'Unit', 'Current Stock', 'Min Stock Level', 'Purchase Cost (INR)', 'Student Charge (INR)', 'Total Stock Value (INR)', 'Status']);
                foreach ($data as $row) {
                    $status = $row->isOutOfStock() ? 'Out of Stock' : ($row->isLowStock() ? 'Low Stock' : 'In Stock');
                    fputcsv($file, [
                        $row->item_code,
                        $row->name,
                        ucwords(str_replace('_', ' ', $row->inventory_type)),
                        $row->category?->name ?? '',
                        $row->unit,
                        $row->current_stock,
                        $row->reorder_level,
                        $row->effective_purchase_cost,
                        $row->effective_student_price,
                        $row->current_stock * $row->effective_purchase_cost,
                        $status,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Invoice Download Route ───────────────────────────────

    public function downloadInvoice(int $id)
    {
        $transaction = InventoryTransaction::findOrFail($id);

        if (!$transaction->invoice_path || !Storage::disk('public')->exists($transaction->invoice_path)) {
            return back()->with('error', 'No invoice file document attached to this transaction.');
        }

        return Storage::disk('public')->download($transaction->invoice_path, 'Invoice_' . ($transaction->invoice_number ?: $transaction->transaction_code) . '.' . pathinfo($transaction->invoice_path, PATHINFO_EXTENSION));
    }
}
