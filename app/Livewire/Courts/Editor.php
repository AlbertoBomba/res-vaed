<?php

namespace App\Livewire\Courts;

use App\Http\Requests\SaveCourtRequest;
use App\Models\Company;
use App\Models\Court;
use App\Services\CourtPricingService;
use App\Services\SaveCourtService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class Editor extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $courtId = null;

    #[Locked]
    public ?int $version = null;

    #[Locked]
    public ?string $existingImageUrl = null;

    public string $name = '';

    public string $sport = 'padel';

    public string $type = 'outdoor';

    public string $surface = 'artificial-grass';

    public string $enclosure = 'glass';

    public string $status = 'inactive';

    public $capacity = 4;

    public bool $lighting = false;

    public bool $accessible = false;

    public string $description = '';

    public string $hourly_price = '';

    public string $two_hour_price = '';

    public string $three_plus_hour_price = '';

    public array $weekly_hours = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => []];

    public $image = null;

    #[Locked]
    public bool $imageReady = false;

    public bool $remove_image = false;

    public $selected_company_id = null;

    public int $selectedDay = 1;

    public $previewPreviousHours = 0;

    public $previewNewHours = 1;

    public ?array $quote = null;

    public function mount(?int $court = null): void
    {
        if ($court) {
            $record = $this->court($court);
            Gate::authorize('update', $record);
            $this->courtId = $record->id;
            $this->version = $record->version;
            $this->selected_company_id = $record->company_id;
            $this->existingImageUrl = $record->imageUrl();
            foreach (['name', 'sport', 'type', 'surface', 'enclosure', 'status', 'capacity', 'lighting', 'accessible', 'description'] as $field) {
                $this->{$field} = $record->{$field} ?? '';
            }
            foreach (['hourly_price', 'two_hour_price', 'three_plus_hour_price'] as $field) {
                $this->{$field} = number_format($record->{$field.'_cents'} / 100, 2, '.', '');
            }
            foreach ($record->hours as $slot) {
                $this->weekly_hours[$slot->weekday][] = (string) $slot->hour;
            }
        } else {
            Gate::authorize('create', Court::class);
            $this->selected_company_id = auth()->user()->company_id;
        }
    }

    private function court(int $id): Court
    {
        $user = auth()->user();

        return Court::query()->when(! $user->hasRole('super-admin'), fn ($query) => $query->forCompany($user->company_id ?? 0))->findOrFail($id);
    }

    private function authorizeEditor(): ?Court
    {
        $record = $this->courtId ? $this->court($this->courtId) : null;
        Gate::authorize($record ? 'update' : 'create', $record ?? Court::class);

        return $record;
    }

    protected function rules(): array
    {
        return (new SaveCourtRequest)->rules();
    }

    public function selectDay(int $day): void
    {
        $this->authorizeEditor();
        abort_unless(in_array($day, range(1, 7), true), 422);
        $this->selectedDay = $day;
    }

    public function setDayHours(string $selection): void
    {
        $this->authorizeEditor();
        abort_unless(in_array($this->selectedDay, range(1, 7), true), 422);
        abort_unless(in_array($selection, ['all', 'none'], true), 422);
        $this->weekly_hours[$this->selectedDay] = $selection === 'all' ? array_map('strval', range(0, 23)) : [];
    }

    public function copyDayToWeek(): void
    {
        $this->authorizeEditor();
        abort_unless(in_array($this->selectedDay, range(1, 7), true), 422);
        foreach (range(1, 7) as $day) {
            $this->weekly_hours[$day] = $this->weekly_hours[$this->selectedDay];
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['hourly_price', 'two_hour_price', 'three_plus_hour_price', 'previewPreviousHours', 'previewNewHours'], true)) {
            $this->quote = null;
        }
    }

    public function updatedImage(): void
    {
        $this->authorizeEditor();
        $this->imageReady = false;
        $request = new SaveCourtRequest;
        $this->validateOnly('image', $request->rules(), $request->messages(), $request->attributes());
        $this->imageReady = $this->image !== null;
    }

    public function previewPrice(CourtPricingService $pricing): void
    {
        $this->authorizeEditor();
        $this->normalizePrices();
        $request = new SaveCourtRequest;
        $prices = array_intersect_key($request->rules(), array_flip(['hourly_price', 'two_hour_price', 'three_plus_hour_price']));
        $data = $this->validate($prices + [
            'previewPreviousHours' => ['required', 'integer', 'between:0,23'],
            'previewNewHours' => ['required', 'integer', 'min:1', 'max:'.(24 - (int) $this->previewPreviousHours)],
        ], $request->messages(), $request->attributes());
        $court = new Court;
        foreach ($prices as $field => $rules) {
            $court->{$field.'_cents'} = (int) round((float) $data[$field] * 100);
        }
        $previous = (int) $data['previewPreviousHours'];
        $this->quote = $pricing->quote($court, (int) $data['previewNewHours'], $previous, $pricing->dailyTotal($court, $previous));
    }

    public function save(SaveCourtService $service)
    {
        $record = $this->authorizeEditor();
        $this->name = trim($this->name);
        $this->normalizePrices();
        $request = new SaveCourtRequest;
        $data = $this->validate($request->rules(), $request->messages(), $request->attributes());

        if (! $record && auth()->user()->hasRole('super-admin')) {
            $this->validate(['selected_company_id' => ['required', 'integer', Rule::exists('companies', 'id')]], [
                'selected_company_id.required' => 'Selecciona la empresa propietaria de la pista.',
                'selected_company_id.exists' => 'La empresa seleccionada no existe.',
            ]);
        }

        $result = $service->save(auth()->user(), $data, $record, $this->version, $this->selected_company_id ? (int) $this->selected_company_id : null);
        session()->flash('success', $record ? 'Pista actualizada correctamente.' : 'Pista creada correctamente.');
        if ($result['warning']) {
            session()->flash('warning', $result['warning']);
        }

        return $this->redirectRoute('admin.courts.index');
    }

    private function normalizePrices(): void
    {
        foreach (['hourly_price', 'two_hour_price', 'three_plus_hour_price'] as $field) {
            $this->{$field} = str_replace(',', '.', trim($this->{$field}));
        }
    }

    public function render()
    {
        $this->authorizeEditor();

        return view('livewire.courts.editor', [
            'companies' => auth()->user()->hasRole('super-admin') && ! $this->courtId ? Company::orderBy('name')->get() : collect(),
            'companyName' => $this->courtId ? $this->court($this->courtId)->company->name : auth()->user()->company?->name,
        ])->layout('layouts.app');
    }
}
