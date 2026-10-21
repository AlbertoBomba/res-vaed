<?php

namespace App\Livewire\Courts;

use App\Models\Court;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sport = '';

    #[Url]
    public string $status = '';

    public ?int $confirmingArchive = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Court::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'sport', 'status'], true)) {
            $this->resetPage();
        }
    }

    private function courts(): Builder
    {
        Gate::authorize('viewAny', Court::class);
        $user = auth()->user();

        return Court::query()->when(! $user->hasRole('super-admin'), fn ($query) => $query->forCompany($user->company_id ?? 0));
    }

    public function confirmArchive(int $id): void
    {
        $court = $this->courts()->findOrFail($id);
        Gate::authorize('delete', $court);
        $this->confirmingArchive = $court->id;
    }

    public function archive(): void
    {
        DB::transaction(function () {
            $court = $this->courts()->lockForUpdate()->findOrFail($this->confirmingArchive);
            Gate::authorize('delete', $court);
            $court->version++;
            $court->save();
            $court->delete();
        });
        $this->confirmingArchive = null;
        session()->flash('success', 'Pista archivada. Puedes recuperarla desde el filtro Archivadas.');
        $this->resetPage();
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id) {
            $court = $this->courts()->onlyTrashed()->lockForUpdate()->findOrFail($id);
            Gate::authorize('restore', $court);
            $court->version++;
            $court->restore();
        });
        session()->flash('success', 'Pista recuperada con su horario y tarifas.');
        $this->resetPage();
    }

    public function render()
    {
        $this->validate([
            'search' => ['string', 'max:100'],
            'sport' => ['nullable', Rule::in(array_keys(config('courts.sports')))],
            'status' => ['nullable', Rule::in([...array_keys(config('courts.statuses')), 'archived'])],
        ]);
        $base = $this->courts();
        $courts = (clone $base)->with('company')->withCount('hours')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->sport !== '', fn ($query) => $query->where('sport', $this->sport))
            ->when($this->status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when($this->status !== '' && $this->status !== 'archived', fn ($query) => $query->where('status', $this->status))
            ->orderBy('name')->orderBy('id')->paginate(9);

        return view('livewire.courts.index', [
            'courts' => $courts,
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'maintenance' => (clone $base)->where('status', 'maintenance')->count(),
        ])->layout('layouts.app');
    }
}
