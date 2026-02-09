<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class HospitalSettings extends Settings
{
    // Tab 1: Informasi Rumah Sakit
    public string $hospital_name;
    public string $hospital_code;
    public string $hospital_class;
    public string $hospital_address;
    public string $hospital_phone;
    public string $hospital_email;
    public string $hospital_director;
    public string $hospital_administrator;

    // Tab 2: Pengaturan Pendaftaran
    public string $mr_number_format;
    public int $mr_starting_number;
    public string $visit_number_format;
    public int $visit_starting_number;

    // Tab 3: Pengaturan BPJS
    public bool $bpjs_enabled;
    public string $bpjs_ppk_code;
    public string $bpjs_ppk_name;
    public string $bpjs_endpoint_dev;
    public string $bpjs_endpoint_prod;
    public string $bpjs_cons_id;
    public string $bpjs_secret_key;
    public string $bpjs_user_key;
    public bool $bpjs_prod_mode;

    // Tab 4: Pengaturan Satu Sehat
    public bool $satu_sehat_enabled;
    public string $satu_sehat_org_id;
    public string $satu_sehat_client_id;
    public string $satu_sehat_client_secret;
    public string $satu_sehat_auth_url;
    public string $satu_sehat_base_url;
    public string $satu_sehat_environment;

    // Tab 5: Pengaturan Notifikasi
    public bool $email_enabled;
    public string $email_host;
    public int $email_port;
    public string $email_username;
    public string $email_password;
    public string $email_encryption;
    public string $email_from_address;
    public string $email_from_name;
    public bool $sms_enabled;
    public string $sms_gateway_provider;
    public string $sms_api_key;
    public string $sms_api_secret;
    public string $sms_sender_id;
    public bool $whatsapp_enabled;
    public string $whatsapp_provider;
    public string $whatsapp_api_key;
    public string $whatsapp_sender;

    // Tab 6: Pengaturan Umum
    public string $timezone;
    public string $currency;
    public string $date_format;
    public string $language;
    public int $session_lifetime;
    public int $items_per_page;
    public bool $debug_mode;

    public static function group(): string
    {
        return 'hospital';
    }

    public static function defaults(): array
    {
        return [
            // Tab 1: Informasi Rumah Sakit
            'hospital_name' => 'Rumah Sakit Umum',
            'hospital_code' => 'RS001',
            'hospital_class' => 'B',
            'hospital_address' => 'Jl. Contoh No. 123, Kota',
            'hospital_phone' => '(021) 12345678',
            'hospital_email' => 'info@rumahsakitku.com',
            'hospital_director' => 'dr. Direktur',
            'hospital_administrator' => 'Admin RS',

            // Tab 2: Pengaturan Pendaftaran
            'mr_number_format' => 'YYYYMM-XXXX',
            'mr_starting_number' => 1,
            'visit_number_format' => 'V-YYYYMMDD-XXXX',
            'visit_starting_number' => 1,

            // Tab 3: Pengaturan BPJS
            'bpjs_enabled' => false,
            'bpjs_ppk_code' => '',
            'bpjs_ppk_name' => '',
            'bpjs_endpoint_dev' => 'https://apijkn-dev.bpjs-kesehatan.go.id',
            'bpjs_endpoint_prod' => 'https://apijkn.bpjs-kesehatan.go.id',
            'bpjs_cons_id' => '',
            'bpjs_secret_key' => '',
            'bpjs_user_key' => '',
            'bpjs_prod_mode' => false,

            // Tab 4: Pengaturan Satu Sehat
            'satu_sehat_enabled' => false,
            'satu_sehat_org_id' => '',
            'satu_sehat_client_id' => '',
            'satu_sehat_client_secret' => '',
            'satu_sehat_auth_url' => 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1',
            'satu_sehat_base_url' => 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1',
            'satu_sehat_environment' => 'sandbox',

            // Tab 5: Pengaturan Notifikasi
            'email_enabled' => false,
            'email_host' => 'smtp.gmail.com',
            'email_port' => 587,
            'email_username' => '',
            'email_password' => '',
            'email_encryption' => 'tls',
            'email_from_address' => 'noreply@rumahsakitku.com',
            'email_from_name' => 'RumahSakitKu',
            'sms_enabled' => false,
            'sms_gateway_provider' => 'twilio',
            'sms_api_key' => '',
            'sms_api_secret' => '',
            'sms_sender_id' => 'RSKU',
            'whatsapp_enabled' => false,
            'whatsapp_provider' => 'twilio',
            'whatsapp_api_key' => '',
            'whatsapp_sender' => '',

            // Tab 6: Pengaturan Umum
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'date_format' => 'd/m/Y',
            'language' => 'id',
            'session_lifetime' => 120,
            'items_per_page' => 25,
            'debug_mode' => false,
        ];
    }
}
