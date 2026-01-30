<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TenantVoucherController;
use App\Http\Controllers\VoucherController;

use App\Http\Controllers\Api\RvmMachineController;
use App\Http\Controllers\Api\EdgeDeviceController;
use App\Http\Controllers\Api\LogController;

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\RedemptionController;
use App\Http\Controllers\Api\TechnicianController;
use App\Http\Controllers\Api\CVController;
use App\Http\Controllers\Api\AssignmentController;

// Kiosk API Controllers
use App\Http\Controllers\Api\Kiosk\SessionController as KioskSessionController;
use App\Http\Controllers\Api\Kiosk\AuthController as KioskAuthController;
use App\Http\Controllers\Api\Kiosk\MaintenanceController as KioskMaintenanceController;
use App\Http\Controllers\Api\Kiosk\LogController as KioskLogController;
use App\Http\Controllers\Api\Kiosk\ConfigController as KioskConfigController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Routes
Route::post('/v1/register', [AuthController::class, 'register']);
Route::post('/v1/login', [AuthController::class, 'login']);
Route::get('/v1/vouchers', [VoucherController::class, 'apiIndex']);
Route::post('/v1/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/v1/reset-password', [AuthController::class, 'resetPassword']);

// RVM Machines routes moved to protected group (see below)

// Protected Admin User Management (Role: Admin, Super Admin)
Route::middleware('auth:sanctum')->prefix('v1/admin')->middleware('role:admin,super_admin')->group(function () {
    // Write operations remain Restricted to Admin/Super Admin

    Route::post('/users', [UserController::class, 'createUser']);
    Route::put('/users/{id}', [UserController::class, 'updateUserAdmin']);

    // Secure operations with password verification - MUST be before {id} route
    Route::post('/verify-password', [UserController::class, 'verifyPassword']);
    Route::delete('/users/bulk', [UserController::class, 'deleteMultipleUsers']);

    // Single user delete (with {id} parameter) - MUST be after /bulk route
    Route::delete('/users/{id}', [UserController::class, 'deleteUser']);
});

// Management Routes (Role: Admin, Super Admin, Teknisi, Operator)
Route::middleware('auth:sanctum')->prefix('v1/admin')->middleware('role:admin,super_admin,teknisi,operator')->group(function () {
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::get('/users/stats', [UserController::class, 'getGlobalStats']);

    // Status toggle - bulk route MUST be before {id} route
    Route::patch('/users/status/bulk', [UserController::class, 'toggleMultipleStatus']);
    Route::patch('/users/{id}/status', [UserController::class, 'toggleStatus']);

    Route::get('/users/{id}/stats', [UserController::class, 'getUserStats']);

    // Assignment Management (RVM Installation Assignments)
    Route::apiResource('assignments', AssignmentController::class);
    Route::patch('/assignments/{id}/status', [AssignmentController::class, 'updateStatus']);
});

// Protected Routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Auth & User Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/change-password', [UserController::class, 'changePassword']);
    Route::post('/user/upload-photo', [UserController::class, 'uploadPhoto']);
    Route::get('/user/balance', [UserController::class, 'balance']);

    // Tenant Management (Role: Tenant, Admin, Super Admin can also access)
    Route::middleware('role:tenant,admin,super_admin')->prefix('tenant')->group(function () {
        Route::get('/vouchers', [TenantVoucherController::class, 'index']);
        Route::post('/vouchers', [TenantVoucherController::class, 'store']);
        Route::put('/vouchers/{id}', [TenantVoucherController::class, 'update']);
        Route::delete('/vouchers/{id}', [TenantVoucherController::class, 'destroy']);
        Route::post('/redemption/validate', [RedemptionController::class, 'validateVoucher']);
    });

    // RVM Management (Role-based access with assignment filtering)
    Route::post('/rvm-machines/bulk-delete', [RvmMachineController::class, 'destroyBulk']); // Must be before apiResource
    Route::apiResource('rvm-machines', RvmMachineController::class);
    Route::post('/rvm-machines/{id}/assign', [RvmMachineController::class, 'assignMachine']);
    Route::get('/rvm-machines/{id}/assignments', [RvmMachineController::class, 'getAssignments']);
    Route::post('/rvm-machines/{id}/regenerate-api-key', [RvmMachineController::class, 'regenerateApiKey']);
    Route::get('/rvm-machines/{id}/credentials', [RvmMachineController::class, 'getCredentials']);

    // Edge Device & Telemetry (IoT)
    // Handshake endpoint - uses API key auth instead of user auth
    Route::post('/edge/handshake', [EdgeDeviceController::class, 'handshake'])
        ->withoutMiddleware('auth:sanctum')
        ->middleware('validate.rvm.apikey');

    Route::post('/edge/deposit', [EdgeDeviceController::class, 'deposit'])
        ->withoutMiddleware('auth:sanctum')
        ->middleware('validate.rvm.apikey');

    Route::post('/edge/sync-offline', [EdgeDeviceController::class, 'syncOffline'])
        ->withoutMiddleware('auth:sanctum')
        ->middleware('validate.rvm.apikey');

    Route::post('/edge/heartbeat', [EdgeDeviceController::class, 'heartbeatEdge'])
        ->withoutMiddleware('auth:sanctum')
        ->middleware('validate.rvm.apikey');
    
    
    Route::prefix('edge')->group(function () {
        Route::post('/register', [EdgeDeviceController::class, 'register']);
        Route::get('/model-sync', [EdgeDeviceController::class, 'modelSync']);
        Route::post('/update-location', [EdgeDeviceController::class, 'updateLocation']);
        Route::post('/upload-image', [EdgeDeviceController::class, 'uploadImage']);
        Route::get('/devices', [EdgeDeviceController::class, 'index']); // List all devices
        Route::get('/devices/trashed', [EdgeDeviceController::class, 'trashed']); // List deleted devices
        Route::get('/devices/{id}', [EdgeDeviceController::class, 'show']); // Get single device
        Route::put('/devices/{id}', [EdgeDeviceController::class, 'update']); // Update device
        Route::delete('/devices/{id}', [EdgeDeviceController::class, 'destroy']); // Soft delete
        Route::post('/devices/{id}/restore', [EdgeDeviceController::class, 'restore']); // Restore from trash
        Route::post('/devices/{id}/command', [EdgeDeviceController::class, 'sendCommand']); // Manual command (Pull/Restart)
        Route::post('/devices/{id}/regenerate-key', [EdgeDeviceController::class, 'regenerateApiKey']); // New API key
        Route::get('/download-config/{deviceId}', [EdgeDeviceController::class, 'downloadConfig']); // Download config
    });
    Route::post('/devices/{id}/telemetry', [EdgeDeviceController::class, 'telemetry']);
    Route::post('/devices/{id}/heartbeat', [EdgeDeviceController::class, 'heartbeat']);

    // Transactions (User Side)
    Route::prefix('transactions')->group(function () {
        Route::post('/session', [TransactionController::class, 'createSession']);
        Route::post('/start', [TransactionController::class, 'start']);
        Route::post('/item', [TransactionController::class, 'depositItem']);
        Route::post('/commit', [TransactionController::class, 'commit']);
        Route::post('/cancel', [TransactionController::class, 'cancel']);
        Route::get('/history', [TransactionController::class, 'history']);
        Route::get('/active', [TransactionController::class, 'getActiveSession']);
        Route::get('/{id}', [TransactionController::class, 'show']);
    });

    // Redemption (User Side)
    Route::post('/redemption/redeem', [RedemptionController::class, 'redeem']);
    Route::get('/redemption/vouchers', [RedemptionController::class, 'getUserVouchers']);
    Route::get('/redemption/voucher/{code}', [RedemptionController::class, 'getVoucherDetail']);

    // CV Integration (RVM-CV Callbacks)
    Route::prefix('cv')->group(function () {
        Route::post('/upload-model', [CVController::class, 'uploadModel']);
        Route::post('/training-complete', [CVController::class, 'trainingComplete']);
        Route::get('/datasets/{id}', [CVController::class, 'getDataset']);
        Route::get('/download-model/{versionOrHash}', [CVController::class, 'downloadModel']);
        Route::post('/playground-inference', [CVController::class, 'playgroundInference']);
        Route::get('/training-jobs', [CVController::class, 'getTrainingJobs']); // Added for dashboard
        Route::get('/models', [CVController::class, 'getModels']); // Added for dashboard
    });

    // Model Download for Edge Devices
    Route::get('/edge/download-model/{hash}', [CVController::class, 'downloadModel']);

    // Technician & Maintenance (Role: Technician)
    Route::prefix('technician')->group(function () {
        Route::get('/assignments', [TechnicianController::class, 'assignments']);
        Route::post('/generate-pin', [TechnicianController::class, 'generatePin']);
        // Route::post('/validate-pin', [TechnicianController::class, 'validatePin']); // This should be called by Machine, not Technician App? 
        // Actually Machine calls validate-pin, so it might need device token or be public with serial number check.
        // For now, let's keep it here but Machine might need specific auth.
    });
    // Public/Device Route for PIN Validation (or secured by Device Token)
    Route::post('/technician/validate-pin', [TechnicianController::class, 'validatePin']);

    // System Logs (Role: Admin/Operator/Teknisi - with role check in controller)
    Route::get('/logs/stats', [LogController::class, 'stats']);
    Route::get('/logs/export', [LogController::class, 'export']); // Export route
    Route::get('/logs', [LogController::class, 'index']);

    // Technician Assignments (Hak Akses RVM) - Complete CRUD
    Route::get('/technician-assignments', [\App\Http\Controllers\Api\TechnicianAssignmentController::class, 'index']);
    Route::post('/technician-assignments', [\App\Http\Controllers\Api\TechnicianAssignmentController::class, 'store']);
    Route::get('/technician-assignments/{id}', [\App\Http\Controllers\Api\TechnicianAssignmentController::class, 'show']);
    Route::put('/technician-assignments/{id}', [\App\Http\Controllers\Api\TechnicianAssignmentController::class, 'update']);
    Route::delete('/technician-assignments/{id}', [\App\Http\Controllers\Api\TechnicianAssignmentController::class, 'destroy']);
    Route::post('/technician-assignments/{id}/generate-pin', [\App\Http\Controllers\Api\TechnicianAssignmentController::class, 'generatePin']);
    Route::get('/technician-assignments/by-rvm/{rvmId}', [\App\Http\Controllers\Api\TechnicianAssignmentController::class, 'getByRvm']);

    // Maintenance Tickets - Complete CRUD
    Route::get('/maintenance-tickets', [\App\Http\Controllers\Api\MaintenanceTicketController::class, 'index']);
    Route::post('/maintenance-tickets', [\App\Http\Controllers\Api\MaintenanceTicketController::class, 'store']);
    Route::get('/maintenance-tickets/{id}', [\App\Http\Controllers\Api\MaintenanceTicketController::class, 'show']);
    Route::put('/maintenance-tickets/{id}', [\App\Http\Controllers\Api\MaintenanceTicketController::class, 'update']);
    Route::delete('/maintenance-tickets/{id}', [\App\Http\Controllers\Api\MaintenanceTicketController::class, 'destroy']);
    Route::patch('/maintenance-tickets/{id}/status', [\App\Http\Controllers\Api\MaintenanceTicketController::class, 'updateStatus']);
});

// =============================================================================
// Kiosk API Routes (Machine-Authenticated, No User Login Required)
// =============================================================================
// These endpoints are consumed by the RVM-UI Kiosk touchscreen interface.
// Authentication is based on machine UUID validation via header.
Route::prefix('v1/kiosk')->middleware(['throttle:60,1'])->group(function () {
    // Session Management
    Route::get('/session/token', [KioskSessionController::class, 'getToken']);
    Route::post('/session/guest', [KioskSessionController::class, 'activateGuest']);
    
    // Technician Authentication (PIN-based)
    Route::post('/auth/pin', [KioskAuthController::class, 'verifyPin']);
    
    // Maintenance Panel (Requires valid technician session)
    Route::post('/maintenance/command', [KioskMaintenanceController::class, 'sendCommand']);
    Route::get('/maintenance/status', [KioskMaintenanceController::class, 'getStatus']);
    
    // Log Viewer (Scoped to machine only)
    Route::get('/logs', [KioskLogController::class, 'index']);
    
    // Configuration
    Route::get('/config', [KioskConfigController::class, 'getConfig']);
    Route::post('/config/theme', [KioskConfigController::class, 'updateTheme']);
});
