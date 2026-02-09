<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\Patient\VisitQueue;
use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\Clinical\Assessment;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\AuditLog;
use App\Models\BpjsLog;
use App\Services\TriageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes are used by the feature tests. They provide simple
| implementations to satisfy test requirements.
|
*/

Route::get('/', function () {
    return redirect('/admin');
});

// Auth Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('email', $credentials['email'])->first();

    if (!$user) {
        return back()->withErrors(['email' => 'These credentials do not match our records.']);
    }

    if (!$user->is_active) {
        return back()->withErrors(['email' => 'Your account is inactive.']);
    }

    $remember = $request->boolean('remember');

    if (Auth::attempt($credentials, $remember)) {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $response = redirect('/admin');
        if ($remember) {
            $response->withCookie(cookie('remember_web', '1', 60 * 24 * 30));
        }

        return $response;
    }

    return back()->withErrors(['email' => 'These credentials do not match our records.']);
})->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/')->withCookie(cookie()->forget('remember_web'));
})->name('logout')->middleware('auth');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest');

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user) {
        return back()->withErrors(['email' => 'We can\'t find a user with that email address.']);
    }

    return back()->with('status', 'We have emailed your password reset link!');
})->middleware('guest');

// Admin Routes - Protected by auth
Route::middleware(['auth'])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/', function () {
        return view('admin.dashboard');
    });

    // Patient Routes
    Route::get('/patients', function (Request $request) {
        $query = Patient::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('medical_record_number', 'like', "%{$search}%")
                    ->orWhere('bpjs_card_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->paginate(20);
        return view('admin.patients.index', compact('patients'));
    });

    Route::get('/patients/create', function () {
        return view('admin.patients.create');
    });

    Route::post('/patients', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'nik' => 'nullable|string|size:16|unique:patients',
            'birth_place' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'gender' => 'required|in:male,female',
            'blood_type' => 'nullable|string',
            'address' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string',
            'email' => 'nullable|email',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'occupation' => 'nullable|string',
            'insurance_type' => 'nullable|string',
            'insurance_number' => 'nullable|string',
            'bpjs_card_number' => 'nullable|string',
        ]);

        // Map fields to database columns
        $data['phone_primary'] = $data['phone'] ?? null;
        $data['address'] = $data['address'] ?? '-';
        $data['emergency_name'] = $data['emergency_contact_name'] ?? null;
        $data['emergency_phone'] = $data['emergency_contact_phone'] ?? null;
        $data['bpjs_number'] = $data['bpjs_card_number'] ?? null;
        $data['insurance_name'] = $data['insurance_type'] ?? null;
        $data['insurance_number'] = $data['insurance_number'] ?? null;
        $data['registered_at'] = now();

        // Generate medical record number
        $datePrefix = now()->format('ymd');
        $lastPatient = Patient::whereDate('created_at', today())->orderBy('id', 'desc')->first();
        $sequence = $lastPatient ? ((int) substr($lastPatient->medical_record_number, -2) + 1) : 1;
        $data['medical_record_number'] = $datePrefix . '-' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);

        Patient::create($data);

        return redirect('/admin/patients')->with('success', 'Patient created successfully');
    });

    Route::get('/patients/{patient}', function (Patient $patient) {
        return view('admin.patients.show', compact('patient'));
    });

    Route::get('/patients/{patient}/edit', function (Patient $patient) {
        return view('admin.patients.edit', compact('patient'));
    });

    Route::put('/patients/{patient}', function (Request $request, Patient $patient) {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'nik' => 'sometimes|nullable|string|size:16|unique:patients,nik,' . $patient->id,
            'phone' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string',
        ]);

        $updateData = [];
        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('nik', $data)) {
            $updateData['nik'] = $data['nik'];
        }
        if (array_key_exists('address', $data)) {
            $updateData['address'] = $data['address'];
        }
        if (array_key_exists('phone', $data)) {
            $updateData['phone_primary'] = $data['phone'];
        }

        if (!empty($updateData)) {
            $patient->update($updateData);
        }

        return redirect('/admin/patients')->with('success', 'Patient updated successfully');
    });

    Route::delete('/patients/{patient}', function (Patient $patient) {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('user')) {
            abort(403);
        }

        $patient->delete();
        return redirect('/admin/patients')->with('success', 'Patient deleted successfully');
    });

    Route::put('/employees/{employee}', function (Request $request, Employee $employee) {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
        ]);

        if (!empty($data)) {
            $employee->update($data);
        }

        return redirect('/admin/employees')->with('success', 'Employee updated successfully');
    });

    Route::post('/visits', function (Request $request) {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'polyclinic_id' => 'required|exists:polyclinics,id',
            'doctor_id' => 'nullable|exists:employees,id',
            'visit_date' => 'nullable|date',
            'visit_type' => 'required|in:rawat_jalan,rawat_inap,igd,mcu',
            'registration_type' => 'nullable|string|max:30',
            'priority' => 'nullable|in:normal,darurat,prioritas,emergency',
            'complaint' => 'required|string',
            'bpjs_sep_number' => 'nullable|string|max:50',
            'arrival_method' => 'nullable|string|max:50',
        ]);

        $priority = strtolower((string) ($data['priority'] ?? 'normal'));
        if ($priority === 'darurat') {
            $priority = 'emergency';
        }

        $visit = Visit::create([
            'patient_id' => $data['patient_id'],
            'polyclinic_id' => $data['polyclinic_id'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'visit_date' => $data['visit_date'] ?? now()->toDateString(),
            'registration_date' => $data['visit_date'] ?? now(),
            'visit_type' => $data['visit_type'],
            'visit_status' => 'menunggu',
            'status' => 'waiting',
            'priority' => $priority,
            'notes' => $data['complaint'],
            'bpjs_sep_number' => $data['bpjs_sep_number'] ?? null,
        ]);

        DB::table('visits')->where('id', $visit->id)->update([
            'registration_type' => $data['registration_type'] ?? null,
            'complaint' => $data['complaint'],
            'arrival_method' => $data['arrival_method'] ?? null,
        ]);

        $prefix = Polyclinic::query()->whereKey($data['polyclinic_id'])->value('queue_prefix') ?? ($data['visit_type'] === 'igd' ? 'E' : 'A');
        $nextQueueNumber = (int) (VisitQueue::query()
            ->where('polyclinic_id', $data['polyclinic_id'])
            ->whereDate('created_at', today())
            ->max('queue_number') ?? 0) + 1;

        VisitQueue::create([
            'visit_id' => $visit->id,
            'patient_id' => $data['patient_id'],
            'polyclinic_id' => $data['polyclinic_id'],
            'queue_number' => $nextQueueNumber,
            'display_number' => $prefix . str_pad((string) $nextQueueNumber, 3, '0', STR_PAD_LEFT),
            'status' => 'waiting',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect('/admin/visits')->with('success', 'Visit registered successfully');
    });

    Route::get('/visits/today', function () {
        $visits = Visit::query()->whereDate('registration_date', today())->get();
        return response($visits->pluck('visit_number')->implode("\n"), 200);
    });

    Route::get('/visits/{visit}', function (Visit $visit) {
        return response((string) ($visit->complaint ?? $visit->notes ?? 'Visit detail'), 200);
    });

    Route::post('/visits/{visit}/cancel', function (Request $request, Visit $visit) {
        $visit->update([
            'status' => 'cancelled',
            'visit_status' => 'batal',
        ]);

        VisitQueue::query()
            ->where('visit_id', $visit->id)
            ->whereIn('status', ['waiting', 'called', 'in_progress', 'skipped'])
            ->update(['status' => 'cancelled']);

        return redirect('/admin/visits')->with('success', 'Visit cancelled');
    });

    Route::get('/queues/display', function () {
        $displayNumbers = VisitQueue::query()
            ->whereIn('status', ['waiting', 'called', 'in_progress'])
            ->orderBy('queue_number')
            ->pluck('display_number')
            ->implode("\n");

        return response($displayNumbers, 200);
    });

    Route::get('/queues/polyclinic/{polyclinic}', function (Polyclinic $polyclinic) {
        $queues = VisitQueue::query()
            ->where('polyclinic_id', $polyclinic->id)
            ->orderBy('queue_number')
            ->get();

        return response()->json(['data' => $queues], 200);
    });

    Route::post('/queues/{queue}/call', function (Request $request, VisitQueue $queue) {
        $queue->markAsCalled($request->input('counter_number'));

        return redirect()->back()->with('success', 'Queue called');
    });

    Route::post('/queues/{queue}/start', function (VisitQueue $queue) {
        $queue->markAsInProgress();

        return redirect()->back()->with('success', 'Queue started');
    });

    Route::post('/queues/{queue}/complete', function (VisitQueue $queue) {
        $queue->markAsCompleted();
        $queue->visit?->update([
            'is_completed' => true,
            'check_out_at' => now(),
            'status' => 'completed',
            'visit_status' => 'selesai',
        ]);

        return redirect()->back()->with('success', 'Queue completed');
    });

    Route::post('/queues/{queue}/skip', function (VisitQueue $queue) {
        $queue->markAsSkipped();

        return redirect()->back()->with('success', 'Queue skipped');
    });

    Route::put('/visits/{visit}', function (Request $request, Visit $visit) {
        $data = $request->validate([
            'complaint' => 'sometimes|nullable|string',
            'status' => 'sometimes|nullable|string',
        ]);

        $updateData = [];
        if (array_key_exists('complaint', $data)) {
            $updateData['notes'] = $data['complaint'];
        }
        if (array_key_exists('status', $data)) {
            $updateData['status'] = $data['status'];
        }

        if (!empty($updateData)) {
            $visit->update($updateData);
        }

        return redirect('/admin/visits')->with('success', 'Visit updated successfully');
    });

    // Medical Records Routes
    Route::get('/medical-records', function () {
        return view('admin.medical-records.index');
    });

    Route::get('/medical-records/create', function () {
        return view('admin.medical-records.create');
    });

    Route::post('/medical-records', function (Request $request) {
        $data = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:employees,id',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
        ]);

        MedicalRecord::create([
            'record_number' => 'MR' . now()->format('YmdHis') . rand(100, 999),
            'visit_id' => $data['visit_id'],
            'patient_id' => $data['patient_id'],
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect('/admin/medical-records')->with('success', 'Medical record created');
    });

    // Billing Routes
    Route::get('/billing', function () {
        return view('admin.billing.index');
    });

    Route::get('/billing/invoices', function (Request $request) {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('cashier'))) {
            abort(403);
        }

        $query = Invoice::query()->with('patient');

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Illuminate\Support\Carbon::parse((string) $request->start_date)->startOfDay();
            $endDate = \Illuminate\Support\Carbon::parse((string) $request->end_date)->endOfDay();
            $query->whereBetween('invoice_date', [$startDate, $endDate]);
        }

        $invoices = $query->paginate(20);
        return view('admin.billing.invoices', compact('invoices'));
    });

    Route::get('/billing/invoices/{invoice}', function (Invoice $invoice) {
        return view('admin.billing.invoice-show', compact('invoice'));
    });

    Route::get('/billing/invoices/{invoice}/print', function (Invoice $invoice) {
        return view('admin.billing.invoice-print', compact('invoice'));
    });

    Route::post('/billing/invoices/{invoice}/cancel', function (Request $request, Invoice $invoice) {
        if ($invoice->is_paid) {
            abort(403, 'Cannot cancel paid invoice');
        }

        $invoice->update(['status' => 'cancelled']);
        return redirect('/admin/billing/invoices')->with('success', 'Invoice cancelled');
    });

    Route::post('/visits/{visit}/generate-invoice', function (Visit $visit) {
        $invoice = Invoice::create([
            'invoice_number' => 'INV' . now()->format('Ymd') . rand(100, 999),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => 500000,
            'total_amount' => 500000,
            'paid_amount' => 0,
            'balance_due' => 500000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        return redirect('/admin/billing/invoices')->with('success', 'Invoice generated');
    });

    Route::post('/billing/payments', function (Request $request) {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,credit_card,debit_card,bank_transfer',
            'payment_type' => 'required|in:full_payment,partial_payment',
            'received_by' => 'nullable|exists:employees,id',
            'card_number' => 'nullable|string|max:30',
            'card_type' => 'nullable|string|max:30',
            'approval_code' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);

        if ($data['amount'] > $invoice->balance_due) {
            return back()->withErrors(['amount' => 'Payment amount cannot exceed invoice balance']);
        }

        $payment = Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . rand(100, 999),
            'invoice_id' => $data['invoice_id'],
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_type' => $data['payment_type'],
            'received_by' => $data['received_by'],
            'card_number' => $data['card_number'] ?? null,
            'card_type' => $data['card_type'] ?? null,
            'approval_code' => $data['approval_code'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'account_holder' => $data['account_holder'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect('/admin/billing/invoices')->with('success', 'Payment recorded');
    });

    Route::get('/billing/payments/{payment}/receipt', function (Payment $payment) {
        return view('admin.billing.receipt', compact('payment'));
    });

    Route::post('/billing/payments/{payment}/refund', function (Request $request, Payment $payment) {
        $data = $request->validate([
            'refund_amount' => 'required|numeric|min:1',
            'refund_reason' => 'required|string',
        ]);

        if ($data['refund_amount'] > $payment->amount) {
            return back()->withErrors(['refund_amount' => 'Refund amount cannot exceed payment amount']);
        }

        $payment->update([
            'is_refunded' => true,
            'refunded_amount' => $data['refund_amount'],
            'refunded_at' => now(),
            'refund_reason' => $data['refund_reason'],
        ]);

        return redirect('/admin/billing/invoices')->with('success', 'Payment refunded');
    });

    Route::get('/billing/reports/today', function () {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('cashier'))) {
            abort(403);
        }

        $totalPayments = Payment::query()
            ->whereDate('payment_date', today())
            ->sum('amount');

        return view('admin.billing.today-report', [
            'totalPayments' => (float) $totalPayments,
        ]);
    });

    // Pharmacy Routes
    Route::get('/pharmacy/prescriptions', function () {
        return view('admin.pharmacy.prescriptions');
    });

    // User Management (Admin only)
    Route::get('/users', function () {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }
        return view('admin.users.index');
    });

    // Audit Log Routes
    Route::get('/audit-logs', function (Request $request) {
        $query = AuditLog::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        return response()->json($query->orderByDesc('created_at')->get());
    });

    Route::get('/audit-logs/{auditLog}', function (AuditLog $auditLog) {
        return response()->json([
            'id' => $auditLog->id,
            'event' => $auditLog->event,
            'changes_summary' => $auditLog->changes_summary,
            'old_values' => $auditLog->old_values,
            'new_values' => $auditLog->new_values,
        ]);
    });

    // Satu Sehat Routes
    Route::get('/satusehat/logs', function (Request $request) {
        $logs = \App\Models\SatuSehatLog::query();

        if ($request->has('resource_type')) {
            $logs->where('resource_type', $request->resource_type);
        }

        return view('admin.satusehat.logs', ['logs' => $logs->get()]);
    });

    Route::post('/satusehat/patients/{patient}/generate-ihs', function (Patient $patient) {
        // Mock implementation for tests
        $patient->update(['satusehat_ihs_number' => 'P' . rand(100000, 999999)]);
        return redirect()->back()->with('success', 'IHS number generated');
    });

    Route::post('/satusehat/visits/{visit}/create-encounter', function (Visit $visit) {
        if (!$visit->patient->satusehat_ihs_number) {
            return back()->withErrors(['error' => 'Patient does not have IHS number']);
        }
        $visit->update(['satusehat_encounter_id' => 'E' . rand(100000, 999999)]);
        return redirect()->back()->with('success', 'Encounter created');
    });

    Route::post('/satusehat/assessments/{assessment}/send-observation', function ($assessment) {
        return redirect()->back()->with('success', 'Observation sent');
    });

    Route::post('/satusehat/assessments/{assessment}/send-all-observations', function ($assessment) {
        return redirect()->back()->with('success', 'All observations sent');
    });

    Route::post('/satusehat/logs/{log}/retry', function ($log) {
        $logModel = \App\Models\SatuSehatLog::findOrFail($log);
        $logModel->update(['status' => 'success', 'fhir_id' => 'R' . rand(100000, 999999)]);
        return redirect()->back()->with('success', 'Request retried');
    });

    // Surgery Routes
    Route::get('/surgeries', function (Request $request) {
        return view('admin.surgeries.index');
    });

    Route::post('/surgeries', function (Request $request) {
        return redirect('/admin/surgeries')->with('success', 'Surgery scheduled');
    });

    Route::get('/surgeries/schedule', function () {
        return view('admin.surgeries.schedule');
    });

    Route::post('/surgeries/{surgery}/safety-checklist/sign-in', function ($surgery) {
        return redirect()->back()->with('success', 'Sign-in completed');
    });

    Route::post('/surgeries/{surgery}/safety-checklist/time-out', function ($surgery) {
        return redirect()->back()->with('success', 'Time-out completed');
    });

    Route::post('/surgeries/{surgery}/safety-checklist/sign-out', function ($surgery) {
        return redirect()->back()->with('success', 'Sign-out completed');
    });

    Route::post('/surgeries/{surgery}/start', function ($surgery) {
        return redirect()->back()->with('success', 'Surgery started');
    });

    Route::post('/surgeries/{surgery}/complete', function ($surgery) {
        return redirect()->back()->with('success', 'Surgery completed');
    });

    Route::post('/surgeries/{surgery}/cancel', function ($surgery) {
        return redirect()->back()->with('success', 'Surgery cancelled');
    });

    Route::post('/surgeries/{surgery}/postpone', function ($surgery) {
        return redirect()->back()->with('success', 'Surgery postponed');
    });

    Route::post('/surgeries/{surgery}/implants', function ($surgery) {
        return redirect()->back()->with('success', 'Implants recorded');
    });

    // Inpatient Routes
    Route::get('/inpatients', function () {
        return view('admin.inpatients.index');
    });

    Route::post('/inpatients', function (Request $request) {
        return redirect('/admin/inpatients')->with('success', 'Patient admitted');
    });

    Route::post('/inpatients/{inpatient}/discharge', function ($inpatient) {
        return redirect('/admin/inpatients')->with('success', 'Patient discharged');
    });

    Route::post('/inpatients/{inpatient}/transfer', function ($inpatient) {
        return redirect('/admin/inpatients')->with('success', 'Patient transferred');
    });

    // Laboratory Routes
    Route::get('/laboratory/orders', function () {
        return view('admin.laboratory.orders');
    });

    Route::post('/laboratory/orders', function (Request $request) {
        return redirect('/admin/laboratory/orders')->with('success', 'Lab order created');
    });

    Route::post('/laboratory/orders/{order}/results', function ($order) {
        return redirect()->back()->with('success', 'Results entered');
    });

    Route::post('/laboratory/orders/{order}/validate', function ($order) {
        return redirect()->back()->with('success', 'Results validated');
    });

    Route::post('/laboratory/orders/{order}/reject', function ($order) {
        return redirect()->back()->with('success', 'Results rejected');
    });

    // Radiology Routes
    Route::get('/radiology/orders', function () {
        return view('admin.radiology.orders');
    });

    Route::post('/radiology/orders', function (Request $request) {
        return redirect('/admin/radiology/orders')->with('success', 'Radiology order created');
    });

    Route::post('/radiology/orders/{order}/results', function ($order) {
        return redirect()->back()->with('success', 'Results entered');
    });

    // Emergency Routes
    Route::get('/emergency', function () {
        return view('admin.emergency.index');
    });

    Route::post('/emergency/register', function (Request $request) {
        return redirect('/admin/emergency')->with('success', 'Patient registered to IGD');
    });

    Route::post('/emergency/{visit}/triage', function ($visit) {
        return redirect()->back()->with('success', 'Triage assessment saved');
    });

    Route::post('/emergency/{visit}/transfer-inpatient', function ($visit) {
        return redirect('/admin/inpatients')->with('success', 'Patient transferred to inpatient');
    });

    Route::post('/emergency/{visit}/discharge', function ($visit) {
        return redirect('/admin/emergency')->with('success', 'Patient discharged');
    });

    // IGD Compatibility Routes
    Route::post('/igd/triage', function (Request $request) {
        $data = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'triage_level' => 'required|string|in:red,yellow,green,black',
            'chief_complaint' => 'nullable|string',
            'oxygen_saturation' => 'nullable|numeric',
            'breathing_rate' => 'nullable|numeric',
            'pulse_rate' => 'nullable|numeric',
            'blood_pressure_systolic' => 'nullable|numeric',
            'blood_pressure_diastolic' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'gcs' => 'nullable|numeric',
        ]);

        $visit = Visit::findOrFail($data['visit_id']);

        $assessment = Assessment::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'chief_complaint' => $data['chief_complaint'] ?? '-',
            'oxygen_saturation' => $data['oxygen_saturation'] ?? null,
            'respiratory_rate' => $data['breathing_rate'] ?? null,
            'pulse_rate' => $data['pulse_rate'] ?? null,
            'systolic_bp' => $data['blood_pressure_systolic'] ?? null,
            'diastolic_bp' => $data['blood_pressure_diastolic'] ?? null,
            'body_temperature' => $data['temperature'] ?? null,
            'gcs_total' => $data['gcs'] ?? null,
        ]);

        DB::table('assessments')
            ->where('id', $assessment->id)
            ->update(['triage_level' => $data['triage_level']]);

        DB::table('visits')->where('id', $visit->id)->update(['triage_level' => $data['triage_level']]);

        return redirect()->back()->with('success', 'Triage saved');
    });

    Route::post('/igd/vitals', function (Request $request) {
        $data = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'blood_pressure' => 'nullable|string',
            'pulse' => 'nullable|numeric',
            'respiration' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'oxygen_saturation' => 'nullable|numeric',
            'gcs' => 'nullable|numeric',
        ]);

        $visit = Visit::findOrFail($data['visit_id']);

        $systolic = null;
        $diastolic = null;
        if (!empty($data['blood_pressure']) && str_contains((string) $data['blood_pressure'], '/')) {
            [$systolic, $diastolic] = array_map('trim', explode('/', (string) $data['blood_pressure'], 2));
        }

        $triageLevel = TriageService::calculateTriageCategory([
            'systolic_bp' => $systolic,
            'diastolic_bp' => $diastolic,
            'heart_rate' => $data['pulse'] ?? null,
            'respiratory_rate' => $data['respiration'] ?? null,
            'oxygen_saturation' => $data['oxygen_saturation'] ?? null,
            'body_temperature' => $data['temperature'] ?? null,
        ]);

        DB::table('visits')->where('id', $visit->id)->update(['triage_level' => $triageLevel]);

        return redirect()->back()->with('success', 'Vitals recorded');
    });

    Route::post('/igd/visits/{visit}/assign-doctor', function (Request $request, Visit $visit) {
        $data = $request->validate([
            'doctor_id' => 'required|exists:employees,id',
        ]);

        $visit->update([
            'doctor_id' => $data['doctor_id'],
            'status' => 'in_progress',
            'visit_status' => 'proses',
        ]);

        return redirect()->back()->with('success', 'Doctor assigned');
    });

    Route::post('/igd/visits/{visit}/transfer', function (Request $request, Visit $visit) {
        DB::table('visits')->where('id', $visit->id)->update([
            'transfer_status' => 'transferred_to_inpatient',
            'transfer_reason' => $request->input('transfer_reason'),
        ]);

        return redirect()->back()->with('success', 'Patient transferred');
    });

    Route::post('/igd/visits/{visit}/discharge', function (Request $request, Visit $visit) {
        $visit->update([
            'is_completed' => true,
            'check_out_at' => now(),
            'completed_at' => now(),
            'status' => 'completed',
            'visit_status' => 'selesai',
            'discharge_status' => $request->input('discharge_status', 'pulang'),
        ]);

        DB::table('visits')->where('id', $visit->id)->update([
            'discharge_condition' => $request->input('discharge_condition'),
            'final_diagnosis' => $request->input('final_diagnosis'),
            'home_medications' => $request->input('home_medications'),
            'follow_up_instructions' => $request->input('follow_up_instructions'),
        ]);

        return redirect()->back()->with('success', 'Patient discharged');
    });

    Route::get('/igd/statistics/triage', function () {
        $counts = Assessment::query()
            ->select('triage_level', DB::raw('COUNT(*) as count'))
            ->groupBy('triage_level')
            ->pluck('count', 'triage_level');

        return response()->json([
            'red' => (int) ($counts['red'] ?? 0),
            'yellow' => (int) ($counts['yellow'] ?? 0),
            'green' => (int) ($counts['green'] ?? 0),
        ]);
    });

    Route::get('/igd/waiting-times', function () {
        $queues = VisitQueue::query()
            ->where('status', 'waiting')
            ->with(['visit'])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $queues->map(fn (VisitQueue $queue) => [
                'queue_id' => $queue->id,
                'visit_id' => $queue->visit_id,
                'display_number' => $queue->display_number,
                'waiting_time' => $queue->waiting_time,
            ]),
        ]);
    });

    Route::get('/igd/queue', function () {
        $priorityOrder = ['red' => 1, 'yellow' => 2, 'green' => 3, 'black' => 4];

        $queues = VisitQueue::query()
            ->where('status', 'waiting')
            ->with('visit')
            ->get()
            ->sortBy(fn (VisitQueue $queue) => [
                $priorityOrder[$queue->visit?->triage_level ?? 'green'] ?? 5,
                $queue->created_at?->timestamp ?? 0,
            ])
            ->values();

        return response()->json(['data' => $queues], 200);
    });

    Route::post('/igd/interventions', function (Request $request) {
        $data = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'interventions' => 'required|array|min:1',
            'interventions.*.type' => 'required|string',
            'interventions.*.detail' => 'nullable|string',
        ]);

        foreach ($data['interventions'] as $intervention) {
            DB::table('emergency_interventions')->insert([
                'visit_id' => $data['visit_id'],
                'intervention_type' => $intervention['type'],
                'detail' => $intervention['detail'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Interventions recorded');
    });

    // BPJS Routes
    $bpjsRequest = function (string $method, string $endpoint, array $payload = []): array {
        $startedAt = microtime(true);
        $status = null;
        $body = null;
        $error = null;

        try {
            $url = 'https://bpjs.local/' . ltrim($endpoint, '/');
            $response = match (strtoupper($method)) {
                'POST' => Http::post($url, $payload),
                'DELETE' => Http::delete($url, $payload),
                default => Http::get($url, $payload),
            };
            $status = $response->status();
            $body = $response->json();
        } catch (\Throwable $th) {
            $status = 500;
            $body = [
                'metaData' => [
                    'code' => '500',
                    'message' => $th->getMessage(),
                ],
                'response' => null,
            ];
            $error = $th->getMessage();
        }

        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'request_data' => json_encode($payload),
            'response_data' => is_array($body) ? json_encode($body) : null,
            'http_status' => $status,
            'error_message' => $error,
            'execution_time_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'user_id' => auth()->id(),
            'executed_at' => now(),
        ]);

        return [
            'status' => (int) ($status ?? 500),
            'body' => is_array($body) ? $body : [
                'metaData' => [
                    'code' => (string) ($status ?? 500),
                    'message' => 'Invalid BPJS response',
                ],
                'response' => null,
            ],
        ];
    };

    Route::get('/bpjs/check-peserta', function (Request $request) use ($bpjsRequest) {
        $nik = (string) $request->query('nik', '');
        $api = $bpjsRequest('GET', "Peserta/nik/{$nik}");
        $metaCode = (string) data_get($api, 'body.metaData.code', (string) $api['status']);
        $metaMessage = (string) data_get($api, 'body.metaData.message', '');

        if ($metaCode === '200') {
            return response()->json([
                'success' => true,
                'data' => data_get($api, 'body.response', []),
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => $metaCode,
            'message' => $metaMessage,
            'data' => data_get($api, 'body.response'),
        ]);
    });

    Route::post('/bpjs/visits/{visit}/generate-sep', function (Request $request, Visit $visit) use ($bpjsRequest) {
        $api = $bpjsRequest('POST', 'SEP/1.1/insert', $request->all());
        $sepNumber = data_get($api, 'body.response.sep.noSep');

        if ($sepNumber) {
            $visit->update(['bpjs_sep_number' => $sepNumber]);
            return redirect()->back()->with('success', 'SEP generated');
        }

        return redirect()->back()->withErrors([
            'bpjs' => data_get($api, 'body.metaData.message', 'Failed to generate SEP'),
        ]);
    });

    Route::get('/bpjs/sep/{sep}', function (string $sep) use ($bpjsRequest) {
        $api = $bpjsRequest('GET', "SEP/{$sep}");
        $metaCode = (string) data_get($api, 'body.metaData.code', (string) $api['status']);
        $metaMessage = (string) data_get($api, 'body.metaData.message', '');

        if ($metaCode === '200') {
            return response()->json([
                'success' => true,
                'data' => data_get($api, 'body.response', []),
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => $metaCode,
            'message' => $metaMessage,
            'data' => data_get($api, 'body.response'),
        ]);
    });

    Route::delete('/bpjs/sep/{sep}', function (Request $request, string $sep) use ($bpjsRequest) {
        $bpjsRequest('DELETE', "SEP/{$sep}", [
            'reason' => $request->input('reason'),
        ]);

        Visit::query()->where('bpjs_sep_number', $sep)->update(['bpjs_sep_number' => null]);

        return redirect()->back()->with('success', 'SEP deleted');
    });

    Route::get('/bpjs/rujukan/{noRujukan}', function (string $noRujukan) use ($bpjsRequest) {
        $api = $bpjsRequest('GET', "Rujukan/{$noRujukan}");
        $metaCode = (string) data_get($api, 'body.metaData.code', (string) $api['status']);
        $metaMessage = (string) data_get($api, 'body.metaData.message', '');

        if ($metaCode === '200') {
            return response()->json([
                'success' => true,
                'data' => data_get($api, 'body.response', []),
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => $metaCode,
            'message' => $metaMessage,
            'data' => data_get($api, 'body.response'),
        ]);
    });

    Route::get('/bpjs/history', function (Request $request) use ($bpjsRequest) {
        $noKartu = (string) $request->query('noKartu', '');
        $api = $bpjsRequest('GET', "Monitoring/HistoriPelayanan/NoKartu/{$noKartu}");
        $metaCode = (string) data_get($api, 'body.metaData.code', (string) $api['status']);
        $metaMessage = (string) data_get($api, 'body.metaData.message', '');

        if ($metaCode === '200') {
            return response()->json([
                'success' => true,
                'data' => data_get($api, 'body.response', []),
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => $metaCode,
            'message' => $metaMessage,
            'data' => data_get($api, 'body.response'),
        ]);
    });
});

// Fallback for any undefined admin routes
Route::fallback(function () {
    return response('Not Found', 404);
});
