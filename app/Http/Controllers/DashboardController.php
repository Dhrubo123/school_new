<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\Notice;
use App\Models\SchoolEvent;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function admin(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'metrics' => ['students' => User::whereHas('role', fn ($q) => $q->where('slug', 'student'))->count(), 'newAdmissions' => AdmissionApplication::where('status', 'new')->count(), 'publishedNotices' => Notice::published()->count()],
            'admissions' => AdmissionApplication::latest()->limit(5)->get(),
            'notices' => Notice::latest()->limit(5)->get(),
            'events' => SchoolEvent::whereDate('starts_at', now())->get(),
        ]);
    }

    public function portal(string $role): Response
    {
        return Inertia::render('Portal/Dashboard', ['role' => $role]);
    }
}
