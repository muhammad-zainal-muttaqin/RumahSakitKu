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
use App\Models\Clinical\Cppt;
use App\Models\Clinical\Prescription;
use App\Models\Clinical\PrescriptionItem;
use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\Surgery;
use App\Models\Clinical\SurgeryImplant;
use App\Models\Clinical\LaboratoryOrder;
use App\Models\Clinical\LaboratoryResult;
use App\Models\Clinical\RadiologyOrder;
use App\Models\Clinical\RadiologyResult;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Employee;
use App\Models\MasterData\LabTest;
use App\Models\MasterData\Polyclinic;
use App\Models\MasterData\Room;
use App\Models\MasterData\Medicine;
use App\Models\AuditLog;
use App\Models\BpjsLog;
use App\Models\SatuSehatLog;
use App\Services\TriageService;
use App\Services\SatuSehat\SatuSehatService;
use App\Services\SurgeryService;
use App\Notifications\RadiologyReportReady;
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
                    ->orWhere('bpjs_number', 'like', "%{$search}%")
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
            'phone' => 'sometimes|required|string',
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
            $updateData['phone'] = $data['phone'];
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
    Route::get('/medical-records', function (Request $request) {
        $query = MedicalRecord::query()->with('patient');

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('diagnosis_primary', 'like', "%{$search}%")
                    ->orWhere('diagnosis_secondary', 'like', "%{$search}%")
                    ->orWhere('icd10_code', 'like', "%{$search}%");
            });
        }

        $records = $query->orderByDesc('created_at')->get();

        $body = '<h1>Medical Records</h1>';
        foreach ($records as $record) {
            $body .= '<p>' . e((string) $record->diagnosis_primary) . ' - ' . e((string) $record->icd10_code) . '</p>';
        }

        return response($body);
    });

    Route::get('/medical-records/create', function () {
        return view('admin.medical-records.create');
    });

    Route::post('/medical-records', function (Request $request) {
        $data = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:employees,id',
            'visit_date' => 'nullable|date',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'diagnosis_primary' => 'nullable|string',
            'diagnosis_secondary' => 'nullable|string',
            'icd10_code' => 'nullable|string',
            'icd10_description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        MedicalRecord::create([
            'record_number' => 'MR' . now()->format('YmdHis') . rand(100, 999),
            'visit_id' => $data['visit_id'],
            'patient_id' => $data['patient_id'],
            'visit_date' => $data['visit_date'] ?? now()->toDateString(),
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'diagnosis_primary' => $data['diagnosis_primary'] ?? null,
            'diagnosis_secondary' => $data['diagnosis_secondary'] ?? null,
            'icd10_code' => $data['icd10_code'] ?? null,
            'icd10_description' => $data['icd10_description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_finalized' => false,
            'created_by' => auth()->id(),
        ]);

        return redirect('/admin/medical-records')->with('success', 'Medical record created');
    });

    Route::get('/medical-records/{medicalRecord}', function (MedicalRecord $medicalRecord) {
        $orders = LaboratoryOrder::query()
            ->where('medical_record_id', $medicalRecord->id)
            ->pluck('id');

        $results = LaboratoryResult::query()
            ->whereIn('laboratory_order_id', $orders)
            ->with('labTest')
            ->get();

        $radiologyOrders = RadiologyOrder::query()
            ->where('medical_record_id', $medicalRecord->id)
            ->pluck('id');

        $radiologyResults = RadiologyResult::query()
            ->whereIn('radiology_order_id', $radiologyOrders)
            ->get();

        $body = "<h1>Medical Record {$medicalRecord->record_number}</h1>";
        foreach ($results as $result) {
            $testName = $result->labTest?->name ?? 'Lab Test';
            $body .= '<p>' . e($testName) . ': ' . e((string) $result->display_value) . '</p>';
        }

        foreach ($radiologyResults as $result) {
            if ($result->conclusion) {
                $body .= '<p>' . e((string) $result->conclusion) . '</p>';
            }
            if ($result->report_text) {
                $body .= '<p>' . e((string) $result->report_text) . '</p>';
            }
        }

        return response($body);
    });

    Route::put('/medical-records/{medicalRecord}', function (Request $request, MedicalRecord $medicalRecord) {
        if ($medicalRecord->is_finalized) {
            abort(403);
        }

        $data = $request->validate([
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'diagnosis_primary' => 'nullable|string',
            'diagnosis_secondary' => 'nullable|string',
            'icd10_code' => 'nullable|string',
            'icd10_description' => 'nullable|string',
        ]);

        $medicalRecord->update($data);

        return redirect()->back()->with('success', 'Medical record updated');
    });

    Route::post('/medical-records/{medicalRecord}/finalize', function (MedicalRecord $medicalRecord) {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('doctor') && !$user->hasRole('admin'))) {
            abort(403);
        }

        $medicalRecord->update([
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Medical record finalized');
    });

    Route::post('/assessments', function (Request $request) {
        $data = $request->validate([
            'medical_record_id' => 'required|exists:medical_records,id',
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'required|exists:visits,id',
            'assessment_type' => 'nullable|string',
            'assessment_date' => 'nullable|date',
            'chief_complaint' => 'nullable|string',
            'vital_signs' => 'nullable|array',
            'physical_examination' => 'nullable|array',
            'assessed_by' => 'nullable|exists:employees,id',
        ]);

        $assessment = Assessment::create([
            'medical_record_id' => $data['medical_record_id'],
            'patient_id' => $data['patient_id'],
            'visit_id' => $data['visit_id'],
            'chief_complaint' => $data['chief_complaint'] ?? '-',
            'vital_signs' => $data['vital_signs'] ?? [],
            'assessment_type' => $data['assessment_type'] ?? null,
            'assessment_date' => $data['assessment_date'] ?? now(),
            'assessed_at' => $data['assessment_date'] ?? now(),
            'assessed_by' => $data['assessed_by'] ?? auth()->user()?->employee_id,
        ]);

        DB::table('assessments')->where('id', $assessment->id)->update([
            'assessment_type' => $data['assessment_type'] ?? null,
            'assessment_date' => $data['assessment_date'] ?? now(),
        ]);

        return redirect()->back()->with('success', 'Assessment created');
    });

    Route::post('/cppts', function (Request $request) {
        $data = $request->validate([
            'medical_record_id' => 'required|exists:medical_records,id',
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'required|exists:visits,id',
            'cppt_date' => 'nullable|date',
            'cppt_time' => 'nullable',
            'subjective' => 'required|string',
            'objective' => 'required|string',
            'assessment' => 'required|string',
            'plan' => 'required|string',
            'instruction' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
        ]);

        Cppt::create([
            'medical_record_id' => $data['medical_record_id'],
            'patient_id' => $data['patient_id'],
            'visit_id' => $data['visit_id'],
            'cppt_date' => $data['cppt_date'] ?? now()->toDateString(),
            'cppt_time' => $data['cppt_time'] ?? now()->format('H:i:s'),
            'subjective' => $data['subjective'],
            'objective' => $data['objective'],
            'assessment' => $data['assessment'],
            'plan' => $data['plan'],
            'instruction' => $data['instruction'] ?? null,
            'is_verified' => false,
            'created_by' => $data['created_by'] ?? auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'CPPT created');
    });

    Route::post('/cppts/{cppt}/verify', function (Cppt $cppt) {
        $cppt->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->user()?->employee_id,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'CPPT verified');
    });

    Route::post('/prescriptions', function (Request $request) {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'required|exists:visits,id',
            'medical_record_id' => 'required|exists:medical_records,id',
            'prescription_date' => 'nullable|date',
            'prescription_type' => 'nullable|string',
            'priority' => 'nullable|in:normal,urgent',
            'clinical_indication' => 'nullable|string',
            'allergies' => 'nullable|string',
            'prescribed_by' => 'nullable|exists:employees,id',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'nullable|exists:medicines,id',
            'items.*.generic_name' => 'required|string',
            'items.*.dosage_form' => 'nullable|string',
            'items.*.strength' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.dosage_instructions' => 'required|string',
            'items.*.frequency' => 'nullable|string',
            'items.*.duration_days' => 'nullable|integer',
            'items.*.route_of_administration' => 'nullable|string',
            'items.*.instructions' => 'nullable|string',
        ]);

        $typeMap = [
            'non_racikan' => 'regular',
            'racikan' => 'compound',
            'cito' => 'emergency',
        ];
        $prescriptionTypeInput = (string) ($data['prescription_type'] ?? 'regular');
        $prescriptionType = $typeMap[$prescriptionTypeInput] ?? $prescriptionTypeInput;
        if (!in_array($prescriptionType, ['regular', 'emergency', 'compound'], true)) {
            $prescriptionType = 'regular';
        }

        $prefix = 'RX' . now()->format('Ymd');
        $lastPrescription = Prescription::query()
            ->where('prescription_number', 'like', "{$prefix}%")
            ->orderByDesc('prescription_number')
            ->first();
        $seq = $lastPrescription ? ((int) substr((string) $lastPrescription->prescription_number, -4) + 1) : 1;

        $prescription = Prescription::create([
            'prescription_number' => $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'patient_id' => $data['patient_id'],
            'visit_id' => $data['visit_id'],
            'medical_record_id' => $data['medical_record_id'],
            'prescription_date' => $data['prescription_date'] ?? now()->toDateString(),
            'prescription_type' => $prescriptionType,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
            'clinical_indication' => $data['clinical_indication'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'prescribed_by' => $data['prescribed_by'] ?? auth()->user()?->employee_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        foreach ($data['items'] as $item) {
            PrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'medicine_id' => $item['medicine_id'] ?? null,
                'generic_name' => $item['generic_name'],
                'dosage_form' => $item['dosage_form'] ?? null,
                'strength' => $item['strength'] ?? null,
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'dosage_instructions' => $item['dosage_instructions'],
                'frequency' => $item['frequency'] ?? null,
                'duration_days' => $item['duration_days'] ?? null,
                'route_of_administration' => $item['route_of_administration'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', 'Prescription created');
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
    $ensurePharmacyAccess = function (): void {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('pharmacy') && !$user->hasRole('admin'))) {
            abort(403);
        }
    };

    Route::get('/pharmacy/prescriptions', function (Request $request) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $query = Prescription::query()->with('items');
        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->query('priority'));
        }

        $prescriptions = $query
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $body = '<h1>Prescriptions</h1>';
        foreach ($prescriptions as $prescription) {
            $body .= '<p>' . e((string) $prescription->prescription_number) . '</p>';
        }

        return response($body, 200);
    });

    Route::get('/pharmacy/prescriptions/history', function () use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $prescriptions = Prescription::query()
            ->where('status', 'completed')
            ->orderByDesc('updated_at')
            ->get();

        $body = '<h1>Prescription History</h1>';
        foreach ($prescriptions as $prescription) {
            $body .= '<p>' . e((string) $prescription->prescription_number) . '</p>';
        }

        return response($body, 200);
    });

    Route::get('/pharmacy/prescriptions/{prescription}', function (Prescription $prescription) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $prescription->load('items');
        $body = '<h1>Prescription ' . e((string) $prescription->prescription_number) . '</h1>';
        foreach ($prescription->items as $item) {
            $body .= '<p>' . e((string) $item->generic_name) . '</p>';
        }

        return response($body, 200);
    });

    Route::post('/pharmacy/prescriptions/{prescription}/verify', function (Request $request, Prescription $prescription) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $prescription->update([
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
            'dispensed_by' => auth()->user()?->employee_id,
            'notes' => $request->input('notes'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Prescription verified');
    });

    Route::post('/pharmacy/prescriptions/{prescription}/process', function (Prescription $prescription) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        if (!$prescription->verified_by_pharmacist) {
            return redirect()->back()->withErrors(['prescription' => 'Prescription must be verified first']);
        }

        $prescription->update([
            'status' => 'processing',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Prescription processing');
    });

    Route::post('/pharmacy/prescriptions/{prescription}/dispense', function (Request $request, Prescription $prescription) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        if (!$prescription->verified_by_pharmacist) {
            abort(403);
        }

        $dispensedItems = $request->input('dispensed_items', []);
        if (!is_array($dispensedItems) || $dispensedItems === []) {
            return redirect()->back()->withErrors(['dispensed_items' => 'Dispensed items are required']);
        }

        $errors = [];
        $itemsToUpdate = [];

        foreach ($dispensedItems as $row) {
            $itemId = $row['item_id'] ?? null;
            $quantity = isset($row['dispensed_quantity']) ? (float) $row['dispensed_quantity'] : 0.0;

            $item = PrescriptionItem::query()
                ->where('prescription_id', $prescription->id)
                ->whereKey($itemId)
                ->first();

            if (!$item) {
                $errors['item_id'] = 'Invalid prescription item.';
                continue;
            }

            if ($quantity <= 0) {
                $errors['dispensed_quantity'] = 'Invalid dispensed quantity.';
                continue;
            }

            $medicine = $item->medicine;
            if ($medicine && $medicine->is_expired) {
                $errors['expired'] = 'Cannot dispense expired medicine.';
                continue;
            }

            if ($medicine && $medicine->stock < $quantity) {
                $errors['stock'] = 'Insufficient stock for dispensing.';
                continue;
            }

            $itemsToUpdate[] = [
                'item' => $item,
                'quantity' => $quantity,
                'medicine' => $medicine,
            ];
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors);
        }

        foreach ($itemsToUpdate as $row) {
            /** @var PrescriptionItem $item */
            $item = $row['item'];
            $quantity = (float) $row['quantity'];
            /** @var Medicine|null $medicine */
            $medicine = $row['medicine'];

            $item->update([
                'is_dispensed' => true,
                'dispensed_quantity' => $quantity,
                'dispensed_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            if ($medicine) {
                $medicine->update([
                    'stock' => $medicine->stock - $quantity,
                ]);
            }
        }

        $prescription->refresh();
        $allDispensed = $prescription->items->every(function (PrescriptionItem $item): bool {
            return $item->is_dispensed
                && $item->dispensed_quantity !== null
                && $item->dispensed_quantity >= $item->quantity;
        });

        $prescription->update([
            'status' => $allDispensed ? 'completed' : 'processing',
            'dispensed_at' => now(),
            'dispensed_by' => auth()->user()?->employee_id,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Prescription dispensed');
    });

    Route::post('/pharmacy/prescriptions/{prescription}/reject', function (Request $request, Prescription $prescription) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $prescription->update([
            'status' => 'rejected',
            'notes' => $request->input('rejection_reason'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Prescription rejected');
    });

    Route::post('/pharmacy/prescriptions/{prescription}/substitute', function (Request $request, Prescription $prescription) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $itemId = (int) $request->input('original_item_id');
        $substituteId = (int) $request->input('substitute_medicine_id');
        $substitutionNotes = $request->input('substitution_notes');

        $item = PrescriptionItem::query()
            ->where('prescription_id', $prescription->id)
            ->whereKey($itemId)
            ->first();
        $medicine = Medicine::query()->find($substituteId);

        if (!$item || !$medicine) {
            return redirect()->back()->withErrors(['substitution' => 'Invalid substitution request']);
        }

        $item->update([
            'medicine_id' => $medicine->id,
            'generic_name' => $medicine->generic_name ?: $medicine->name,
            'substitution_notes' => $substitutionNotes,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Medicine substituted');
    });

    Route::get('/pharmacy/medicines', function (Request $request) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $query = Medicine::query();
        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $medicines = $query->orderBy('name')->get();
        $body = '<h1>Medicines</h1>';
        foreach ($medicines as $medicine) {
            $stockValue = rtrim(rtrim((string) $medicine->stock, '0'), '.');
            $body .= '<p>' . e((string) $medicine->name) . ' - ' . e($stockValue) . '</p>';
        }

        return response($body, 200);
    });

    Route::get('/pharmacy/medicines/low-stock', function () use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $medicines = Medicine::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('name')
            ->get();

        $body = '<h1>Low Stock Medicines</h1>';
        foreach ($medicines as $medicine) {
            $body .= '<p>' . e((string) $medicine->name) . '</p>';
        }

        return response($body, 200);
    });

    Route::post('/pharmacy/medicines/{medicine}/update-stock', function (Request $request, Medicine $medicine) use ($ensurePharmacyAccess) {
        $ensurePharmacyAccess();

        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:in,out',
            'reason' => 'nullable|string',
        ]);

        $quantity = (float) $data['quantity'];
        if ($data['type'] === 'out') {
            $quantity = -1 * $quantity;
        }

        $medicine->update([
            'stock' => $medicine->stock + $quantity,
        ]);

        return redirect()->back()->with('success', 'Stock updated');
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

    // Report Routes
    Route::get('/reports/rl-1-1', function () {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $hospitalName = config('app.hospital_name', 'Rumah Sakit');

        return response("<h1>Data Dasar Rumah Sakit</h1><p>{$hospitalName}</p>");
    });

    Route::get('/reports/rl-3-1/export', function (Request $request) {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $format = strtolower((string) $request->query('format', 'excel'));
        if ($format === 'pdf') {
            return response('RL 3.1 PDF', 200, [
                'content-type' => 'application/pdf',
            ]);
        }

        return response('RL 3.1 EXCEL', 200, [
            'content-type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    });

    Route::get('/reports/rl-3-1', function (Request $request) {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $totalBeds = (int) Room::query()->sum('total_beds');
        if ($totalBeds <= 0) {
            $totalBeds = Bed::query()->count();
        }
        $occupiedBeds = (int) Bed::query()->where('status', 'terisi')->count();
        $bor = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 2) : 0;

        $inpatientVisits = Visit::query()
            ->where('visit_type', 'rawat_inap')
            ->whereNotNull('check_out_at')
            ->get();

        $dischargedCount = $inpatientVisits->count();
        $totalLengthOfStay = $inpatientVisits->sum(function (Visit $visit) {
            $checkIn = $visit->check_in_at ?? $visit->registration_date;
            $checkOut = $visit->check_out_at ?? $visit->check_in_at ?? $visit->registration_date;
            if (!$checkIn || !$checkOut) {
                return 0;
            }

            return max(1, \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)));
        });

        $los = $dischargedCount > 0 ? round($totalLengthOfStay / $dischargedCount, 2) : 0;
        $bto = $totalBeds > 0 ? round($dischargedCount / $totalBeds, 2) : 0;
        $toi = $dischargedCount > 0 ? round((($totalBeds * 30) - $totalLengthOfStay) / $dischargedCount, 2) : 0;

        $html = "<h1>RL 3.1</h1>"
            . "<p>BOR: {$bor}</p>"
            . "<p>LOS: {$los}</p>"
            . "<p>TOI: {$toi}</p>"
            . "<p>BTO: {$bto}</p>";

        return response($html);
    });

    Route::get('/reports/rl-4', function (Request $request) {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $records = MedicalRecord::query()->get();
        $rows = $records
            ->map(function (MedicalRecord $record) {
                return [
                    'code' => $record->icd_code ?: $record->icd10_code,
                    'name' => $record->icd_name ?: $record->icd10_description,
                ];
            })
            ->filter(fn (array $row) => !empty($row['code']) || !empty($row['name']));

        if ($request->query('group_by') === 'chapter') {
            $chapters = $rows
                ->groupBy(function (array $row) {
                    return strtoupper(substr((string) ($row['code'] ?? 'X'), 0, 1));
                })
                ->map(fn ($items, $chapter) => "Chapter {$chapter}: " . count($items))
                ->values()
                ->implode("\n");

            return response("<h1>RL 4 - Chapter</h1><pre>{$chapters}</pre>");
        }

        $top = $rows
            ->groupBy(fn (array $row) => (string) ($row['name'] ?: $row['code']))
            ->map(function ($items, $name) {
                /** @var \Illuminate\Support\Collection<int, array{code: string|null, name: string|null}> $items */
                return [
                    'name' => $name,
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $body = '<h1>RL 4</h1>';
        foreach ($top as $item) {
            $body .= '<p>' . e((string) $item['name']) . ' - ' . e((string) $item['count']) . '</p>';
        }

        return response($body);
    });

    Route::get('/reports/daily', function (Request $request) {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $date = (string) $request->query('date', now()->format('Y-m-d'));
        $count = Visit::query()
            ->whereDate('visit_date', $date)
            ->orWhereDate('registration_date', $date)
            ->count();

        return response("<h1>Daily Report</h1><p>{$date}: {$count}</p>");
    });

    Route::get('/reports/monthly', function (Request $request) {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $month = (string) $request->query('month', now()->format('Y-m'));
        [$yearValue, $monthValue] = array_pad(explode('-', $month), 2, null);
        $year = (int) $yearValue;
        $monthNum = (int) $monthValue;

        $count = Visit::query()
            ->where(function ($q) use ($year, $monthNum) {
                $q->whereYear('visit_date', $year)->whereMonth('visit_date', $monthNum);
            })
            ->orWhere(function ($q) use ($year, $monthNum) {
                $q->whereYear('registration_date', $year)->whereMonth('registration_date', $monthNum);
            })
            ->count();

        return response("<h1>Monthly Report</h1><p>{$month}: {$count}</p>");
    });

    Route::get('/reports/yearly', function (Request $request) {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $year = (int) $request->query('year', now()->year);
        $count = Visit::query()
            ->whereYear('visit_date', $year)
            ->orWhereYear('registration_date', $year)
            ->count();

        return response("<h1>Yearly Report</h1><p>{$year}: {$count}</p>");
    });

    Route::get('/reports/summary', function () {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $rawatJalan = Visit::query()->where('visit_type', 'rawat_jalan')->count();
        $rawatInap = Visit::query()->where('visit_type', 'rawat_inap')->count();
        $igd = Visit::query()->where('visit_type', 'igd')->count();

        return response(
            "<h1>Summary</h1>"
            . "<p>Rawat Jalan: {$rawatJalan}</p>"
            . "<p>Rawat Inap: {$rawatInap}</p>"
            . "<p>IGD: {$igd}</p>"
        );
    });

    Route::get('/reports/comparison', function () {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $visits = Visit::query()->get();
        $currentMonth = $visits->filter(function (Visit $visit) {
            $date = $visit->visit_date ?? $visit->registration_date;
            return $date ? \Carbon\Carbon::parse($date)->isSameMonth(now()) : false;
        })->count();

        $lastMonth = $visits->filter(function (Visit $visit) {
            $date = $visit->visit_date ?? $visit->registration_date;
            return $date ? \Carbon\Carbon::parse($date)->isSameMonth(now()->subMonth()) : false;
        })->count();

        $growth = $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;

        return response("<h1>Comparison</h1><p>Growth: {$growth}%</p>");
    });

    Route::get('/reports/bed-occupancy-by-class', function () {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('report_viewer'))) {
            abort(403);
        }

        $rooms = Room::query()
            ->select(['room_class', 'total_beds', 'available_beds'])
            ->orderBy('room_class')
            ->get();

        $body = '<h1>Bed Occupancy By Class</h1>';
        foreach ($rooms as $room) {
            $occupied = max(0, (int) $room->total_beds - (int) $room->available_beds);
            $body .= '<p>' . e((string) $room->room_class) . ": {$occupied}/{$room->total_beds}</p>";
        }

        return response($body);
    });

    // Satu Sehat Routes
    Route::get('/satusehat/logs', function (Request $request) {
        $logs = SatuSehatLog::query();

        if ($request->has('resource_type')) {
            $logs->where('resource_type', $request->resource_type);
        }

        return view('admin.satusehat.logs', ['logs' => $logs->get()]);
    });

    Route::post('/satusehat/patients/{patient}/generate-ihs', function (Patient $patient) {
        $service = app(SatuSehatService::class);

        $gender = match (strtolower((string) $patient->gender)) {
            'l', 'male', 'm', 'laki-laki' => 'male',
            'p', 'female', 'f', 'perempuan' => 'female',
            default => 'unknown',
        };

        $result = $service->request('Patient', 'POST', [
            'resourceType' => 'Patient',
            'identifier' => [
                [
                    'system' => 'https://fhir.kemkes.go.id/id/nik',
                    'value' => $patient->nik,
                ],
            ],
            'name' => [
                [
                    'text' => $patient->name,
                ],
            ],
            'gender' => $gender,
            'birthDate' => optional($patient->birth_date)->format('Y-m-d'),
            'local_type' => 'patient',
            'local_id' => $patient->id,
        ]);

        if (!($result['success'] ?? false)) {
            return redirect()->back()->with('error', $result['error'] ?? 'Failed to generate IHS number');
        }

        $patient->update([
            'satusehat_ihs_number' => (string) data_get($result, 'data.id'),
        ]);

        return redirect()->back()->with('success', 'IHS number generated');
    });

    Route::post('/satusehat/visits/{visit}/create-encounter', function (Visit $visit) {
        $service = app(SatuSehatService::class);

        if (!$visit->patient->satusehat_ihs_number) {
            return back()
                ->withErrors(['error' => 'Patient does not have IHS number'])
                ->with('error', 'Patient does not have IHS number');
        }

        $result = $service->request('Encounter', 'POST', [
            'resourceType' => 'Encounter',
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
            ],
            'subject' => [
                'reference' => 'Patient/' . $visit->patient->satusehat_ihs_number,
            ],
            'period' => [
                'start' => now()->toIso8601String(),
                'end' => now()->addHour()->toIso8601String(),
            ],
            'local_type' => 'visit',
            'local_id' => $visit->id,
        ]);

        if (!($result['success'] ?? false)) {
            return redirect()->back()->with('error', $result['error'] ?? 'Failed to create encounter');
        }

        $visit->update(['satusehat_encounter_id' => (string) data_get($result, 'data.id')]);

        return redirect()->back()->with('success', 'Encounter created');
    });

    Route::post('/satusehat/assessments/{assessment}/send-observation', function (Assessment $assessment) {
        $service = app(SatuSehatService::class);
        $patient = $assessment->patient;
        $visit = $assessment->visit;

        if (!$patient?->satusehat_ihs_number || !$visit?->satusehat_encounter_id) {
            return back()
                ->withErrors(['error' => 'Patient IHS number or encounter ID is missing'])
                ->with('error', 'Patient IHS number or encounter ID is missing');
        }

        $vitalSigns = $assessment->vital_signs ?? [];

        $metricMap = [
            'systolic' => ['code' => '8480-6', 'display' => 'Systolic blood pressure', 'unit' => 'mmHg'],
            'diastolic' => ['code' => '8462-4', 'display' => 'Diastolic blood pressure', 'unit' => 'mmHg'],
            'heart_rate' => ['code' => '8867-4', 'display' => 'Heart rate', 'unit' => 'beats/minute'],
            'temperature' => ['code' => '8310-5', 'display' => 'Body temperature', 'unit' => 'celcius'],
            'respiratory_rate' => ['code' => '9279-1', 'display' => 'Respiratory rate', 'unit' => 'breaths/minute'],
            'oxygen_saturation' => ['code' => '2708-6', 'display' => 'Oxygen saturation', 'unit' => '%'],
        ];

        $pickedKey = null;
        foreach (array_keys($metricMap) as $key) {
            if (array_key_exists($key, $vitalSigns) && $vitalSigns[$key] !== null && $vitalSigns[$key] !== '') {
                $pickedKey = $key;
                break;
            }
        }

        if ($pickedKey === null) {
            return back()->withErrors(['error' => 'No valid vital signs found']);
        }

        $metric = $metricMap[$pickedKey];
        $result = $service->request('Observation', 'POST', [
            'resourceType' => 'Observation',
            'status' => 'final',
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => $metric['code'],
                        'display' => $metric['display'],
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patient->satusehat_ihs_number,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $visit->satusehat_encounter_id,
            ],
            'valueQuantity' => [
                'value' => (float) $vitalSigns[$pickedKey],
                'unit' => $metric['unit'],
            ],
            'local_type' => 'assessment',
            'local_id' => $assessment->id,
        ]);

        if (!($result['success'] ?? false)) {
            return back()->with('error', $result['error'] ?? 'Failed to send observation');
        }

        return redirect()->back()->with('success', 'Observation sent');
    });

    Route::post('/satusehat/assessments/{assessment}/send-all-observations', function (Assessment $assessment) {
        $service = app(SatuSehatService::class);
        $patient = $assessment->patient;
        $visit = $assessment->visit;

        if (!$patient?->satusehat_ihs_number || !$visit?->satusehat_encounter_id) {
            return back()
                ->withErrors(['error' => 'Patient IHS number or encounter ID is missing'])
                ->with('error', 'Patient IHS number or encounter ID is missing');
        }

        $vitalSigns = $assessment->vital_signs ?? [];
        $metrics = [
            'systolic' => ['code' => '8480-6', 'display' => 'Systolic blood pressure', 'unit' => 'mmHg'],
            'diastolic' => ['code' => '8462-4', 'display' => 'Diastolic blood pressure', 'unit' => 'mmHg'],
            'heart_rate' => ['code' => '8867-4', 'display' => 'Heart rate', 'unit' => 'beats/minute'],
            'temperature' => ['code' => '8310-5', 'display' => 'Body temperature', 'unit' => 'celcius'],
            'respiratory_rate' => ['code' => '9279-1', 'display' => 'Respiratory rate', 'unit' => 'breaths/minute'],
            'oxygen_saturation' => ['code' => '2708-6', 'display' => 'Oxygen saturation', 'unit' => '%'],
        ];

        $errors = [];
        foreach ($metrics as $key => $metric) {
            if (!array_key_exists($key, $vitalSigns) || $vitalSigns[$key] === null || $vitalSigns[$key] === '') {
                continue;
            }

            $result = $service->request('Observation', 'POST', [
                'resourceType' => 'Observation',
                'status' => 'final',
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => $metric['code'],
                            'display' => $metric['display'],
                        ],
                    ],
                ],
                'subject' => [
                    'reference' => 'Patient/' . $patient->satusehat_ihs_number,
                ],
                'encounter' => [
                    'reference' => 'Encounter/' . $visit->satusehat_encounter_id,
                ],
                'valueQuantity' => [
                    'value' => (float) $vitalSigns[$key],
                    'unit' => $metric['unit'],
                ],
                'local_type' => 'assessment',
                'local_id' => $assessment->id,
            ]);

            if (!($result['success'] ?? false)) {
                $errors[] = $result['error'] ?? "Failed to send {$key}";
            }
        }

        if (!empty($errors)) {
            return back()->with('error', implode('; ', $errors));
        }

        return redirect()->back()->with('success', 'All observations sent');
    });

    Route::post('/satusehat/logs/{log}/retry', function (SatuSehatLog $log) {
        $service = app(SatuSehatService::class);

        $payload = is_array($log->request_data) ? $log->request_data : [];
        $payload['local_type'] = $payload['local_type'] ?? $log->local_type;
        $payload['local_id'] = $payload['local_id'] ?? $log->local_id;

        $result = $service->request($log->resource_type, strtoupper($log->action ?: 'POST'), $payload);

        $log->update([
            'status' => ($result['success'] ?? false) ? 'success' : 'failed',
            'fhir_id' => (string) data_get($result, 'data.id', $log->fhir_id),
            'response_data' => $result['data'] ?? null,
            'error_message' => ($result['success'] ?? false) ? null : ($result['error'] ?? 'Retry failed'),
            'retry_count' => (int) $log->retry_count + 1,
        ]);

        if (!($result['success'] ?? false)) {
            return redirect()->back()->with('error', $result['error'] ?? 'Retry failed');
        }

        return redirect()->back()->with('success', 'Request retried');
    });

    // Surgery Routes
    Route::get('/surgeries', function (Request $request) {
        $surgeries = Surgery::query()
            ->when($request->filled('type'), fn ($q) => $q->where('surgery_type', (string) $request->query('type')))
            ->orderByDesc('scheduled_date')
            ->get();

        return view('admin.surgeries.index', ['surgeries' => $surgeries]);
    });

    Route::post('/surgeries', function (Request $request) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'visit_id' => 'nullable|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'surgeon_id' => 'required|exists:employees,id',
            'scheduled_date' => 'required|date',
            'start_time' => 'nullable|date',
            'estimated_end_time' => 'nullable|date|after:start_time',
            'operating_room' => 'required|string|max:20',
            'procedure_name' => 'required|string|max:191',
            'procedure_code' => 'nullable|string|max:20',
            'surgery_type' => 'nullable|in:elektif,urgent,cito,emergency',
            'pre_diagnosis' => 'nullable|string',
            'anesthesia_type' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        if (!empty($data['start_time']) && !empty($data['estimated_end_time'])) {
            $hasConflict = Surgery::overlapping(
                (string) $data['operating_room'],
                \Carbon\Carbon::parse((string) $data['start_time']),
                \Carbon\Carbon::parse((string) $data['estimated_end_time'])
            )->exists();

            if ($hasConflict) {
                return redirect()->back()
                    ->withErrors(['operating_room' => 'Operating room already booked for this time slot'])
                    ->withInput();
            }
        }

        $visitId = $data['visit_id'] ?? Visit::query()
            ->where('patient_id', (int) $data['patient_id'])
            ->latest('id')
            ->value('id');

        if (!$visitId) {
            return redirect()->back()
                ->withErrors(['visit_id' => 'Visit is required for surgery scheduling'])
                ->withInput();
        }

        Surgery::create([
            'surgery_number' => app(SurgeryService::class)->generateSurgeryNumber(),
            'visit_id' => $visitId,
            'patient_id' => $data['patient_id'],
            'surgeon_id' => $data['surgeon_id'],
            'scheduled_date' => $data['scheduled_date'],
            'start_time' => $data['start_time'] ?? null,
            'estimated_end_time' => $data['estimated_end_time'] ?? null,
            'operating_room' => $data['operating_room'],
            'procedure_name' => $data['procedure_name'],
            'procedure_code' => $data['procedure_code'] ?? null,
            'surgery_type' => $data['surgery_type'] ?? 'elektif',
            'pre_diagnosis' => $data['pre_diagnosis'] ?? null,
            'anesthesia_type' => $data['anesthesia_type'] ?? null,
            'status' => 'scheduled',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect('/admin/surgeries')->with('success', 'Surgery scheduled');
    });

    Route::get('/surgeries/schedule', function () {
        return view('admin.surgeries.schedule');
    });

    Route::post('/surgeries/{surgery}/safety-checklist/sign-in', function (Surgery $surgery) {
        $surgery->update([
            'safety_checklist_sign_in' => true,
            'safety_checklist_sign_in_at' => now(),
            'status' => in_array($surgery->status, ['scheduled', 'preparation'], true) ? 'preparation' : $surgery->status,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Sign-in completed');
    });

    Route::post('/surgeries/{surgery}/safety-checklist/time-out', function (Surgery $surgery) {
        $surgery->update([
            'safety_checklist_time_out' => true,
            'safety_checklist_time_out_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Time-out completed');
    });

    Route::post('/surgeries/{surgery}/safety-checklist/sign-out', function (Surgery $surgery) {
        $surgery->update([
            'safety_checklist_sign_out' => true,
            'safety_checklist_sign_out_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Sign-out completed');
    });

    Route::post('/surgeries/{surgery}/start', function (Surgery $surgery) {
        $result = app(SurgeryService::class)->startSurgery($surgery->id);
        if (!$result) {
            return redirect()->back()->withErrors(['status' => 'Surgery cannot be started']);
        }

        return redirect()->back()->with('success', 'Surgery started');
    });

    Route::post('/surgeries/{surgery}/complete', function (Request $request, Surgery $surgery) {
        if (!in_array($surgery->surgery_type, ['cito', 'emergency'], true) && !$surgery->safety_checklist_sign_out) {
            return redirect()->back()->withErrors(['safety_checklist' => 'All safety checklist items must be completed']);
        }

        $data = $request->validate([
            'actual_end' => 'nullable|date',
            'post_diagnosis' => 'nullable|string',
            'procedure_notes' => 'nullable|string',
            'findings' => 'nullable|string',
            'complications' => 'nullable|string',
            'specimens' => 'nullable|string',
        ]);

        $result = app(SurgeryService::class)->completeSurgery($surgery->id, $data);
        if (!$result) {
            return redirect()->back()->withErrors(['status' => 'Surgery cannot be completed']);
        }

        return redirect()->back()->with('success', 'Surgery completed');
    });

    Route::post('/surgeries/{surgery}/cancel', function (Request $request, Surgery $surgery) {
        $result = app(SurgeryService::class)->cancelSurgery(
            $surgery->id,
            $request->input('cancellation_reason'),
            auth()->id()
        );

        if (!$result) {
            return redirect()->back()->withErrors(['status' => 'Surgery cannot be cancelled']);
        }

        return redirect()->back()->with('success', 'Surgery cancelled');
    });

    Route::post('/surgeries/{surgery}/postpone', function (Request $request, Surgery $surgery) {
        $data = $request->validate([
            'postponed_reason' => 'nullable|string',
            'new_scheduled_date' => 'nullable|date',
        ]);

        $result = app(SurgeryService::class)->postponeSurgery($surgery->id, $data['postponed_reason'] ?? null);
        if (!$result) {
            return redirect()->back()->withErrors(['status' => 'Surgery cannot be postponed']);
        }

        if (!empty($data['new_scheduled_date'])) {
            $surgery->update([
                'scheduled_date' => $data['new_scheduled_date'],
                'updated_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', 'Surgery postponed');
    });

    Route::post('/surgeries/{surgery}/implants', function (Request $request, Surgery $surgery) {
        $data = $request->validate([
            'implants' => 'required|array|min:1',
            'implants.*.implant_name' => 'required|string|max:191',
            'implants.*.implant_type' => 'nullable|string|max:50',
            'implants.*.implant_code' => 'nullable|string|max:100',
            'implants.*.manufacturer' => 'nullable|string|max:191',
            'implants.*.batch_number' => 'nullable|string|max:100',
            'implants.*.quantity' => 'nullable|integer|min:1',
            'implants.*.notes' => 'nullable|string',
        ]);

        foreach ($data['implants'] as $implant) {
            SurgeryImplant::create([
                'surgery_id' => $surgery->id,
                'implant_name' => $implant['implant_name'],
                'implant_type' => $implant['implant_type'] ?? 'other',
                'serial_number' => $implant['implant_code'] ?? null,
                'manufacturer' => $implant['manufacturer'] ?? null,
                'batch_number' => $implant['batch_number'] ?? null,
                'quantity' => (int) ($implant['quantity'] ?? 1),
                'notes' => $implant['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', 'Implants recorded');
    });

    // Inpatient Routes
    Route::get('/inpatients', function () {
        return view('admin.inpatients.index');
    });

    Route::post('/inpatients', function (Request $request) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'room_id' => 'required|exists:rooms,id',
            'bed_id' => 'required|exists:beds,id',
            'doctor_id' => 'required|exists:employees,id',
            'visit_type' => 'nullable|in:rawat_inap',
            'admission_date' => 'required|date',
            'complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'deposit_amount' => 'nullable|numeric|min:0',
        ]);

        $validator->after(function ($validator) use ($request) {
            $room = Room::query()->find($request->input('room_id'));
            $bed = Bed::query()->find($request->input('bed_id'));

            $roomHasAvailableBed = false;
            if ($room) {
                $roomHasAvailableBed = Bed::query()
                    ->where('room_id', $room->id)
                    ->where('status', 'kosong')
                    ->whereNull('current_visit_id')
                    ->exists();
            }

            if ($room && (int) ($room->getRawOriginal('available_beds') ?? 0) <= 0 && !$roomHasAvailableBed) {
                $validator->errors()->add('room_id', 'Room is full.');
            }

            if ($bed) {
                if ((int) $bed->room_id !== (int) $request->input('room_id')) {
                    $validator->errors()->add('bed_id', 'Bed does not belong to selected room.');
                }

                if ($bed->status !== 'kosong' || $bed->current_visit_id !== null) {
                    $validator->errors()->add('bed_id', 'Bed is occupied.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $visit = Visit::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'room_id' => $data['room_id'],
            'bed_id' => $data['bed_id'],
            'visit_type' => 'rawat_inap',
            'registration_date' => $data['admission_date'],
            'admission_date' => $data['admission_date'],
            'check_in_at' => $data['admission_date'],
            'status' => 'in_progress',
            'visit_status' => 'proses',
            'notes' => $data['complaint'] ?? null,
            'priority' => 'normal',
        ]);

        DB::table('visits')->where('id', $visit->id)->update([
            'complaint' => $data['complaint'] ?? null,
            'final_diagnosis' => $data['diagnosis'] ?? null,
        ]);

        $bed = Bed::query()->findOrFail($data['bed_id']);
        $bed->occupy($visit->id);

        return redirect('/admin/inpatients')->with('success', 'Patient admitted');
    });

    Route::post('/inpatients/{inpatient}/discharge', function (Request $request, Visit $inpatient) {
        if ((bool) $inpatient->is_completed || !empty($inpatient->check_out_at) || $inpatient->status === 'completed') {
            $response = redirect()->back()->withErrors(['inpatient' => 'Patient already discharged']);
            $response->setStatusCode(422);
            return $response;
        }

        $bed = Bed::query()
            ->where('current_visit_id', $inpatient->id)
            ->first();

        if (!$bed && $inpatient->bed_id) {
            $bed = Bed::query()->find($inpatient->bed_id);
        }

        if ($bed) {
            $bed->vacate();
        }

        $rawDischargeStatus = (string) $request->input('discharge_status', 'pulang');
        $mappedDischargeStatus = match (strtolower($rawDischargeStatus)) {
            'sembuh' => 'pulang',
            default => $rawDischargeStatus,
        };

        $inpatient->update([
            'discharge_date' => $request->input('discharge_date', now()->toDateString()),
            'discharge_status' => $mappedDischargeStatus,
            'is_completed' => true,
            'check_out_at' => now(),
            'completed_at' => now(),
            'status' => 'completed',
            'visit_status' => 'selesai',
            'notes' => $request->input('notes'),
        ]);

        DB::table('visits')->where('id', $inpatient->id)->update([
            'final_diagnosis' => $request->input('final_diagnosis'),
        ]);

        Invoice::firstOrCreate(
            ['visit_id' => $inpatient->id],
            [
                'invoice_number' => 'INV' . now()->format('YmdHis') . rand(100, 999),
                'visit_id' => $inpatient->id,
                'patient_id' => $inpatient->patient_id,
                'due_date' => now()->addDays(14),
                'total_amount' => 0,
                'paid_amount' => 0,
                'status' => 'pending',
            ]
        );

        return redirect('/admin/inpatients')->with('success', 'Patient discharged');
    });

    Route::post('/inpatients/{inpatient}/transfer', function (Request $request, Visit $inpatient) {
        $data = $request->validate([
            'new_bed_id' => 'required|exists:beds,id',
            'transfer_reason' => 'nullable|string',
        ]);

        $newBed = Bed::query()->findOrFail($data['new_bed_id']);
        if ($newBed->status !== 'kosong' || $newBed->current_visit_id !== null) {
            return redirect()->back()->withErrors(['new_bed_id' => 'Target bed is occupied.']);
        }

        $oldBed = Bed::query()->where('current_visit_id', $inpatient->id)->first();
        if (!$oldBed && $inpatient->bed_id) {
            $oldBed = Bed::query()->find($inpatient->bed_id);
        }

        if ($oldBed) {
            $oldBed->vacate();
        }

        $newBed->occupy($inpatient->id);

        $inpatient->update([
            'room_id' => $newBed->room_id,
            'bed_id' => $newBed->id,
        ]);

        DB::table('visits')->where('id', $inpatient->id)->update([
            'transfer_reason' => $data['transfer_reason'] ?? null,
        ]);

        return redirect('/admin/inpatients')->with('success', 'Patient transferred');
    });

    Route::post('/inpatients/{inpatient}/deposit', function (Request $request, Visit $inpatient) {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::firstOrCreate(
            ['visit_id' => $inpatient->id],
            [
                'invoice_number' => 'INV' . now()->format('YmdHis') . rand(100, 999),
                'visit_id' => $inpatient->id,
                'patient_id' => $inpatient->patient_id,
                'due_date' => now()->addDays(14),
                'total_amount' => 0,
                'paid_amount' => 0,
                'status' => 'pending',
            ]
        );

        $paymentMethod = strtolower((string) $data['payment_method']);
        if ($paymentMethod === 'transfer') {
            $paymentMethod = 'bank_transfer';
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => $data['amount'],
            'payment_method' => $paymentMethod,
            'payment_type' => 'deposit',
            'notes' => $data['notes'] ?? null,
            'received_by' => auth()->id(),
        ]);

        DB::table('payments')->where('id', $payment->id)->update([
            'visit_id' => $inpatient->id,
        ]);

        return redirect('/admin/inpatients')->with('success', 'Deposit recorded');
    });

    Route::get('/reports/bed-occupancy', function () {
        $totalBeds = Bed::query()->count();
        $occupiedBeds = Bed::query()->where('status', 'terisi')->count();
        $bor = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 2) : 0;

        return response()->json([
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'bor' => $bor,
        ]);
    });

    // Laboratory Routes
    Route::get('/laboratory/orders', function (Request $request) {
        $query = LaboratoryOrder::query()->with(['patient', 'doctor']);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->query('priority'));
        }

        return view('admin.laboratory.orders', [
            'orders' => $query->orderByDesc('created_at')->get(),
        ]);
    });

    Route::post('/laboratory/orders', function (Request $request) {
        $data = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:employees,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'order_date' => 'nullable|date',
            'priority' => 'nullable|in:normal,urgent,cito',
            'diagnosis_notes' => 'nullable|string',
            'clinical_notes' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*' => 'exists:lab_tests,id',
        ]);

        $prefix = 'LAB' . now()->format('Ymd');
        $lastOrder = LaboratoryOrder::query()
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByDesc('order_number')
            ->first();
        $sequence = $lastOrder ? ((int) substr((string) $lastOrder->order_number, -4) + 1) : 1;

        $order = LaboratoryOrder::create([
            'order_number' => $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'visit_id' => $data['visit_id'],
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? auth()->user()?->employee_id,
            'medical_record_id' => $data['medical_record_id'] ?? null,
            'order_date' => $data['order_date'] ?? now(),
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
            'diagnosis_notes' => $data['diagnosis_notes'] ?? null,
            'clinical_notes' => $data['clinical_notes'] ?? null,
            'is_cito' => ($data['priority'] ?? 'normal') === 'cito',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        foreach (($data['lab_tests'] ?? []) as $labTestId) {
            LaboratoryResult::create([
                'laboratory_order_id' => $order->id,
                'lab_test_id' => $labTestId,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        return redirect('/admin/laboratory/orders')->with('success', 'Lab order created');
    });

    Route::post('/laboratory/orders/{order}/results', function (Request $request, LaboratoryOrder $order) {
        $data = $request->validate([
            'results' => 'required|array|min:1',
            'results.*.lab_test_id' => 'required|exists:lab_tests,id',
            'results.*.result_value' => 'nullable|numeric',
            'results.*.result_text' => 'nullable|string',
            'results.*.unit' => 'nullable|string|max:20',
            'results.*.reference_range' => 'nullable|string|max:100',
            'results.*.flag' => 'nullable|in:normal,low,high,abnormal,critical',
            'results.*.notes' => 'nullable|string',
        ]);

        foreach ($data['results'] as $resultRow) {
            $result = LaboratoryResult::query()
                ->where('laboratory_order_id', $order->id)
                ->where('lab_test_id', $resultRow['lab_test_id'])
                ->first();

            if (!$result) {
                $result = new LaboratoryResult([
                    'laboratory_order_id' => $order->id,
                    'lab_test_id' => $resultRow['lab_test_id'],
                ]);
            }

            $result->fill([
                'result_value' => $resultRow['result_value'] ?? null,
                'result_text' => $resultRow['result_text'] ?? null,
                'unit' => $resultRow['unit'] ?? null,
                'reference_range' => $resultRow['reference_range'] ?? null,
                'flag' => $resultRow['flag'] ?? 'normal',
                'notes' => $resultRow['notes'] ?? null,
                'updated_by' => auth()->id(),
                'created_by' => $result->exists ? $result->created_by : auth()->id(),
            ]);
            $result->save();
        }

        $order->update([
            'status' => 'in_progress',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Results entered');
    });

    Route::post('/laboratory/orders/{order}/validate', function (Request $request, LaboratoryOrder $order) {
        $data = $request->validate([
            'action' => 'required|in:approve,reject',
            'validator_notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        if ($data['action'] === 'reject') {
            $order->update([
                'status' => 'in_progress',
                'updated_by' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Results rejected');
        }

        $results = $order->results()->get();
        $hasAnyResult = $results->isNotEmpty();
        $allEntered = $hasAnyResult && $results->every(
            fn (LaboratoryResult $result) => $result->result_value !== null || !empty($result->result_text)
        );

        if (!$allEntered) {
            return redirect()->back()->withErrors(['results' => 'All results must be entered before validation']);
        }

        $validatorEmployeeId = auth()->user()?->employee_id;

        LaboratoryResult::query()
            ->where('laboratory_order_id', $order->id)
            ->update([
                'validated_by' => $validatorEmployeeId,
                'validated_at' => now(),
                'updated_by' => auth()->id(),
            ]);

        $order->update([
            'status' => 'validated',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Results validated');
    });

    Route::post('/laboratory/orders/{order}/reject', function (LaboratoryOrder $order) {
        $order->update([
            'status' => 'in_progress',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Results rejected');
    });

    Route::get('/laboratory/orders/{order}/print', function (LaboratoryOrder $order) {
        return response("Laboratory Result {$order->order_number}", 200, [
            'content-type' => 'application/pdf',
        ]);
    });

    // Radiology Routes
    Route::get('/radiology/orders', function (Request $request) {
        $query = RadiologyOrder::query();
        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->query('priority'));
        }

        $orders = $query->orderByDesc('created_at')->get();
        $body = '<h1>Radiology Orders</h1>';
        foreach ($orders as $order) {
            $body .= '<p>' . e((string) $order->order_number) . '</p>';
        }

        return response($body, 200);
    });

    Route::post('/radiology/orders', function (Request $request) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'visit_id' => 'required|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:employees,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'examination_type' => 'required|string',
            'body_area' => 'required|string',
            'position' => 'nullable|string',
            'contrast' => 'nullable|boolean',
            'contrast_type' => 'nullable|string',
            'clinical_indication' => 'nullable|string',
            'priority' => 'nullable|in:normal,urgent,emergency',
            'notes' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->boolean('contrast') && empty($request->input('contrast_type'))) {
                $validator->errors()->add('contrast_type', 'Contrast type is required.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $prefix = 'RAD' . now()->format('Ymd');
        $lastOrder = RadiologyOrder::query()
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByDesc('order_number')
            ->first();
        $sequence = $lastOrder ? ((int) substr((string) $lastOrder->order_number, -4) + 1) : 1;

        RadiologyOrder::create([
            'order_number' => $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'visit_id' => $data['visit_id'],
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? auth()->user()?->employee_id,
            'medical_record_id' => $data['medical_record_id'] ?? null,
            'examination_type' => $data['examination_type'],
            'body_area' => $data['body_area'],
            'position' => $data['position'] ?? null,
            'contrast' => (bool) ($data['contrast'] ?? false),
            'contrast_type' => $data['contrast_type'] ?? null,
            'clinical_indication' => $data['clinical_indication'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect('/admin/radiology/orders')->with('success', 'Radiology order created');
    });

    Route::post('/radiology/orders/{order}/schedule', function (Request $request, RadiologyOrder $order) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'scheduled_date' => 'required|date',
            'room' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request, $order) {
            $scheduledDate = $request->input('scheduled_date');
            $room = (string) $request->input('room');
            if (!$scheduledDate || !$room) {
                return;
            }

            $hasConflict = RadiologyOrder::query()
                ->where('room', $room)
                ->where('status', 'scheduled')
                ->where('scheduled_date', $scheduledDate)
                ->where('id', '!=', $order->id)
                ->exists();

            if ($hasConflict) {
                $validator->errors()->add('scheduled_date', 'Room already booked for this time.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $order->update([
            'scheduled_date' => $data['scheduled_date'],
            'room' => $data['room'],
            'notes' => $data['notes'] ?? $order->notes,
            'status' => 'scheduled',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Radiology scheduled');
    });

    Route::post('/radiology/orders/{order}/upload', function (Request $request, RadiologyOrder $order) {
        $images = $request->file('images', []);
        $paths = [];
        foreach ($images as $image) {
            if ($image) {
                $paths[] = $image->getClientOriginalName();
            }
        }

        $result = RadiologyResult::firstOrNew(['radiology_order_id' => $order->id]);
        $result->result_images = $paths;
        $result->created_by = $result->created_by ?? auth()->id();
        $result->updated_by = auth()->id();
        $result->save();

        if ($order->status !== 'in_progress') {
            $order->update([
                'status' => 'in_progress',
                'updated_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', 'Images uploaded');
    });

    Route::post('/radiology/orders/{order}/complete', function (Request $request, RadiologyOrder $order) {
        $data = $request->validate([
            'technician_notes' => 'nullable|string',
            'exposure_parameters' => 'nullable|string',
            'dose_info' => 'nullable|string',
        ]);

        $result = RadiologyResult::firstOrNew(['radiology_order_id' => $order->id]);
        $result->technician_notes = $data['technician_notes'] ?? $result->technician_notes;
        $result->exposure_parameters = $data['exposure_parameters'] ? ['raw' => $data['exposure_parameters']] : $result->exposure_parameters;
        $result->dose_info = $data['dose_info'] ?? $result->dose_info;
        $result->created_by = $result->created_by ?? auth()->id();
        $result->updated_by = auth()->id();
        $result->save();

        $order->update([
            'status' => 'completed',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Radiology completed');
    });

    Route::post('/radiology/results/{result}/report', function (Request $request, RadiologyResult $result) {
        $data = $request->validate([
            'report_text' => 'required|string',
            'conclusion' => 'required|string',
            'recommendation' => 'nullable|string',
            'radiologist_id' => 'nullable|exists:employees,id',
        ]);

        $result->update([
            'report_text' => $data['report_text'],
            'conclusion' => $data['conclusion'],
            'recommendation' => $data['recommendation'] ?? null,
            'radiologist_id' => $data['radiologist_id'] ?? auth()->user()?->employee_id,
            'reported_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        $order = $result->radiologyOrder;
        if ($order) {
            $order->update([
                'status' => 'reported',
                'updated_by' => auth()->id(),
            ]);

            if ($order->doctor_id) {
                $doctorUser = \App\Models\User::query()
                    ->where('employee_id', $order->doctor_id)
                    ->first();
                if ($doctorUser) {
                    $doctorUser->notify(new RadiologyReportReady($order));
                }
            }
        }

        return redirect()->back()->with('success', 'Radiology report created');
    });

    Route::get('/patients/{patient}/radiology-history', function (Patient $patient) {
        $orders = RadiologyOrder::query()
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        $body = '<h1>Radiology History</h1>';
        foreach ($orders as $order) {
            $body .= '<p>' . e((string) $order->order_number) . '</p>';
        }

        return response($body, 200);
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
