<?php

return [
    'mode' => env('SATUSEHAT_MODE', 'development'),

    'development' => [
        'auth_url' => 'https://api-satusehat-stg.kemkes.go.id/oauth2/v1',
        'base_url' => 'https://api-satusehat-stg.kemkes.go.id/fhir-r4/v1',
        'client_id' => env('SATUSEHAT_CLIENT_ID'),
        'client_secret' => env('SATUSEHAT_CLIENT_SECRET'),
        'organization_id' => env('SATUSEHAT_ORGANIZATION_ID'),
    ],

    'production' => [
        'auth_url' => env('SATUSEHAT_AUTH_URL', 'https://api-satusehat.kemkes.go.id/oauth2/v1'),
        'base_url' => env('SATUSEHAT_BASE_URL', 'https://api-satusehat.kemkes.go.id/fhir-r4/v1'),
        'client_id' => env('SATUSEHAT_CLIENT_ID'),
        'client_secret' => env('SATUSEHAT_CLIENT_SECRET'),
        'organization_id' => env('SATUSEHAT_ORGANIZATION_ID'),
    ],

    'timeout' => 60,
    'retry_times' => 3,
    'retry_sleep' => 1000,

    'cache_token' => true,
    'token_cache_key' => 'satusehat_access_token',
    'token_expires_in' => 3500,

    'resources' => [
        'patient' => 'Patient',
        'encounter' => 'Encounter',
        'observation' => 'Observation',
        'condition' => 'Condition',
        'procedure' => 'Procedure',
        'medication' => 'Medication',
        'medication_request' => 'MedicationRequest',
        'organization' => 'Organization',
        'location' => 'Location',
        'practitioner' => 'Practitioner',
    ],
];
