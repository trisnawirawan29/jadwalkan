<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'stats' => [
                ['label' => 'Total Pengguna', 'value' => '1,248', 'change' => '+12.5%', 'icon' => 'fas fa-users', 'color' => 'primary'],
                ['label' => 'Pendapatan Bulan Ini', 'value' => 'Rp 48,6 jt', 'change' => '+8.2%', 'icon' => 'fas fa-wallet', 'color' => 'success'],
                ['label' => 'Pesanan Baru', 'value' => '356', 'change' => '+5.7%', 'icon' => 'fas fa-shopping-bag', 'color' => 'warning'],
                ['label' => 'Tiket Terbuka', 'value' => '24', 'change' => '-3.1%', 'icon' => 'fas fa-headset', 'color' => 'danger'],
            ],
        ]);
    }
}
