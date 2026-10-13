<?php

use App\Http\Controllers\Admin\AcademicProfileController;
use App\Http\Controllers\Admin\AdmissionController as AdminAdmissionController;
use App\Http\Controllers\Admin\ContactEnquiryController as AdminContactEnquiryController;
use App\Http\Controllers\Admin\ContentSectionController;
use App\Http\Controllers\Admin\CoreDataController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ExaminationController;
use App\Http\Controllers\Admin\ExamScheduleController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\GalleryImageController;
use App\Http\Controllers\Admin\HomeBannerController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\SchoolSettingsController;
use App\Http\Controllers\Admin\TeachingAssignmentController;
use App\Http\Controllers\Admin\TimetableController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\ContactEnquiryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\ResultPortalController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherResultsController;
use App\Http\Controllers\TimetablePortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/news-events', [PublicSiteController::class, 'news'])->name('news');
Route::get('/teachers', [PublicSiteController::class, 'teachers'])->name('public.teachers');
Route::get('/facilities', [PublicSiteController::class, 'facilities'])->name('public.facilities');
Route::get('/contact', [PublicSiteController::class, 'contact'])->name('public.contact');
Route::get('/{page}', [PublicSiteController::class, 'page'])->whereIn('page', ['about', 'academics', 'admissions', 'gallery', 'contact'])->name('public.page');
Route::post('/admissions/apply', [AdmissionController::class, 'store'])->middleware('throttle:10,1')->name('admissions.apply');
Route::post('/contact/enquiries', [ContactEnquiryController::class, 'store'])->middleware('throttle:10,1')->name('contact-enquiries.store');
Route::get('/dashboard', fn () => redirect(auth()->user()->portalPath()))->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
    Route::resource('notices', NoticeController::class)->except('show');
    Route::resource('events', EventController::class)->except('show');
    Route::resource('facilities', FacilityController::class)->except('show');
    Route::resource('banners', HomeBannerController::class)->except('show');
    Route::resource('examinations', ExaminationController::class);
    Route::patch('examinations/{examination}/status', [ExaminationController::class, 'changeStatus'])->name('examinations.status');
    Route::get('examinations/{examination}/schedules/create', [ExamScheduleController::class, 'create'])->name('examinations.schedules.create');
    Route::post('examinations/{examination}/schedules', [ExamScheduleController::class, 'store'])->name('examinations.schedules.store');
    Route::get('examinations/{examination}/schedules/{schedule}/edit', [ExamScheduleController::class, 'edit'])->name('examinations.schedules.edit');
    Route::put('examinations/{examination}/schedules/{schedule}', [ExamScheduleController::class, 'update'])->name('examinations.schedules.update');
    Route::delete('examinations/{examination}/schedules/{schedule}', [ExamScheduleController::class, 'destroy'])->name('examinations.schedules.destroy');
    Route::resource('admissions', AdminAdmissionController::class)->only(['index', 'show', 'update']);
    Route::resource('contact-enquiries', AdminContactEnquiryController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::resource('content', ContentSectionController::class)->except('show');
    Route::get('academics', [CoreDataController::class, 'index'])->name('academics.index');
    Route::get('academics/{type}/create', [CoreDataController::class, 'create'])->name('academics.create');
    Route::post('academics/{type}', [CoreDataController::class, 'store'])->name('academics.store');
    Route::get('academics/{type}/{id}/edit', [CoreDataController::class, 'edit'])->name('academics.edit');
    Route::put('academics/{type}/{id}', [CoreDataController::class, 'update'])->name('academics.update');
    Route::delete('academics/{type}/{id}', [CoreDataController::class, 'destroy'])->name('academics.destroy');
    Route::get('{type}', [AcademicProfileController::class, 'index'])->whereIn('type', ['teachers', 'students'])->name('people.index');
    Route::get('{type}/create', [AcademicProfileController::class, 'create'])->whereIn('type', ['teachers', 'students'])->name('people.create');
    Route::post('{type}', [AcademicProfileController::class, 'store'])->whereIn('type', ['teachers', 'students'])->name('people.store');
    Route::get('{type}/{id}/edit', [AcademicProfileController::class, 'edit'])->whereIn('type', ['teachers', 'students'])->name('people.edit');
    Route::put('{type}/{id}', [AcademicProfileController::class, 'update'])->whereIn('type', ['teachers', 'students'])->name('people.update');
    Route::delete('{type}/{id}', [AcademicProfileController::class, 'destroy'])->whereIn('type', ['teachers', 'students'])->name('people.destroy');
    Route::resource('teaching-assignments', TeachingAssignmentController::class)->except('show');
    Route::resource('timetables', TimetableController::class)->except('show');
    Route::get('settings', [SchoolSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SchoolSettingsController::class, 'update'])->name('settings.update');
    Route::resource('users', UserManagementController::class)->except('show');
    Route::resource('galleries', GalleryController::class)->except('show');
    Route::post('galleries/{gallery}/images', [GalleryImageController::class, 'store'])->name('galleries.images.store');
    Route::patch('galleries/{gallery}/images/reorder', [GalleryImageController::class, 'reorder'])->name('galleries.images.reorder');
    Route::patch('galleries/{gallery}/images/{image}', [GalleryImageController::class, 'update'])->name('galleries.images.update');
    Route::delete('galleries/{gallery}/images/{image}', [GalleryImageController::class, 'destroy'])->name('galleries.images.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/teacher/attendance', [TeacherAttendanceController::class, 'index'])->middleware('role:teacher')->name('teacher.attendance.index');
    Route::post('/teacher/attendance', [TeacherAttendanceController::class, 'store'])->middleware('role:teacher')->name('teacher.attendance.store');
    Route::get('/teacher/results', [TeacherResultsController::class, 'index'])->middleware('role:teacher')->name('teacher.results.index');
    Route::post('/teacher/results/{schedule}/marks', [TeacherResultsController::class, 'store'])->middleware('role:teacher')->name('teacher.results.store');
    Route::get('/teacher/timetable', [TimetablePortalController::class, 'teacher'])->middleware('role:teacher')->name('teacher.timetable');
    Route::get('/teacher', fn () => app(DashboardController::class)->portal('teacher'))->middleware('role:teacher')->name('teacher.dashboard');
    Route::get('/guardian/results', [ResultPortalController::class, 'guardian'])->middleware('role:guardian')->name('guardian.results');
    Route::get('/guardian/timetable', [TimetablePortalController::class, 'guardian'])->middleware('role:guardian')->name('guardian.timetable');
    Route::get('/guardian', fn () => app(DashboardController::class)->portal('guardian'))->middleware('role:guardian')->name('guardian.dashboard');
    Route::get('/student/results', [ResultPortalController::class, 'student'])->middleware('role:student')->name('student.results');
    Route::get('/student/timetable', [TimetablePortalController::class, 'student'])->middleware('role:student')->name('student.timetable');
    Route::get('/student', fn () => app(DashboardController::class)->portal('student'))->middleware('role:student')->name('student.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
