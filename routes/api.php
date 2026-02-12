<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\InpatientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LaboratoryController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\RadiologyController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BpjsController;
use App\Http\Controllers\Api\SurgeryController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->name('api.auth.reset-password');

// Protected routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {

    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('api.auth.logout-all');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/change-password', [AuthController::class, 'changePassword'])->name('api.auth.change-password');

    // Patients
    Route::apiResource('patients', PatientController::class)->names([
        'index' => 'api.patients.index',
        'store' => 'api.patients.store',
        'show' => 'api.patients.show',
        'update' => 'api.patients.update',
        'destroy' => 'api.patients.destroy',
    ]);
    Route::get('/patients/search', [PatientController::class, 'search'])->name('api.patients.search');
    Route::get('/patients/{patient}/visits', [PatientController::class, 'visits'])->name('api.patients.visits');
    Route::get('/patients/{patient}/medical-records', [PatientController::class, 'medicalRecords'])->name('api.patients.medical-records');

    // Visits
    Route::apiResource('visits', VisitController::class)->names([
        'index' => 'api.visits.index',
        'store' => 'api.visits.store',
        'show' => 'api.visits.show',
        'update' => 'api.visits.update',
        'destroy' => 'api.visits.destroy',
    ]);
    Route::get('/visits/today', [VisitController::class, 'today'])->name('api.visits.today');
    Route::post('/visits/{visit}/checkin', [VisitController::class, 'checkin'])->name('api.visits.checkin');
    Route::post('/visits/{visit}/checkout', [VisitController::class, 'checkout'])->name('api.visits.checkout');
    Route::put('/visits/{visit}/status', [VisitController::class, 'updateStatus'])->name('api.visits.update-status');

    // Medical Records
    Route::apiResource('medical-records', MedicalRecordController::class)->names([
        'index' => 'api.medical-records.index',
        'store' => 'api.medical-records.store',
        'show' => 'api.medical-records.show',
        'update' => 'api.medical-records.update',
        'destroy' => 'api.medical-records.destroy',
    ]);
    Route::post('/medical-records/{record}/finalize', [MedicalRecordController::class, 'finalize'])->name('api.medical-records.finalize');
    Route::get('/medical-records/{record}/cppts', [MedicalRecordController::class, 'cppts'])->name('api.medical-records.cppts');
    Route::get('/medical-records/{record}/prescriptions', [MedicalRecordController::class, 'prescriptions'])->name('api.medical-records.prescriptions');

    // Prescriptions
    Route::apiResource('prescriptions', PrescriptionController::class)->names([
        'index' => 'api.prescriptions.index',
        'store' => 'api.prescriptions.store',
        'show' => 'api.prescriptions.show',
        'update' => 'api.prescriptions.update',
        'destroy' => 'api.prescriptions.destroy',
    ]);
    Route::put('/prescriptions/{prescription}/verify', [PrescriptionController::class, 'verify'])->name('api.prescriptions.verify');
    Route::put('/prescriptions/{prescription}/process', [PrescriptionController::class, 'process'])->name('api.prescriptions.process');
    Route::put('/prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense'])->name('api.prescriptions.dispense');

    // Invoices & Payments
    Route::apiResource('invoices', InvoiceController::class)->names([
        'index' => 'api.invoices.index',
        'store' => 'api.invoices.store',
        'show' => 'api.invoices.show',
        'update' => 'api.invoices.update',
        'destroy' => 'api.invoices.destroy',
    ]);
    Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('api.invoices.pay');
    Route::get('/invoices/{invoice}/payments', [InvoiceController::class, 'payments'])->name('api.invoices.payments');

    // Queue Management
    Route::get('/queues', [QueueController::class, 'index'])->name('api.queues.index');
    Route::get('/queues/display', [QueueController::class, 'display'])->name('api.queues.display');
    Route::post('/queues/{queue}/call', [QueueController::class, 'call'])->name('api.queues.call');
    Route::post('/queues/{queue}/skip', [QueueController::class, 'skip'])->name('api.queues.skip');
    Route::post('/queues/{queue}/complete', [QueueController::class, 'complete'])->name('api.queues.complete');
    Route::get('/queues/stats', [QueueController::class, 'stats'])->name('api.queues.stats');

    // Rooms & Beds
    Route::get('/rooms', [RoomController::class, 'index'])->name('api.rooms.index');
    Route::get('/rooms/{room}/beds', [RoomController::class, 'beds'])->name('api.rooms.beds');
    Route::get('/rooms/occupancy', [RoomController::class, 'occupancy'])->name('api.rooms.occupancy');
    Route::get('/beds/available', [RoomController::class, 'availableBeds'])->name('api.beds.available');

    // Inpatient
    Route::get('/inpatients', [InpatientController::class, 'index'])->name('api.inpatients.index');
    Route::post('/inpatients/admit', [InpatientController::class, 'admit'])->name('api.inpatients.admit');
    Route::post('/inpatients/{inpatient}/transfer', [InpatientController::class, 'transfer'])->name('api.inpatients.transfer');
    Route::post('/inpatients/{inpatient}/discharge', [InpatientController::class, 'discharge'])->name('api.inpatients.discharge');
    Route::get('/inpatients/{inpatient}/bill', [InpatientController::class, 'bill'])->name('api.inpatients.bill');

    // BPJS Integration
    Route::prefix('bpjs')->name('api.bpjs.')->group(function (): void {
        Route::get('/peserta/{nik}', [BpjsController::class, 'peserta'])->name('peserta');
        Route::post('/sep', [BpjsController::class, 'createSep'])->name('sep.create');
        Route::get('/sep/{sepNumber}', [BpjsController::class, 'getSep'])->name('sep.show');
        Route::delete('/sep/{sepNumber}', [BpjsController::class, 'deleteSep'])->name('sep.delete');
        Route::get('/rujukan/{noRujukan}', [BpjsController::class, 'rujukan'])->name('rujukan');
    });

    // Reports
    Route::prefix('reports')->name('api.reports.')->group(function (): void {
        Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
        Route::get('/rl/3', [ReportController::class, 'rl3'])->name('rl3');
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/top-diseases', [ReportController::class, 'topDiseases'])->name('top-diseases');
    });

    // Laboratory
    Route::apiResource('lab/orders', LaboratoryController::class)->names([
        'index' => 'api.lab.orders.index',
        'store' => 'api.lab.orders.store',
        'show' => 'api.lab.orders.show',
        'update' => 'api.lab.orders.update',
        'destroy' => 'api.lab.orders.destroy',
    ]);
    Route::put('/lab/orders/{order}/results', [LaboratoryController::class, 'results'])->name('api.lab.orders.results');
    Route::put('/lab/orders/{order}/validate', [LaboratoryController::class, 'validateOrder'])->name('api.lab.orders.validate');

    // Radiology
    Route::apiResource('radiology/orders', RadiologyController::class)->names([
        'index' => 'api.radiology.orders.index',
        'store' => 'api.radiology.orders.store',
        'show' => 'api.radiology.orders.show',
        'update' => 'api.radiology.orders.update',
        'destroy' => 'api.radiology.orders.destroy',
    ]);
    Route::post('/radiology/orders/{order}/results', [RadiologyController::class, 'results'])->name('api.radiology.orders.results');
    Route::post('/radiology/orders/{order}/upload', [RadiologyController::class, 'upload'])->name('api.radiology.orders.upload');

    // Surgery
    Route::apiResource('surgeries', SurgeryController::class)->names([
        'index' => 'api.surgeries.index',
        'store' => 'api.surgeries.store',
        'show' => 'api.surgeries.show',
        'update' => 'api.surgeries.update',
        'destroy' => 'api.surgeries.destroy',
    ]);
    Route::put('/surgeries/{surgery}/start', [SurgeryController::class, 'start'])->name('api.surgeries.start');
    Route::put('/surgeries/{surgery}/complete', [SurgeryController::class, 'complete'])->name('api.surgeries.complete');
    Route::put('/surgeries/{surgery}/cancel', [SurgeryController::class, 'cancel'])->name('api.surgeries.cancel');

    // Exports
    Route::post('/exports/patients', [ExportController::class, 'patients'])->name('api.exports.patients');
    Route::post('/exports/visits', [ExportController::class, 'visits'])->name('api.exports.visits');
    Route::post('/exports/financial', [ExportController::class, 'financial'])->name('api.exports.financial');

    // Imports
    Route::post('/imports/patients', [ImportController::class, 'patients'])->name('api.imports.patients');
    Route::post('/imports/medicines', [ImportController::class, 'medicines'])->name('api.imports.medicines');
    Route::post('/imports/employees', [ImportController::class, 'employees'])->name('api.imports.employees');
});
