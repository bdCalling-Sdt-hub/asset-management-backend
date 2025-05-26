<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MaintainanceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Organization\AssetController;
use App\Http\Controllers\Organization\FAQController;
use App\Http\Controllers\Organization\ReportController;
use App\Http\Controllers\Organization\SettingController;
use App\Http\Controllers\Statistic\LocationEmployee;
use App\Http\Controllers\Statistic\Organization;
use App\Http\Controllers\Statistic\SuperAdmin;
use App\Http\Controllers\Statistic\SupportAgent;
use App\Http\Controllers\SupportAgent\InspectionSheetController;
use App\Http\Controllers\SupportAgent\JobCardController;
use App\Http\Controllers\User\AdminController;
use App\Http\Controllers\User\LocationController;
use App\Http\Controllers\User\OrganizationController;
use App\Http\Controllers\User\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function ($router) {
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('verify', [AuthController::class, 'verify']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('social-login', [AuthController::class, 'socialLogin']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::middleware('auth:api')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('profile-update', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('location', [LocationController::class, 'Address']);
        Route::get('get-address/{id}', [LocationController::class, 'getAddress']);
    });
    Route::post('logout', [AuthController::class, 'logout']);

});
Route::middleware(['auth:api', 'super_admin'])->group(function () {

    //statistics
    Route::get('super-admin-overview', [SuperAdmin::class, 'overview']);
    Route::get('super-admin-chart', [SuperAdmin::class, 'chartsuperAdmin']);

    Route::get('ticket-activity-super', [SuperAdmin::class, 'activityTicket']);
    //inspection sheet
    Route::get('inspection-statistics', [SuperAdmin::class, 'statisticsInspectionSheet']);
    //job card
    Route::get('card-statistics', [SuperAdmin::class, 'statisticsJobCard']);

    //add and update organization
    Route::post('organization-add', [AdminController::class, 'addOrganization']);
    Route::post('organization-update/{id}', [AdminController::class, 'updateOrganization']);

    Route::post('third-party-add', [AdminController::class, 'addThirdParty']);
    Route::post('third-party-update/{id}', [AdminController::class, 'updateThirdParty']);

    //add and update location employee
    Route::post('employee-add', [AdminController::class, 'addEmployee']);
    Route::post('employee-update/{id}', [AdminController::class, 'updateEmployee']);

    //add and update support agent
    Route::post('agent-add', [AdminController::class, 'addAgent']);
    Route::post('agent-update/{id}', [AdminController::class, 'updateSAgent']);

    //add and update technician
    Route::post('add-technician', [AdminController::class, 'technicianAdd']);
    Route::post('update-technician/{id}', [AdminController::class, 'technicianUpdate']);

    Route::get('delete-user/{id}', [AdminController::class, 'deleteUser']);
    Route::get('soft-delete-user', [AdminController::class, 'SoftDeletedUsers']);

    //assetlist
    Route::get('get-asset-list', [AssetController::class, 'assetListAdmin']);

    //setting
    Route::post('create-setting', [SettingController::class, 'createSetting']);

    //faq
    Route::post('create-faq', [FAQController::class, 'createFaq']);
    Route::post('update-faq/{id}', [FAQController::class, 'updateFaq']);
    Route::get('faq_list', [FAQController::class, 'listFaq'])->withoutMiddleware(['auth:api', 'super_admin']);
    Route::delete('delete-faq/{id}', [FAQController::class, 'deleteFaq']);

    //report
    // Route::post('create-report', [ReportController::class, 'createReport']);
    // Route::get('list-report', [ReportController::class, 'listReports']);
    // Route::get('details-report/{id}', [ReportController::class, 'detailsReports']);

    Route::get('make-report', [ReportController::class, 'makeReport']);
});

Route::middleware(['auth:api', 'user'])->group(function () {

    //ticket
    Route::post('create-ticket', [TicketController::class, 'createTicket']);
    // Route::get('ticket-list', [TicketController::class, 'ticketList']);
    Route::get('ticket-details/{id}', [TicketController::class, 'ticketDetails']);
    Route::delete('ticket-delete/{id}', [TicketController::class, 'deleteTicket']);
    Route::get('qr-scan/{qrcode}', [AssetController::class, 'qrScan']);

});

Route::middleware(['auth:api', 'common'])->group(function () {

    //update ticket
    Route::post('update-ticket/{id}', [TicketController::class, 'updateTicket']);
    Route::get('ticket-details/{id}', [TicketController::class, 'ticketDetails']);
    Route::get('ticket-list', [TicketController::class, 'ticketList']);

    //message routes
    Route::post('send-message', [MessageController::class, 'sendMessage']);
    Route::get('get-message', [MessageController::class, 'getMessage']);
    Route::get('mark-read', [MessageController::class, 'markRead']);
    Route::get('search-new-user', [MessageController::class, 'searchNewUser']);
    Route::get('chat-list', [MessageController::class, 'chatList']);

    Route::get('settings', [SettingController::class, 'listSetting']);
    //faq list
    Route::get('faq-list', [FAQController::class, 'listFaq']);

});
Route::middleware(['auth:api', 'super_admin.third_party.organization'])->group(function () {

    //add and update location employee
    Route::post('location-employee-add', [OrganizationController::class, 'addLocationEmployee']);
    Route::post('location-employee-update/{id}', [OrganizationController::class, 'updateLocationEmployee']);

    //add and update support agent
    Route::post('support-agent-add', [OrganizationController::class, 'addSupportAgent']);
    Route::post('support-agent-update/{id}', [OrganizationController::class, 'updateSupportAgent']);

    //add and update technician
    Route::post('technician-add', [OrganizationController::class, 'addTechnician']);
    Route::post('technician-update/{id}', [OrganizationController::class, 'updateTechnician']);

    //just delete supportagent, location employee and technician
    Route::delete('user-delete/{id}', [OrganizationController::class, 'deleteSpecificUser']);
    Route::get('all-user', [AdminController::class, 'userList']);
    Route::get('user-details/{id}', [AdminController::class, 'userDetails']);
    Route::get('get-user-details/{id}', [OrganizationController::class, 'getuserDetails']);

    //asset route
    Route::post('create-asset', [AssetController::class, 'createAsset']);
    Route::post('update-asset/{id}', [AssetController::class, 'updateAsset']);
    Route::get('asset-list', [AssetController::class, 'assetList']);
    Route::get('asset-maturity/{id}', [AssetController::class, 'assetMaturity']);
    Route::get('asset-details/{id}', [AssetController::class, 'assetDetails']);
    Route::delete('delete-asset/{id}', [AssetController::class, 'deleteAsset']);

    Route::post('import-asset', [AssetController::class, 'importAssets']);
});

Route::middleware(['auth:api', 'super_admin.location_employee.organization'])->group(function () {
    // Route::get('technician', [MaintainanceController::class, 'technicianGet']);
    Route::get('asset', [MaintainanceController::class, 'assetGet']);
    Route::get('maintainance', [MaintainanceController::class, 'maintainanceGet']);

});

Route::middleware(['auth:api', 'location_employee'])->group(function () {
    Route::post('set-reminder', [MaintainanceController::class, 'setReminder']);
    Route::get('get-reminder', [MaintainanceController::class, 'getReminder']);
    Route::post('update-maintainance/{id}', [MaintainanceController::class, 'updateStatus']);
    Route::get('location-employee-dashboard', [LocationEmployee::class, 'dashboard']);
});

Route::middleware(['auth:api', 'support_agent'])->group(function () {

    //get ticket details
    Route::get('get-ticket-details/{id}', [TicketController::class, 'getTicketDetails']);
    //inspection sheet
    Route::post('create-inspection-sheet', [InspectionSheetController::class, 'createInspectionSheet']);
    //job card
    Route::post('create-job-card', [JobCardController::class, 'createJobCard']);
});
Route::middleware(['auth:api', 'support_agent.location_employee.technician.third_party'])->group(function () {

    //get ticket details
    Route::get('get-ticket-details/{id}', [TicketController::class, 'getTicketDetails']);
    //inspection sheet
    Route::post('update-inspection/{id}', [InspectionSheetController::class, 'updateInspectionSheet']);
    Route::delete('delete-inspection/{id}', [InspectionSheetController::class, 'deleteInspectionSheet']);

    Route::get('inspection-list', [InspectionSheetController::class, 'InspectionSheetList']);
    Route::get('inspection-details', [InspectionSheetController::class, 'InspectionSheetDetails']);

    //job card update list details
    Route::post('update-card/{id}', [JobCardController::class, 'updateJobCard']);
    Route::delete('delete-card/{id}', [JobCardController::class, 'deleteJobCard']);

    Route::get('card-list', [JobCardController::class, 'JobCardList']);
    Route::get('card-details/{id}', [JobCardController::class, 'detailsJobCard']);

});

Route::middleware(['auth:api', 'organization'])->group(function () {
    Route::get('organization-dashboard', [Organization::class, 'dashboard']);
    Route::get('organization-ticket-activity', [Organization::class, 'ticketActivity']);
    Route::get('inspaction-sheet-overview', [Organization::class, 'inspactionSheetOverview']);
    Route::get('job-card-overview', [Organization::class, 'jobCardOverview']);

    //report
    Route::post('add-report', [ReportController::class, 'addReport']);
    Route::get('get-report/{id}', [ReportController::class, 'reportDetails']);

});
Route::middleware(['auth:api', 'support_agent'])->group(function () {
    Route::get('support-agent-dashboard', [SupportAgent::class, 'chartSupportAgent']);
    Route::get('ticket-activity', [SupportAgent::class, 'activityTicket']);
    //inspection sheet
    Route::get('inspection-sheet-statistics', [SupportAgent::class, 'statisticsInspectionSheet']);
    //job card
    Route::get('job-card-statistics', [SupportAgent::class, 'statisticsJobCard']);
});
Route::middleware(['auth:api', 'location_employee'])->group(function () {
    Route::get('location-employee-dashboard', [LocationEmployee::class, 'dashboardLocationEmployee']);

});

// notification
Route::middleware(['auth:api', 'common'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'notifications'])->name('all_Notification');
    Route::post('mark-notification/{id}', [NotificationController::class, 'singleMark'])->name('singleMark');
    Route::post('mark-all-notification', [NotificationController::class, 'allMark'])->name('allMark');

    Route::get('get-organization', [OrganizationController::class, 'getOrganization']);
    Route::get('technician', [MaintainanceController::class, 'technicianGet']);
});
