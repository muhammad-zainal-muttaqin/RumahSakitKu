<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for storing/updating patient data.
 */
class StorePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('patient.create') || $this->user()->can('patient.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $patientId = $this->route('patient')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'medical_record_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('patients')->ignore($patientId),
            ],
            'nik' => [
                'nullable',
                'string',
                'digits:16',
                Rule::unique('patients')->ignore($patientId),
            ],
            'bpjs_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('patients')->ignore($patientId),
            ],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'in:male,female'],
            'blood_type' => ['nullable', 'in:A,B,AB,O,A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('patients')->ignore($patientId),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'religion' => ['nullable', 'in:islam,christian,catholic,hindu,buddha,confucian,other'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'education' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'spouse_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama pasien',
            'medical_record_number' => 'Nomor rekam medis',
            'nik' => 'NIK',
            'bpjs_number' => 'Nomor BPJS',
            'birth_date' => 'Tanggal lahir',
            'gender' => 'Jenis kelamin',
            'blood_type' => 'Golongan darah',
            'phone' => 'Nomor telepon',
            'email' => 'Email',
            'address' => 'Alamat',
            'region_id' => 'Wilayah',
            'religion' => 'Agama',
            'marital_status' => 'Status perkawinan',
            'occupation' => 'Pekerjaan',
            'education' => 'Pendidikan',
            'mother_name' => 'Nama ibu',
            'father_name' => 'Nama ayah',
            'spouse_name' => 'Nama pasangan',
            'emergency_contact_name' => 'Nama kontak darurat',
            'emergency_contact_relation' => 'Hubungan kontak darurat',
            'emergency_contact_phone' => 'Telepon kontak darurat',
            'is_active' => 'Status aktif',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pasien wajib diisi.',
            'name.max' => 'Nama pasien maksimal 100 karakter.',
            'nik.digits' => 'NIK harus 16 digit.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'bpjs_number.unique' => 'Nomor BPJS sudah terdaftar.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'gender.required' => 'Jenis kelamin wajib diisi.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'blood_type.in' => 'Golongan darah tidak valid.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'region_id.exists' => 'Wilayah tidak ditemukan.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up NIK and BPJS number - remove non-numeric characters
        if ($this->has('nik')) {
            $this->merge([
                'nik' => preg_replace('/[^0-9]/', '', $this->input('nik')),
            ]);
        }

        if ($this->has('bpjs_number')) {
            $this->merge([
                'bpjs_number' => preg_replace('/[^0-9]/', '', $this->input('bpjs_number')),
            ]);
        }

        // Set default is_active to true if not provided
        if (!$this->has('is_active')) {
            $this->merge([
                'is_active' => true,
            ]);
        }
    }
}
