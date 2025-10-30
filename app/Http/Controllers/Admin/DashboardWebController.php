<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Admin Dashboard Web Controller
 * Handles web-based dashboard views (Blade templates)
 */
class DashboardWebController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        // Sample statistics data
        // TODO: Replace with real data from repositories/services
        $stats = [
            'articles' => 1247,
            'hadith' => 3856,
            'media' => 342,
            'users' => 15643,
        ];

        // Sample chart data
        $chartData = [
            'articles' => [30, 40, 35, 50, 49, 60, 70, 91, 125],
            'hadith' => [20, 30, 25, 40, 39, 50, 60, 81, 105],
            'media' => [10, 15, 20, 25, 30, 35, 40, 45, 50],
            'distribution' => [44, 32, 13, 11], // Seerah, Hadith, Media, Quran
        ];

        // Sample recent activities
        $recentActivities = [
            [
                'type' => 'Article',
                'icon' => 'ki-book',
                'title' => 'The Hijrah - Migration to Madinah',
                'user_name' => 'Ahmed Hassan',
                'user_avatar' => asset('admin/media/avatars/300-1.png'),
                'status' => 'published',
                'date' => now()->subHours(2)->format('M d, Y'),
                'url' => '#',
            ],
            [
                'type' => 'Hadith',
                'icon' => 'ki-scroll',
                'title' => 'Sahih Bukhari - Chapter 12',
                'user_name' => 'Fatima Ali',
                'user_avatar' => asset('admin/media/avatars/300-2.png'),
                'status' => 'published',
                'date' => now()->subHours(5)->format('M d, Y'),
                'url' => '#',
            ],
            [
                'type' => 'Media',
                'icon' => 'ki-video',
                'title' => 'The Life of Prophet Muhammad - Episode 5',
                'user_name' => 'Omar Khalil',
                'user_avatar' => asset('admin/media/avatars/300-3.png'),
                'status' => 'draft',
                'date' => now()->subDay()->format('M d, Y'),
                'url' => '#',
            ],
            [
                'type' => 'Article',
                'icon' => 'ki-book',
                'title' => 'The Battle of Badr',
                'user_name' => 'Aisha Muhammad',
                'user_avatar' => asset('admin/media/avatars/300-4.png'),
                'status' => 'published',
                'date' => now()->subDays(2)->format('M d, Y'),
                'url' => '#',
            ],
            [
                'type' => 'Media',
                'icon' => 'ki-video',
                'title' => 'Stories from the Seerah - Episode 12',
                'user_name' => 'Ahmed Hassan',
                'user_avatar' => asset('admin/media/avatars/300-1.png'),
                'status' => 'published',
                'date' => now()->subDays(3)->format('M d, Y'),
                'url' => '#',
            ],
        ];

        return view('admin.dashboard.index', compact('stats', 'chartData', 'recentActivities'));
    }
}
