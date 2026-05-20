<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ============================================
// PUBLIC ROUTES (No Authentication Required)
// ============================================

// Basic public pages
$routes->get('/', 'Home::index');
$routes->get('/contact', 'Home::contact'); 
$routes->get('/facilities', 'Home::facilities'); 
$routes->get('/event', 'Home::event'); 
$routes->get('/about', 'Home::about'); 

// Dynamic facility detail route (NEW - handles all facilities from database)
$routes->get('/facility/(:segment)', 'Home::facilityDetail/$1');

// Legacy routes - OLD hardcoded facility routes (kept for backward compatibility)
$routes->get('facilities/gymnasium', 'Home::gymnasium');
$routes->get('facilities/pearlmini', 'Home::pearlmini');
$routes->get('facilities/FunctionHall', 'Home::FunctionHall');
$routes->get('/facilities/classroom', 'Home::classroom');
$routes->get('facilities/Auditorium', 'Home::Auditorium');
$routes->get('facilities/Dormitory', 'Home::Dormitory');
$routes->get('facilities/staffhouse', 'Home::staffhouse');


// Authentication
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->post('register', 'Auth::register');
$routes->get('logout', 'Auth::logout');
$routes->get('unauthorized', 'Unauthorized::index');

// Google OAuth
$routes->get('google/login', 'Auth::googleLogin');
$routes->get('google/callback', 'Auth::googleCallback');

// Smart dashboard redirect based on role
$routes->get('dashboard', 'DashboardRouter::index', ['filter' => 'auth']);

// Public Guest Registration (no authentication required)
$routes->get('guest-registration/(:num)', 'GuestRegistration::index/$1');
$routes->post('api/guest-registration/register', 'Api\GuestApiController::publicRegister');

// Public facility API
$routes->get('api/facilities/(:any)/data', 'Api\BookingApiController::getFacilityData/$1');
$routes->get('api/facilities/list', 'FacilitiesController::getFacilitiesList');
$routes->get('api/facilities/student', 'FacilitiesController::getStudentFacilities');
$routes->get('api/facilities/external', 'FacilitiesController::getExternalFacilities');
$routes->get('api/facilities/data', 'FacilitiesController::getFacilityData');
$routes->get('api/facilities/data/(:segment)', 'FacilitiesController::getFacilityData/$1');
$routes->get('api/facilities/gallery/(:segment)', 'FacilitiesController::getFacilityGallery/$1');
$routes->get('api/facilities/image/(:segment)/(:any)', 'FacilitiesController::getGalleryImage/$1/$2');
$routes->delete('api/facilities/image/(:segment)/(:any)', 'FacilitiesController::deleteGalleryImage/$1/$2');
$routes->get('api/addons', 'FacilitiesController::getAddons');
$routes->get('api/equipment', 'FacilitiesController::getEquipment');
$routes->post('api/bookings', 'FacilitiesController::createBooking');
$routes->post('api/bookings/checkDateConflict', 'Api\BookingApiController::checkDateConflict');
$routes->post('api/bookings/equipment-availability', 'Api\BookingApiController::equipmentAvailability');
$routes->get('api/bookings/equipment', 'Api\BookingApiController::getEquipment'); // PUBLIC endpoint for facility detail page

// ============================================
// ADMIN ROUTES (Requires Admin Role)
// ============================================

// Admin dashboard
$routes->get('/admin', 'Admin::index', ['filter' => 'auth']);
$routes->get('/admin/equipment', 'Admin::equipment', ['filter' => 'auth']);
$routes->get('/admin/internal', 'Admin::internal', ['filter' => 'auth']);
$routes->get('/admin/external', 'Admin::external', ['filter' => 'auth']);
$routes->get('/admin/events', 'Admin::events', ['filter' => 'auth']);
$routes->get('/admin/calendar-debug', 'Admin::calendarDebug', ['filter' => 'auth']); // Debug tool
$routes->get('/admin/facilities-management', 'Admin::facilitiesManagement', ['filter' => 'auth']);
$routes->get('/admin/attendance', 'Admin::attendance', ['filter' => 'auth']);

// Apply role filter to admin routes  
$routes->group('admin', ['filter' => 'role'], function($routes) {
    $routes->get('/', 'Admin::index');
    $routes->get('dashboard', 'Admin::dashboard');
});

// Admin protected routes group
$routes->group('admin', ['filter' => 'auth'], function($routes) {


    // Equipment management
    $routes->get('equipment', 'Admin\Equipment::index');
    $routes->get('equipment/getEquipment', 'Admin\Equipment::getEquipment');
    $routes->get('equipment/getEquipmentDetails/(:num)', 'Admin\Equipment::getEquipmentDetails/$1');
    $routes->post('equipment/addEquipment', 'Admin\Equipment::addEquipment');
    $routes->post('equipment/updateEquipment', 'Admin\Equipment::updateEquipment');
    $routes->delete('equipment/deleteEquipment/(:num)', 'Admin\Equipment::deleteEquipment/$1');
    $routes->get('equipment/generateReport', 'Admin\Equipment::generateReport');

    // Plans management
    $routes->get('plans', 'Admin\Plans::index');
    $routes->get('plans/getPlans', 'Admin\Plans::getPlans');
    $routes->get('plans/getPlanDetails/(:num)', 'Admin\Plans::getPlanDetails/$1');
    $routes->post('plans/addPlan', 'Admin\Plans::addPlan');
    $routes->post('plans/updatePlan', 'Admin\Plans::updatePlan');
    $routes->delete('plans/deletePlan/(:num)', 'Admin\Plans::deletePlan/$1');
    $routes->get('plans/getFacilities', 'Admin\Plans::getFacilities');
    $routes->get('plans/getEquipmentList', 'Admin\Plans::getEquipmentList');
    $routes->get('plans/getAddons', 'Admin\Plans::getAddons');
    $routes->post('plans/addAddon', 'Admin\Plans::addAddon');
    $routes->post('plans/updateAddon', 'Admin\Plans::updateAddon');
    $routes->delete('plans/deleteAddon/(:num)', 'Admin\Plans::deleteAddon/$1');
    $routes->get('plans/getSettings', 'Admin\Plans::getSettings');
    $routes->post('plans/updateSettings', 'Admin\Plans::updateSettings');
    $routes->post('plans/updateFacilityRate', 'Admin\Plans::updateFacilityRate');

    // Facilities management
    $routes->get('facilities-management', 'Admin::facilitiesManagement');

    // Booking management
    $routes->get('booking-management', 'Admin::bookingManagement');
    $routes->get('bookings', 'Admin::bookings');
    
    // User management
    $routes->group('users', ['namespace' => 'App\Controllers\Admin'], function($routes) {
        $routes->get('/', 'Users::index');
        $routes->get('getUsers', 'Users::getUsers');
        $routes->post('add', 'Users::add');
        $routes->put('update/(:num)', 'Users::update/$1');
        $routes->delete('delete/(:num)', 'Users::delete/$1');
        $routes->get('view/(:num)', 'Users::view/$1');
    });
    
    // Template downloads
    $routes->group('templates', function($routes) {
        $routes->get('download/(:any)/(:num)', 'Admin\TemplateController::downloadTemplate/$1/$2');
        $routes->get('download/all/(:num)', 'Admin\TemplateController::downloadAllTemplates/$1');
    });

    // File Templates Management
    $routes->group('file-templates', function($routes) {
        $routes->get('/', 'Admin\FileTemplatesController::index');
        $routes->get('list', 'Admin\FileTemplatesController::getTemplates');
        $routes->post('get-template-config', 'Admin\FileTemplatesController::getTemplateConfig');
        $routes->post('update-signatories', 'Admin\FileTemplatesController::updateSignatories');
        $routes->post('get-signatories', 'Admin\FileTemplatesController::getSignatories');
        $routes->post('update', 'Admin\FileTemplatesController::updateTemplate');
        $routes->post('save-signatories', 'Admin\FileTemplatesController::saveSignatories');
        $routes->get('download/(:any)', 'Admin\FileTemplatesController::downloadTemplate/$1');
    });
});

// Admin Booking API
$routes->group('api/bookings', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], function($routes) {
    // Basic operations
    $routes->get('list', 'BookingApiController::list');
    $routes->get('detail/(:num)', 'BookingApiController::detail/$1');
    $routes->post('approve/(:num)', 'BookingApiController::approve/$1');
    $routes->post('decline/(:num)', 'BookingApiController::decline/$1');
    $routes->post('reschedule', 'BookingApiController::reschedule');
    $routes->delete('delete/(:num)', 'BookingApiController::delete/$1');
    $routes->get('equipment', 'BookingApiController::getEquipment');

    // File operations (admin)
    $routes->post('(:num)/upload', 'BookingApiController::uploadFiles/$1');
    $routes->get('(:num)/files', 'BookingApiController::getBookingFiles/$1');
    $routes->get('(:num)/files/(:num)/download', 'BookingApiController::downloadFile/$1/$2');
    $routes->delete('files/(:num)', 'BookingApiController::deleteFile/$1');

    // Guest management for bookings (user/student access)
    $routes->get('(:num)/guests', 'GuestApiController::getGuestsByBooking/$1');
    $routes->get('(:num)/export-attendance', 'GuestApiController::exportAttendanceByBooking/$1');
    $routes->get('export-all-attendance', 'GuestApiController::exportAllAttendance');

    // Statistics and search
    $routes->get('statistics', 'BookingApiController::getStatistics');
    $routes->post('search', 'BookingApiController::searchBookings');
    $routes->get('report', 'BookingApiController::generateReport');
    $routes->get('generateFacilityRentalReport', 'BookingApiController::generateFacilityRentalReport');
    
    // Document generation
    $routes->get('(:num)/billing-statement', 'BookingApiController::generateBillingStatement/$1');
    $routes->get('(:num)/equipment-request-form', 'BookingApiController::generateEquipmentRequestForm/$1');
    $routes->get('(:num)/moa', 'BookingApiController::generateMoa/$1');
    $routes->get('(:num)/faculty-evaluation', 'BookingApiController::generateFacultyEvaluation/$1');
    $routes->get('(:num)/inspection-evaluation', 'BookingApiController::generateInspectionEvaluation/$1');
    $routes->get('(:num)/order-of-payment', 'BookingApiController::generateOrderOfPayment/$1');
    $routes->get('(:num)/download-zip', 'BookingApiController::downloadSelectedZip/$1');
    
    // Download specific files
    $routes->get('bookings/(:num)/billing-statement', 'Api\BookingApiController::downloadBillingStatement/$1');
    $routes->get('bookings/(:num)/moa', 'Api\BookingApiController::downloadMoa/$1');
    $routes->get('bookings/(:num)/equipment-request-form', 'Api\BookingApiController::downloadEquipmentRequestForm/$1');
    $routes->get('bookings/(:num)/faculty-evaluation', 'Api\BookingApiController::downloadFacultyEvaluation/$1');
    $routes->get('bookings/(:num)/inspection-evaluation', 'Api\BookingApiController::downloadInspectionEvaluation/$1');
    $routes->get('bookings/(:num)/order-of-payment', 'Api\BookingApiController::downloadOrderOfPayment/$1');
});

// Evaluation File Download - Survey Controller (outside api namespace)
$routes->get('api/bookings/evaluation-file/(:any)', 'Survey::downloadEvaluationFile/$1', ['filter' => 'auth']);

// Admin Events API
$routes->group('api/events', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->get('/', 'EventsApiController::list');
    $routes->get('list', 'EventsApiController::list');
    $routes->get('calendar', 'EventsApiController::getCalendarEvents');
    $routes->get('upcoming', 'EventsApiController::getUpcomingEvents');
    $routes->get('statistics', 'EventsApiController::getEventStats');
    $routes->get('equipment-reports-summary', 'EventsApiController::equipmentReportsSummary');
    $routes->get('download-equipment-report', 'EventsApiController::downloadEquipmentReport');
    $routes->get('(:num)', 'EventsApiController::getEventDetails/$1');

});

// Facilities Management API
$routes->group('api/facilities', ['filter' => 'auth'], function($routes) {
    $routes->get('all', 'FacilitiesController::getAllFacilities');
    $routes->post('create', 'FacilitiesController::createFacility');
    $routes->post('update/(:num)', 'FacilitiesController::updateFacility/$1');
    $routes->delete('delete/(:num)', 'FacilitiesController::deleteFacility/$1');
});

// Add these routes in the admin API section

$routes->group('api/admin', ['filter' => 'auth'], function($routes) {
    $routes->get('dashboard-stats', 'Api\AdminApi::getDashboardStats');
    $routes->get('recent-bookings', 'Api\AdminApi::getRecentBookings');
    $routes->get('upcoming-events', 'Api\AdminApi::getUpcomingEvents');
    $routes->get('equipment-status', 'Api\AdminApi::getEquipmentStatus');
    $routes->get('facility-utilization', 'Api\AdminApi::getFacilityUtilization');
    $routes->get('api/facilities/(:any)/data', 'Api\BookingController::getFacilityData/$1');
    
    // Cancellation management
    $routes->get('cancellations/pending', 'Api\AdminApi::getPendingCancellations');
    $routes->post('cancellations/approve/(:num)', 'Api\AdminApi::approveCancellation/$1');
    $routes->post('cancellations/reject/(:num)', 'Api\AdminApi::rejectCancellation/$1');
});

// ============================================
// USER ROUTES (Requires Login, Any Role)
// ============================================


$routes->group('user', ['filter' => 'auth'], function($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('bookings', 'Dashboard::bookings');
    $routes->get('bookings/(:num)', 'Dashboard::bookingDetails/$1');
    $routes->get('profile', 'Dashboard::profile');
    $routes->get('history', 'Dashboard::history');
    $routes->get('attendance', 'Dashboard::attendance');
});


// Student dashboard routes
$routes->group('student', ['filter' => 'user'], function($routes) {
    $routes->get('dashboard', 'Student::index');
    $routes->get('bookings', 'Student::bookings');
    $routes->get('profile', 'Student::profile');
    $routes->get('history', 'Student::history');
    $routes->get('book', 'StudentController::book');
    $routes->get('attendance', 'Student::attendance');
});


$routes->get('api/profile/(:num)', 'Api\UserProfileApi::getProfile/$1');
$routes->put('api/profile/(:num)', 'Api\UserProfileApi::updateProfile/$1');
$routes->post('api/profile/change-password/(:num)', 'Api\UserProfileApi::changePassword/$1');
$routes->delete('api/profile/(:num)', 'Api\UserProfileApi::deleteAccount/$1');

$routes->group('api/user', ['filter' => 'userfilter'], function($routes) {
    $routes->get('dashboard-stats', 'Api\UserApi::getDashboardStats');
    $routes->get('recent-bookings', 'Api\UserApi::getRecentBookings');
});

// User Booking Operations - Add namespace and use 'userfilter'
$routes->group('user/bookings', [
    'namespace' => 'App\Controllers\Api'
    // filter removed temporarily
], function($routes) {
    $routes->get('list', 'UserApi::getUserBookings');
    $routes->get('details/(:num)', 'UserApi::getUserBookingDetails/$1');
    $routes->post('cancel/(:num)', 'UserApi::cancelBooking/$1');
    $routes->post('upload-receipt/(:num)', 'UserApi::uploadReceipt/$1');
    $routes->get('download-receipt/(:num)', 'UserApi::downloadReceipt/$1');
});


$routes->group('api/user', ['filter' => 'auth'], function($routes) {
    $routes->get('bookings', 'Api\UserApi::getUserBookings');
    $routes->get('bookings/(:num)', 'Api\UserApi::getUserBookingDetails/$1');
    $routes->post('bookings/cancel/(:num)', 'Api\UserApi::cancelBooking/$1');
    $routes->post('bookings/(:num)/cancel', 'Api\UserApi::cancelBooking/$1');
    $routes->post('bookings/(:num)/upload-receipt', 'Api\UserApi::uploadReceipt/$1');
    $routes->get('bookings/(:num)/download-receipt', 'Api\UserApi::downloadReceipt/$1');
    $routes->delete('bookings/(:num)/delete-receipt', 'Api\UserApi::deleteReceipt/$1');
    $routes->delete('bookings/(:num)/delete', 'Api\UserApi::deleteBooking/$1');
    $routes->post('bookings/(:num)/reschedule', 'Api\UserApi::rescheduleBooking/$1');
});


// Student API routes - WITH auth filter
$routes->group('api/student', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], function($routes) {
    $routes->post('bookings/create', 'StudentBookingApi::createStudentBooking');
    $routes->post('bookings/(:num)/upload', 'StudentBookingApi::uploadStudentDocuments/$1');
    $routes->post('bookings/(:num)/upload-files', 'StudentBookingApi::uploadStudentDocuments/$1');
    $routes->post('bookings/(:num)/cancel', 'StudentBookingApi::cancelStudentBooking/$1');
    $routes->post('bookings/(:num)/reschedule', 'StudentBookingApi::rescheduleStudentBooking/$1');
    $routes->delete('bookings/(:num)', 'StudentBookingApi::deleteStudentBooking/$1');
    
    $routes->get('bookings/(:num)/files', 'StudentBookingApi::getStudentBookingFiles/$1');
    $routes->get('bookings/(:num)/files/(:num)/download', 'StudentBookingApi::downloadStudentDocument/$1/$2');
    $routes->delete('bookings/(:num)/files/(:num)', 'StudentBookingApi::deleteStudentDocument/$1/$2');
    $routes->post('bookings/check-availability', 'StudentBookingApi::checkFacilityAvailability');
    $routes->get('bookings/check-availability', 'StudentBookingApi::checkFacilityAvailability');
    
    $routes->get('bookings', 'StudentBookingApi::getStudentBookings');
    $routes->get('bookings/(:num)', 'StudentBookingApi::getStudentBooking/$1');
    $routes->get('equipment', 'StudentBookingApi::getEquipment');
    $routes->get('equipment/availability', 'StudentBookingApi::getEquipmentAvailabilityByDate');
});

// Facilities endpoint - NO auth filter (method checks session internally)
$routes->group('api/student', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->get('facilities/(:segment)/data', 'StudentBookingApi::getFacilityData/$1');
});

// Public booking check endpoint - NO auth required for public availability check
$routes->group('bookings', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->post('check-availability', 'StudentBookingApi::checkFacilityAvailability');
    $routes->get('check-availability', 'StudentBookingApi::checkFacilityAvailability');
});

$routes->get('test-email', 'TestEmail::index');
$routes->get('contact', 'Contact::index');
$routes->post('contact/send', 'Contact::sendMessage');


$routes->group('api/events', ['namespace' => 'App\Controllers\Api'], function($routes) {
    // Calendar endpoints (PUBLIC - no auth required)
    $routes->get('calendar', 'EventsApiController::getCalendarEvents');
    $routes->get('stats', 'EventsApiController::getEventStats');
    $routes->get('list', 'EventsApiController::list');  // ← CHANGED
    $routes->get('upcoming', 'EventsApiController::getUpcomingEvents');

    // Facilitator-specific routes (requires auth) - MUST come before generic (:num) route
    $routes->get('completed', '\App\Controllers\Facilitator::getCompletedEvents', ['filter' => 'auth']);
    $routes->get('checklist/(:num)', '\App\Controllers\Facilitator::getEventChecklist/$1', ['filter' => 'auth']);
    $routes->get('(:num)/generated-files', '\App\Controllers\Facilitator::getGeneratedFiles/$1', ['filter' => 'auth']);
    $routes->get('(:num)/download-file/(:any)', '\App\Controllers\Facilitator::downloadInspectionFile/$1/$2', ['filter' => 'auth']);
    $routes->delete('(:num)/delete-file/(:any)', '\App\Controllers\Facilitator::deleteInspectionFile/$1/$2', ['filter' => 'auth']);
    $routes->post('(:num)/equipment-report', '\App\Controllers\Facilitator::generateEquipmentReport/$1', ['filter' => 'auth']);
    $routes->post('(:num)/update-equipment', '\App\Controllers\Facilitator::updateEquipmentFromInspection/$1', ['filter' => 'auth']);

    // Generic event detail route - MUST be after specific routes
    $routes->get('(:num)', 'EventsApiController::getEventDetails/$1');

    // Availability endpoints (PUBLIC)
    $routes->get('availability', 'EventsApiController::getFacilityAvailability');
    $routes->get('date-range', 'EventsApiController::getEventsByDateRange');
    $routes->get('monthly-summary', 'EventsApiController::getMonthlyEventSummary');

    // Guest management for attendance (requires auth)
    $routes->group('(:num)', ['filter' => 'auth'], function($routes) {
        $routes->get('guests', 'GuestApiController::getGuests/$1');
        $routes->get('attendance-stats', 'GuestApiController::getAttendanceStats/$1');
        $routes->get('attendance-export', 'GuestApiController::exportAttendanceExcel/$1');
    });
});

// ============================================
// GUEST & ATTENDANCE API ROUTES
// ============================================

$routes->group('api/guests', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], function($routes) {
    // Guest CRUD operations
    $routes->post('create', 'GuestApiController::create');
    $routes->post('bulk-create', 'GuestApiController::bulkCreate');
    $routes->put('(:num)/update', 'GuestApiController::update/$1');
    $routes->delete('(:num)/delete', 'GuestApiController::delete/$1');

    // QR Code operations
    $routes->get('(:num)/qr-download', 'GuestApiController::downloadQRCode/$1');
    $routes->get('(:num)/qr-url', 'GuestApiController::getQRCodeUrl/$1');
});

$routes->group('api/attendance', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], function($routes) {
    // Attendance recording
    $routes->post('scan', 'GuestApiController::recordAttendance');
    $routes->post('manual-checkin', 'GuestApiController::manualCheckIn');
});

// ============================================
// FACILITATOR ROUTES (Requires Facilitator Role)
// ============================================

$routes->group('facilitator', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Facilitator::index');
    $routes->get('dashboard', 'Facilitator::index');
    $routes->get('history', 'Facilitator::getSubmissionHistory');
});

// ============================================
// EXTENSION ROUTES (Hour Extension System)
// ============================================

$routes->group('api/extensions', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], function($routes) {
    // Request extension (student/faculty)
    $routes->post('request', 'ExtensionApiController::requestExtension');
    
    // Check if student has extension for a booking (student endpoint)
    $routes->get('check-booking/(:num)', 'ExtensionApiController::checkStudentExtension/$1');
    
    // Get pending extensions (admin/facilitator)
    $routes->get('pending', 'ExtensionApiController::getPendingExtensions');
    
    // Get extension details
    $routes->get('(:num)', 'ExtensionApiController::getExtensionDetails/$1');
    
    // Approve extension (admin/facilitator)
    $routes->post('(:num)/approve', 'ExtensionApiController::approveExtension/$1');
    
    // Reject extension (admin/facilitator)
    $routes->post('(:num)/reject', 'ExtensionApiController::rejectExtension/$1');
    
    // Upload file (admin/facilitator)
    $routes->post('(:num)/upload', 'ExtensionApiController::uploadFile/$1');
    
    // Download file
    $routes->get('files/(:num)/download', 'ExtensionApiController::downloadFile/$1');
    
    // Delete file
    $routes->delete('files/(:num)', 'ExtensionApiController::deleteFile/$1');
    
    // Mark payment received (admin/facilitator)
    $routes->post('(:num)/mark-paid', 'ExtensionApiController::markPaymentReceived/$1');
    
    // Get stats
    $routes->get('stats/all', 'ExtensionApiController::getStats');
    
    // Generate extension payment order (admin) - NOTE: Uses full namespace since Admin controller is not in Api namespace
    $routes->get('(:num)/order-of-payment', '\App\Controllers\Admin\OrderOfPaymentController::generateExtensionOrderOfPayment/$1');
    
    // Download extension payment order (admin) - NOTE: Uses full namespace since Admin controller is not in Api namespace
    $routes->get('(:num)/download-order-of-payment', '\App\Controllers\Admin\OrderOfPaymentController::downloadExtensionOrderOfPayment/$1');
});

// ============================================
// SURVEY ROUTES (No Authentication Required for survey submission)
// ============================================

$routes->get('survey/(:segment)', 'Survey::index/$1');
$routes->post('survey/submit', 'Survey::submit');
$routes->get('survey/thank-you', 'Survey::thankYou');
$routes->get('api/survey/(:num)', 'Survey::getSurvey/$1', ['filter' => 'auth']);
$routes->get('api/survey-files/(:num)', 'Survey::getEvaluationFiles/$1', ['filter' => 'auth']);

// ============================================
// EMPLOYEE ROUTES (Requires Employee Role)
// ============================================

$routes->group('employee', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Employee::index');
    $routes->get('dashboard', 'Employee::dashboard');
    $routes->get('bookings', 'Employee::bookings');
    $routes->get('profile', 'Employee::profile');
    $routes->get('history', 'Employee::history');
    $routes->get('attendance', 'Employee::attendance');
    $routes->get('book', 'Employee::book');
});

// ============================================
// LEGACY FACULTY ROUTES (Redirect to Employee)
// ============================================
// Kept for backward compatibility - all requests to /faculty/* redirect to /employee/*

$routes->group('faculty', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Faculty::index');
    $routes->get('dashboard', 'Faculty::dashboard');
    $routes->get('bookings', 'Faculty::bookings');
    $routes->get('profile', 'Faculty::profile');
    $routes->get('history', 'Faculty::history');
    $routes->get('attendance', 'Faculty::attendance');
    $routes->get('book', 'Faculty::book');
});