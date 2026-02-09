<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MasterData\Medicine;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to pharmacy staff when medicine stock is low.
 * Channels: database, mail
 */
class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Medicine $medicine,
        public ?float $currentStock = null,
        public ?float $minStock = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $medicineName = $this->medicine->name;
        $medicineCode = $this->medicine->code;
        $currentStock = $this->currentStock ?? $this->medicine->stock;
        $minStock = $this->minStock ?? $this->medicine->min_stock;
        $unit = $this->medicine->unit ?? 'unit';

        return (new MailMessage)
            ->subject('Peringatan Stok Obat Menipis')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Stok obat berikut telah mencapai batas minimum:')
            ->line('')
            ->line("**{$medicineName}** ({$medicineCode})")
            ->line("Stok Saat Ini: **{$currentStock} {$unit}**")
            ->line("Stok Minimum: **{$minStock} {$unit}**")
            ->line('')
            ->warning()
            ->action('Kelola Stok Obat', url('/admin/medicines/' . $this->medicine->id))
            ->line('Segera lakukan pemesanan ulang untuk menghindari kehabisan stok.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'medicine_id' => $this->medicine->id,
            'medicine_name' => $this->medicine->name,
            'medicine_code' => $this->medicine->code,
            'current_stock' => $this->currentStock ?? $this->medicine->stock,
            'min_stock' => $this->minStock ?? $this->medicine->min_stock,
            'unit' => $this->medicine->unit,
            'stock_status' => $this->medicine->stock_status,
            'message' => "Stok {$this->medicine->name} menipis ({$this->medicine->stock} {$this->medicine->unit})",
            'action_url' => '/admin/medicines/' . $this->medicine->id,
            'priority' => $this->medicine->is_out_of_stock ? 'high' : 'medium',
        ];
    }
}
