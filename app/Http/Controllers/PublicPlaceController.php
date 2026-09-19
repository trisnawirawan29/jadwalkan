<?php

namespace App\Http\Controllers;

use App\Models\BusinessPlace;
use App\Models\Setting;
use Illuminate\View\View;

class PublicPlaceController extends Controller
{
    public function show(BusinessPlace $businessPlace): View
    {
        abort_unless($businessPlace->is_active, 404);

        $businessPlace->load([
            'businessCategories:id,name',
            'services' => static function ($query): void {
                $query->where('is_active', true)
                    ->with([
                        'businessCategory:id,name',
                        'schedules' => static fn ($scheduleQuery) => $scheduleQuery->where('is_active', true)->where('is_closed', false)->orderBy('day_of_week')->orderBy('start_time'),
                        'hourlyPrices',
                        'bookings' => static fn ($bookingQuery) => $bookingQuery
                            ->whereIn('status', ['held', 'rejected', 'payment_submitted', 'confirmed'])
                            ->whereDate('booking_date', '>=', today())
                            ->where(function ($query): void {
                                $query->whereNotIn('status', ['held'])->orWhere('expires_at', '>', now());
                            })
                            ->orderBy('booking_date')
                            ->orderBy('start_time'),
                        'closures' => static fn ($closureQuery) => $closureQuery->where('is_active', true)->where('closure_date', '>=', today())->orderBy('closure_date'),
                    ])
                    ->withCount('schedules')
                    ->orderBy('name');
            },
        ]);

        return view('places.show', [
            'appName' => Setting::value('app_name', config('app.name')),
            'businessPlace' => $businessPlace,
        ]);
    }
}
