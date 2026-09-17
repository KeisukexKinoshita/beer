<?php
/*
 * アップロードされた写真を、**本人にだけ**配る。
 *
 * img/upload/ は nginx で塞いである(公開面に出さない約束。設計書 §4)。
 * 本人が自分の写真を見るための唯一の経路がここ。
 */
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/repo.php';

$uploadId = (isset($_GET['u']) && ctype_digit((string)$_GET['u'])) ? (int)$_GET['u'] : 0;
if ($uploadId <= 0) { http_response_code(404); exit; }

$visitorId = visitor_current();
$row = upload_owned_by($uploadId, $visitorId);
if (!$row || empty($row['image_path'])) { http_response_code(404); exit; }

// DB の値であっても、パスの形を検査してから開く。
// image_path は「img/upload/<sha256>.jpg」以外にはなりえない。
if (!preg_match('#^img/upload/[0-9a-f]{64}\.jpg$#', (string)$row['image_path'])) {
    http_response_code(404); exit;
}
$path = __DIR__ . '/' . $row['image_path'];
if (!is_file($path)) { http_response_code(404); exit; }

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
readfile($path);
