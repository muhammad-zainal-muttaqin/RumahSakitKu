<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use App\Settings\HospitalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Welcome mail sent to new users.
 */
class WelcomeUserMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
        public ?string $role = null
    ) {
    }

    public function envelope(): Envelope
    {
        $settings = app(HospitalSettings::class);

        return new Envelope(
            subject: 'Selamat Datang di ' . $settings->hospital_name . ' - Informasi Akun',
        );
    }

    public function content(): Content
    {
        $settings = app(HospitalSettings::class);
        $employee = $this->user->employee;

        return new Content(
            markdown: 'emails.user.welcome',
            with: [
                'userName' => $this->user->name,
                'email' => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'role' => $this->role ?? ($this->user->roles->first()?->name ?? 'User'),
                'employeeName' => $employee?->name,
                'employeeCode' => $employee?->employee_code,
                'loginUrl' => url('/admin/login'),
                'hospitalName' => $settings->hospital_name,
                'hospitalPhone' => $settings->hospital_phone,
                'supportEmail' => $settings->hospital_email,
            ],
        );
    }
}
