<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Isolated helper regression checks; no database, network or financial mutations.
require __DIR__ . '/../config/auth_check.php';
require __DIR__ . '/../config/payment_verification.php';
function check($condition, $label) { if (!$condition) throw new RuntimeException($label); echo "PASS: $label\n"; }
check(safe_redirect_target('https://attacker.example') === '', 'Reject external redirect');
check(safe_redirect_target('//attacker.example') === '', 'Reject scheme-relative redirect');
check(safe_redirect_target(BASE_URL . '/dashboard.php') === BASE_URL . '/dashboard.php', 'Preserve internal redirect');
check(safe_redirect_target("/\\attacker.example") === '', 'Reject backslash redirect');
$_SESSION['csrf_token'] = 'test-token';
check(verify_csrf_token('test-token'), 'Accept matching CSRF');
check(!verify_csrf_token(['test-token']), 'Reject array CSRF without crashing');
check(!verify_csrf_token('wrong'), 'Reject invalid CSRF');
$response = ['transaction_code'=>'TEST-1','status'=>'COMPLETE','total_amount'=>'100.00','transaction_uuid'=>'ORDER-1','product_code'=>ESEWA_PRODUCT_CODE,'signed_field_names'=>'transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names'];
$parts=[];foreach(explode(',', $response['signed_field_names']) as $key) $parts[]=$key.'='.$response[$key];
$response['signature']=base64_encode(hash_hmac('sha256',implode(',',$parts),ESEWA_SECRET_KEY,true));
check(esewa_valid_signature($response), 'Accept authentic callback signature');
$response['total_amount']='1.00';
check(!esewa_valid_signature($response), 'Reject tampered callback amount');
$response['signed_field_names']='status';
check(!esewa_valid_signature($response), 'Reject incomplete signed fields');
check(esewa_amount_matches('100.00',100), 'Match numeric amount');
check(!esewa_amount_matches('99',100), 'Reject mismatched amount');
check(!esewa_amount_matches([],100), 'Reject malformed amount');
$_SESSION['user_id']=1;$_SESSION['user_role']='admin';$_SESSION['user_permissions']='["manage_resources"]';
check(admin_has_permission('manage_resources'), 'Allow assigned permission');
check(!admin_has_permission('manage_orders'), 'Reject unassigned permission');
echo "All checks passed.\n";
