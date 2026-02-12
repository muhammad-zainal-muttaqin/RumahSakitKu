<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Services\BPJS\BpjsVclaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * BPJS Integration API Controller.
 *
 * Handles BPJS integration for patient verification, SEP creation,
 * and referral management.
 */
class BpjsController extends BaseController
{
    /**
     * BPJS Service instance.
     */
    protected BpjsVclaimService $bpjsService;

    /**
     * Constructor.
     *
     * @param BpjsVclaimService $bpjsService
     */
    public function __construct(BpjsVclaimService $bpjsService)
    {
        $this->bpjsService = $bpjsService;
    }

    /**
     * Get BPJS participant data by NIK.
     *
     * @param Request $request
     * @param string $nik
     * @return JsonResponse
     */
    public function getParticipant(Request $request, string $nik): JsonResponse
    {
        $validator = Validator::make(['nik' => $nik], [
            'nik' => ['required', 'digits:16'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $response = $this->bpjsService->getParticipantByNIK($nik);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch participant data',
                    400,
                    $response['errors'] ?? null
                );
            }

            return $this->successResponse([
                'participant' => $response['data'] ?? null,
                'coverage' => $response['coverage'] ?? null,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get BPJS participant data by card number.
     *
     * @param Request $request
     * @param string $cardNumber
     * @return JsonResponse
     */
    public function getParticipantByCard(Request $request, string $cardNumber): JsonResponse
    {
        $validator = Validator::make(['card_number' => $cardNumber], [
            'card_number' => ['required', 'digits:13'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $response = $this->bpjsService->getParticipantByCardNumber($cardNumber);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch participant data',
                    400
                );
            }

            return $this->successResponse([
                'participant' => $response['data'] ?? null,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create SEP (Surat Eligibilitas Peserta).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createSep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noKartu' => ['required', 'string', 'size:13'],
            'tglSep' => ['required', 'date'],
            'ppkPelayanan' => ['required', 'string'],
            'jnsPelayanan' => ['required', 'in:1,2'], // 1=Rawat Inap, 2=Rawat Jalan
            'klsRawat' => ['required', 'array'],
            'klsRawat.klsRawatHak' => ['required', 'string'],
            'noMR' => ['required', 'string'],
            'rujukan' => ['nullable', 'array'],
            'rujukan.asalRujukan' => ['required_with:rujukan', 'string'],
            'rujukan.tglRujukan' => ['required_with:rujukan', 'date'],
            'rujukan.noRujukan' => ['required_with:rujukan', 'string'],
            'rujukan.ppkRujukan' => ['required_with:rujukan', 'string'],
            'catatan' => ['nullable', 'string'],
            'diagAwal' => ['required', 'string'],
            'poli' => ['required', 'array'],
            'poli.tujuan' => ['required', 'string'],
            'poli.eksekutif' => ['required', 'string', 'in:0,1'],
            'cob' => ['array'],
            'katarak' => ['array'],
            'jaminan' => ['nullable', 'array'],
            'tujuanKunj' => ['required', 'string'],
            'flagProcedure' => ['nullable', 'string'],
            'kdPenunjang' => ['nullable', 'string'],
            'assesmentPel' => ['nullable', 'string'],
            'skdp' => ['nullable', 'array'],
            'dpjpLayan' => ['nullable', 'string'],
            'noTelp' => ['required', 'string'],
            'user' => ['required', 'string'],
        ]);

        try {
            $response = $this->bpjsService->createSEP($validated);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to create SEP',
                    400,
                    $response['errors'] ?? null
                );
            }

            return $this->createdResponse([
                'sep' => $response['data'] ?? null,
            ], 'SEP created successfully');
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get SEP by SEP number.
     *
     * @param Request $request
     * @param string $sepNumber
     * @return JsonResponse
     */
    public function getSep(Request $request, string $sepNumber): JsonResponse
    {
        try {
            $response = $this->bpjsService->getSEP($sepNumber);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch SEP',
                    400
                );
            }

            return $this->successResponse([
                'sep' => $response['data'] ?? null,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete SEP.
     *
     * @param Request $request
     * @param string $sepNumber
     * @return JsonResponse
     */
    public function deleteSep(Request $request, string $sepNumber): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
        ]);

        try {
            $response = $this->bpjsService->deleteSEP($sepNumber, $validated['reason']);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to delete SEP',
                    400
                );
            }

            return $this->successResponse(null, 'SEP deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update SEP.
     *
     * @param Request $request
     * @param string $sepNumber
     * @return JsonResponse
     */
    public function updateSep(Request $request, string $sepNumber): JsonResponse
    {
        $validated = $request->validate([
            'noKartu' => ['required', 'string', 'size:13'],
            'tglSep' => ['required', 'date'],
            'noMR' => ['required', 'string'],
            'catatan' => ['nullable', 'string'],
            'diagAwal' => ['required', 'string'],
            'poli' => ['required', 'array'],
            'poli.tujuan' => ['required', 'string'],
            'poli.eksekutif' => ['required', 'string', 'in:0,1'],
            'klsRawat' => ['required', 'array'],
            'klsRawat.klsRawatHak' => ['required', 'string'],
            'noTelp' => ['required', 'string'],
        ]);

        try {
            $response = $this->bpjsService->updateSEP($sepNumber, $validated);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to update SEP',
                    400
                );
            }

            return $this->successResponse([
                'sep' => $response['data'] ?? null,
            ], 'SEP updated successfully');
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get referral data by referral number.
     *
     * @param Request $request
     * @param string $noRujukan
     * @return JsonResponse
     */
    public function getReferral(Request $request, string $noRujukan): JsonResponse
    {
        $type = $request->input('type', 'pcare'); // pcare or rs

        try {
            $response = $this->bpjsService->getRujukan($noRujukan, $type);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch referral data',
                    400
                );
            }

            return $this->successResponse([
                'referral' => $response['data'] ?? null,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get list of referrals by participant card number.
     *
     * @param Request $request
     * @param string $cardNumber
     * @return JsonResponse
     */
    public function getReferralList(Request $request, string $cardNumber): JsonResponse
    {
        $type = $request->input('type', 'pcare');

        try {
            $response = $this->bpjsService->getRujukanList($cardNumber, $type);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch referral list',
                    400
                );
            }

            return $this->successResponse([
                'referrals' => $response['data'] ?? [],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get FKTP (Fasilitas Kesehatan Tingkat Pertama) list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFktpList(Request $request): JsonResponse
    {
        $provinceCode = $request->input('province_code');
        $regencyCode = $request->input('regency_code');

        try {
            $response = $this->bpjsService->getFaskes($provinceCode, $regencyCode, 1); // 1 = FKTP

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch FKTP list',
                    400
                );
            }

            return $this->successResponse([
                'faskes' => $response['data'] ?? [],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get FKTL (Fasilitas Kesehatan Tingkat Lanjutan) list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFktlList(Request $request): JsonResponse
    {
        $provinceCode = $request->input('province_code');
        $regencyCode = $request->input('regency_code');

        try {
            $response = $this->bpjsService->getFaskes($provinceCode, $regencyCode, 2); // 2 = FKTL

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch FKTL list',
                    400
                );
            }

            return $this->successResponse([
                'faskes' => $response['data'] ?? [],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get diagnosis list (ICD-10).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDiagnosis(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 3) {
            return $this->errorResponse('Query must be at least 3 characters', 422);
        }

        try {
            $response = $this->bpjsService->searchDiagnosis($query);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch diagnosis',
                    400
                );
            }

            return $this->successResponse([
                'diagnosis' => $response['data'] ?? [],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get procedure list (ICD-9CM).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProcedures(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 3) {
            return $this->errorResponse('Query must be at least 3 characters', 422);
        }

        try {
            $response = $this->bpjsService->searchProcedure($query);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch procedures',
                    400
                );
            }

            return $this->successResponse([
                'procedures' => $response['data'] ?? [],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get polyclinic list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPoliList(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 3) {
            return $this->errorResponse('Query must be at least 3 characters', 422);
        }

        try {
            $response = $this->bpjsService->searchPoli($query);

            if (!$response['success']) {
                return $this->errorResponse(
                    $response['message'] ?? 'Failed to fetch polyclinic list',
                    400
                );
            }

            return $this->successResponse([
                'poli' => $response['data'] ?? [],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('BPJS service error: ' . $e->getMessage(), 500);
        }
    }
}
