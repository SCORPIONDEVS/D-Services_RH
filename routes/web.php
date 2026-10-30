<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Funcionários
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('guia-clinica', [EmployeeController::class, 'clinicIndex'])->name('clinic.index');
    Route::get('employees/labels', [EmployeeController::class, 'labels'])->name('employees.labels');
    Route::get('employees/{employee}/clinic-guide', [EmployeeController::class, 'clinicGuide'])->name('employees.clinic');
    Route::get('employees/{employee}/print', [EmployeeController::class, 'print'])->name('employees.print');
    Route::resource('employees', EmployeeController::class);

    // Benefícios VT/VR
    Route::get('benefits', [BenefitController::class, 'index'])->name('benefits.index');
    Route::post('benefits/generate', [BenefitController::class, 'generate'])->name('benefits.generate');
    Route::get('benefits/export', [BenefitController::class, 'export'])->name('benefits.export');
    Route::get('benefits/{entry}/receipt', [BenefitController::class, 'receipt'])->name('benefits.receipt');
    Route::patch('benefits/{entry}', [BenefitController::class, 'update'])->name('benefits.update');
    Route::delete('benefits/{entry}', [BenefitController::class, 'destroy'])->name('benefits.destroy');

    // Escalas
    Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('schedules/visao', [ScheduleController::class, 'overview'])->name('schedules.overview');
    Route::get('schedules/patterns', [ScheduleController::class, 'patterns'])->name('schedules.patterns');
    Route::post('schedules/patterns/postos', [ScheduleController::class, 'fillPostos'])->name('schedules.patterns.postos');
    Route::post('schedules/patterns/rotacao/importar', [ScheduleController::class, 'importRotation'])->name('schedules.rotation.import');
    Route::post('schedules/patterns/rotacao/gerar', [ScheduleController::class, 'generateRotation'])->name('schedules.rotation.generate');
    Route::patch('schedules/patterns/{pattern}', [ScheduleController::class, 'updatePattern'])->name('schedules.patterns.update');
    Route::patch('schedules/{entry}', [ScheduleController::class, 'update'])->name('schedules.update');

    Route::get('api/cids/{code}', [ApiController::class, 'cid'])->name('api.cid');

    // Módulos CRUD genéricos (config/modules.php)
    $modules = implode('|', array_map('preg_quote', array_keys(config('modules'))));
    Route::prefix('m/{module}')->where(['module' => $modules, 'id' => '[0-9]+'])->group(function () {
        Route::get('/', [ModuleController::class, 'index'])->name('m.index');
        Route::get('/create', [ModuleController::class, 'create'])->name('m.create');
        Route::post('/', [ModuleController::class, 'store'])->name('m.store');
        Route::get('/{id}/edit', [ModuleController::class, 'edit'])->name('m.edit');
        Route::put('/{id}', [ModuleController::class, 'update'])->name('m.update');
        Route::delete('/{id}', [ModuleController::class, 'destroy'])->name('m.destroy');
    });
});
