<?php
/** Fail-closed eSewa verification; no callback can grant access on its own. */
function esewa_valid_signature(array $response): bool {
    $fields = 'transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names';
    if (($response['signed_field_names'] ?? '') !== $fields) return false;
    $parts = [];
    foreach (explode(',', $fields) as $field) {
        if (!isset($response[$field]) || !is_scalar($response[$field])) return false;
        $parts[] = $field . '=' . $response[$field];
    }
    $expected = base64_encode(hash_hmac('sha256', implode(',', $parts), ESEWA_SECRET_KEY, true));
    return is_string($response['signature'] ?? null) && hash_equals($expected, $response['signature']);
}
function esewa_amount_matches($actual, $expected): bool {
    return is_scalar($actual) && is_numeric($actual) && (float)$actual >= 0
        && number_format((float)$actual, 2, '.', '') === number_format((float)$expected, 2, '.', '');
}
function esewa_verify_order(array $response, array $order): bool {
    if (!esewa_valid_signature($response) || $response['status'] !== 'COMPLETE'
        || $response['product_code'] !== ESEWA_PRODUCT_CODE
        || $response['transaction_uuid'] !== $order['transaction_uuid']
        || empty($response['transaction_code'])
        || !esewa_amount_matches($response['total_amount'], $order['amount'])
        || !function_exists('curl_init')) return false;
    if (parse_url(ESEWA_STATUS_URL, PHP_URL_SCHEME) !== 'https') return false;
    $url = ESEWA_STATUS_URL . '?' . http_build_query([
        'product_code' => ESEWA_PRODUCT_CODE, 'transaction_uuid' => $order['transaction_uuid'],
        'total_amount' => $order['amount']
    ]);
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2]);
    $body = curl_exec($curl);
    $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $status = is_string($body) ? json_decode($body, true) : null;
    return $http === 200 && is_array($status) && ($status['status'] ?? '') === 'COMPLETE'
        && ($status['transaction_uuid'] ?? '') === $order['transaction_uuid']
        && ($status['product_code'] ?? '') === ESEWA_PRODUCT_CODE
        && ($status['ref_id'] ?? '') === $response['transaction_code']
        && esewa_amount_matches($status['total_amount'] ?? null, $order['amount']);
}
