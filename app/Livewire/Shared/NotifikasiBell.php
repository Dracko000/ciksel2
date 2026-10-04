<?php

namespace App\Livewire\Shared;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class NotifikasiBell extends Component
{
    use WithPagination;

    /** Jumlah entri yang ditampilkan di dropdown. */
    private const LIMIT = 15;

    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refreshUnreadCount();
    }

    public function refreshUnreadCount(): void
    {
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        $this->refreshUnreadCount();
    }

    #[On('notifikasi-baru')]
    public function onNotifikasiBaru(): void
    {
        $this->refreshUnreadCount();
    }

    public function render(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->limit(self::LIMIT)
            ->get();

        return view('livewire.shared.notifikasi-bell', [
            'notifications' => $notifications,
        ]);
    }
}