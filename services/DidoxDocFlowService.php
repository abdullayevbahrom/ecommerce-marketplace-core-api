<?php

namespace app\services;

class DidoxDocFlowService
{
    /** @var DidoxService */
    private $didoxService;

    public function __construct(?DidoxService $didoxService = null)
    {
        $this->didoxService = $didoxService ?: new DidoxService();
    }

    public function loginWithEimzo(string $taxId, string $signature): array
    {
        $taxId = trim($taxId);
        $signature = trim($signature);
        if ($taxId === '' || $signature === '') {
            return ['success' => false, 'error' => 'taxId and signature are required'];
        }

        $result = $this->didoxService->authenticateWithEimzo($taxId, $signature);
        if (empty($result['success']) || empty($result['token'])) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to authenticate in DIDOX',
                'details' => $result,
            ];
        }

        return [
            'success' => true,
            'user_key' => $result['token'],
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? 'DIDOX auth success',
        ];
    }

    public function getIncomingToSign(string $didoxId, string $userKey): array
    {
        $didoxId = trim($didoxId);
        $userKey = trim($userKey);
        if ($didoxId === '' || $userKey === '') {
            return ['success' => false, 'error' => 'didox_id and user_key are required'];
        }

        $result = $this->didoxService->getIncomingDocumentForSigning($didoxId, $userKey);
        if (empty($result['success'])) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to get incoming payload',
                'debug' => $result['debug'] ?? null,
                'details' => $result,
            ];
        }

        $toSign = trim((string)($result['data']['toSign'] ?? ''));
        $json = $result['data']['json'] ?? null;
        $jsonBase64 = null;
        if (is_string($json) && trim($json) !== '') {
            $jsonBase64 = base64_encode($json);
        }

        $payloadCandidates = [];
        foreach ([$toSign, $jsonBase64] as $candidate) {
            $candidate = is_string($candidate) ? trim($candidate) : '';
            if ($candidate !== '' && !in_array($candidate, $payloadCandidates, true)) {
                $payloadCandidates[] = $candidate;
            }
        }

        $docBase64Res = $this->didoxService->getIncomingDocumentBase64($didoxId, $userKey);
        $documentBase64 = '';
        if (!empty($docBase64Res['success'])) {
            $documentBase64 = trim((string)($docBase64Res['data']['documentBase64'] ?? ''));
        }
        // Fallback: if documentBase64 endpoint does not return payload,
        // extract JSON part from toSign and encode back to base64.
        if ($documentBase64 === '' && $toSign !== '') {
            $documentBase64 = $this->extractJsonBase64FromToSign($toSign) ?? '';
        }
        if ($documentBase64 === '' && is_string($jsonBase64) && trim($jsonBase64) !== '') {
            $documentBase64 = trim($jsonBase64);
        }

        return [
            'success' => true,
            'data' => [
                'didox_id' => $didoxId,
                'to_sign_value' => $toSign,
                'document_base64' => $documentBase64,
                'json_base64' => $jsonBase64,
                'sign_payload_candidates' => $payloadCandidates,
                'debug_info' => [
                    'incoming_sign' => $result['debug'] ?? null,
                    'document_base64' => $docBase64Res['data'] ?? null,
                    'document_base64_error' => empty($docBase64Res['success']) ? ($docBase64Res['error'] ?? null) : null,
                    'document_base64_fallback_used' => $documentBase64 !== '' && empty($docBase64Res['success']),
                ],
            ],
        ];
    }

    private function extractJsonBase64FromToSign(string $toSignB64): ?string
    {
        $raw = base64_decode(trim($toSignB64), true);
        if ($raw === false || $raw === '') {
            return null;
        }
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $jsonCandidate = substr($raw, $start, $end - $start + 1);
        $decoded = json_decode($jsonCandidate, true);
        if (!is_array($decoded)) {
            return null;
        }
        return base64_encode($jsonCandidate);
    }

    public function acceptIncoming(string $didoxId, string $signature, string $userKey, array $signatureCandidates = []): array
    {
        $didoxId = trim($didoxId);
        $signature = trim($signature);
        $userKey = trim($userKey);
        if ($didoxId === '' || $signature === '' || $userKey === '') {
            return ['success' => false, 'error' => 'didox_id, signature and user_key are required'];
        }

        $candidates = [];
        foreach (array_merge([$signature], $signatureCandidates) as $candidate) {
            $candidate = is_string($candidate) ? trim($candidate) : '';
            if ($candidate !== '' && !in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        }

        $result = $this->didoxService->acceptIncomingDocument($didoxId, $candidates, $userKey);
        if (empty($result['success'])) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to accept incoming document',
                'debug' => $result['debug'] ?? null,
                'details' => $result,
            ];
        }

        return [
            'success' => true,
            'data' => $result['data'] ?? null,
            'httpCode' => $result['httpCode'] ?? null,
            'debug' => $result['debug'] ?? null,
            'message' => 'Incoming document accepted',
        ];
    }

    /**
     * Didox v6.3.5 doc flow:
     * 1) signature1 = toSign payload signature
     * 2) signature2 = documentBase64 signature with timestamp
     * 3) join signatures
     * 4) send joined pkcs7B64 to /documents/{id}/sign
     */
    public function acceptIncomingByJoin(
        string $didoxId,
        string $signature1,
        string $signature2,
        string $userKey,
        array $signature2Candidates = [],
        array $signature1Candidates = []
    ): array {
        $didoxId = trim($didoxId);
        $signature1 = trim($signature1);
        $signature2 = trim($signature2);
        $userKey = trim($userKey);
        if ($didoxId === '' || $signature1 === '' || $signature2 === '' || $userKey === '') {
            return ['success' => false, 'error' => 'didox_id, signature1, signature2 and user_key are required'];
        }

        // Keep signature1 strict: primary only (toSign signature).
        $candidates1 = [$signature1];

        $candidates = [];
        foreach (array_merge([$signature2], $signature2Candidates) as $cand) {
            $cand = is_string($cand) ? trim($cand) : '';
            if ($cand !== '' && !in_array($cand, $candidates, true)) {
                $candidates[] = $cand;
                // Keep signature2 narrow: primary + at most one fallback.
                if (count($candidates) >= 2) {
                    break;
                }
            }
        }
        if (empty($candidates1) || empty($candidates)) {
            return [
                'success' => false,
                'error' => 'signature1/signature2 candidates are empty',
            ];
        }

        $joinRes = null;
        $joinAttempts = [];
        $acceptAttempts = [];
        $finalAcceptRes = null;
        $finalJoinRes = null;
        $joined = '';
        foreach ($candidates1 as $i1 => $cand1) {
            foreach ($candidates as $i2 => $cand2) {
                $res = $this->didoxService->joinSignatures($cand1, $cand2, $userKey);
                $joinAttempts[] = [
                    'signature1_index' => $i1,
                    'signature1_length' => strlen($cand1),
                    'signature2_index' => $i2,
                    'signature2_length' => strlen($cand2),
                    'success' => !empty($res['success']),
                    'httpCode' => $res['httpCode'] ?? null,
                    'error' => $res['error'] ?? null,
                ];
                if (!empty($res['success'])) {
                    $joined = trim((string)($res['data']['pkcs7B64'] ?? ''));
                    $joinRes = $res;
                    if ($joined === '') {
                        continue;
                    }

                    // Try accept immediately for each successful join candidate.
                    // Some timestamp chains are rejected by DIDOX, so we continue until one works.
                    $acceptRes = $this->didoxService->acceptIncomingDocument($didoxId, [$joined], $userKey);
                    $acceptAttempts[] = [
                        'signature1_index' => $i1,
                        'signature2_index' => $i2,
                        'joined_length' => strlen($joined),
                        'success' => !empty($acceptRes['success']),
                        'httpCode' => $acceptRes['httpCode'] ?? null,
                        'error' => $acceptRes['error'] ?? null,
                    ];

                    if (!empty($acceptRes['success'])) {
                        $finalAcceptRes = $acceptRes;
                        $finalJoinRes = $res;
                        break 2;
                    }
                } else {
                    $joinRes = $res;
                }
            }
        }

        if ($finalAcceptRes !== null) {
            return [
                'success' => true,
                'data' => $finalAcceptRes['data'] ?? null,
                'httpCode' => $finalAcceptRes['httpCode'] ?? null,
                'debug' => [
                    'join' => $finalJoinRes['data'] ?? null,
                    'join_attempts' => $joinAttempts,
                    'accept_attempts' => $acceptAttempts,
                    'accept' => $finalAcceptRes['debug'] ?? null,
                ],
                'message' => 'Incoming document accepted via join flow',
            ];
        }

        if ($joined === '') {
            return [
                'success' => false,
                'error' => 'Join returned empty pkcs7B64',
                'debug' => [
                    'join' => $joinRes,
                    'join_attempts' => $joinAttempts,
                    'accept_attempts' => $acceptAttempts,
                ],
            ];
        }

        return [
            'success' => false,
            'error' => 'Joined signatures were generated, but DIDOX rejected all accept attempts',
            'debug' => [
                'join' => $joinRes,
                'join_attempts' => $joinAttempts,
                'accept_attempts' => $acceptAttempts,
            ],
        ];
    }
}
