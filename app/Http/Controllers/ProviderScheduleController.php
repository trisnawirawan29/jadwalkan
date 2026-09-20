<?php

namespace App\Http\Controllers;

use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;
use Illuminate\View\View;

class ProviderScheduleController extends Controller
{
    public function index(BusinessPlace $businessPlace, BusinessService $businessService): View
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('view', $businessService);
        $businessService->load([
            'schedules' => fn ($query) => $query->orderBy('day_of_week')->orderBy('start_time'),
            'closures' => fn ($query) => $query->where('closure_date', '>=', today())->where('is_active', true)->orderBy('closure_date'),
        ]);

        return view('provider.schedules.index', compact('businessPlace', 'businessService'));
    }

    public function create(BusinessPlace $businessPlace, BusinessService $businessService): View
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('view', $businessService);
        $this->authorize('create', ServiceSchedule::class);

        return view('provider.schedules.form', compact('businessPlace', 'businessService'));
    }

    public function store(Request $request, BusinessPlace $businessPlace, BusinessService $businessService): RedirectResponse
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('view', $businessService);
        $this->authorize('create', ServiceSchedule::class);
        if (! is_array($request->input('day_of_week'))) {
            $request->merge(['day_of_week' => [$request->input('day_of_week')]]);
        }
        $data = $this->validatedSchedule($request, $businessService, multipleDays: true);
        $days = $data['day_of_week'];
        unset($data['day_of_week']);

        foreach ($days as $day) {
            $businessService->schedules()->create([...$data, 'day_of_week' => $day]);
        }

        return redirect()->route('provider.business-places.services.schedules.index', [$businessPlace, $businessService])->with('success', 'Jadwal berulang berhasil ditambahkan.');
    }

    public function show(BusinessPlace $businessPlace, BusinessService $businessService, ServiceSchedule $schedule): View
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('view', $businessService);
        $this->authorize('view', $schedule);

        return view('provider.schedules.show', compact('businessPlace', 'businessService', 'schedule'));
    }

    public function edit(BusinessPlace $businessPlace, BusinessService $businessService, ServiceSchedule $schedule): View
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('view', $businessService);
        $this->authorize('update', $schedule);

        return view('provider.schedules.form', compact('businessPlace', 'businessService', 'schedule'));
    }

    public function update(Request $request, BusinessPlace $businessPlace, BusinessService $businessService, ServiceSchedule $schedule): RedirectResponse
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('view', $businessService);
        $this->authorize('update', $schedule);
        $schedule->update($this->validatedSchedule($request, $businessService, $schedule));

        return redirect()->route('provider.business-places.services.schedules.index', [$businessPlace, $businessService])->with('success', 'Jadwal berulang berhasil diperbarui.');
    }

    public function destroy(BusinessPlace $businessPlace, BusinessService $businessService, ServiceSchedule $schedule): RedirectResponse
    {
        $this->authorize('view', $businessPlace);
        $this->authorize('view', $businessService);
        $this->authorize('delete', $schedule);
        $schedule->delete();

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    private function validatedSchedule(Request $request, BusinessService $businessService, ?ServiceSchedule $schedule = null, bool $multipleDays = false): array
    {
        /** @var ValidationValidator $validator */
        $validator = Validator::make($request->all(), [
            'day_of_week' => $multipleDays ? ['required', 'array', 'min:1'] : ['required', 'integer', Rule::in(range(1, 7))],
            'day_of_week.*' => ['integer', 'distinct', Rule::in(range(1, 7))],
            'start_time' => ['nullable', 'required_unless:is_closed,1', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_unless:is_closed,1', 'date_format:H:i'],
            'is_closed' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request, $businessService, $schedule, $multipleDays): void {
            $isClosed = $request->boolean('is_closed');
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');
            $days = $multipleDays ? array_map('intval', (array) $request->input('day_of_week', [])) : [$request->integer('day_of_week')];

            foreach ($days as $index => $day) {
                if ($isClosed) {
                    $duplicateClosedDay = ServiceSchedule::query()
                        ->where('business_service_id', $businessService->id)
                        ->where('day_of_week', $day)
                        ->when($schedule, fn ($query) => $query->whereKeyNot($schedule->id))
                        ->exists();

                    if ($duplicateClosedDay) {
                        $validator->errors()->add($multipleDays ? "day_of_week.{$index}" : 'day_of_week', 'Hari ini sudah memiliki jadwal. Edit jadwal yang ada untuk menjadikannya tutup.');
                    }

                    continue;
                }

                if ($startTime !== null && $endTime !== null && $startTime >= $endTime) {
                    $validator->errors()->add('end_time', 'Jam selesai harus setelah jam mulai.');

                    return;
                }

                if ($validator->errors()->hasAny(['day_of_week', 'start_time', 'end_time'])) {
                    return;
                }

                $overlaps = ServiceSchedule::query()
                    ->where('business_service_id', $businessService->id)
                    ->where('day_of_week', $day)
                    ->when($schedule, fn ($query) => $query->whereKeyNot($schedule->id))
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->exists();

                if ($overlaps) {
                    $validator->errors()->add('start_time', 'Jadwal bertumpuk dengan jadwal lain pada hari yang sama.');
                }
            }
        });

        return $validator->validate();
    }
}
