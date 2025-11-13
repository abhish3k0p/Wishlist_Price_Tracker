<?php
function h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function json_ok($data=[]){header('Content-Type: application/json');echo json_encode(['ok'=>true,'data'=>$data]);exit;}
function json_err($msg, $code=400){http_response_code($code);header('Content-Type: application/json');echo json_encode(['ok'=>false,'error'=>$msg]);exit;}
function sanitize_url($url){$url=trim($url);return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;}
