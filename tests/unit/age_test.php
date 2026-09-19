<?php
$_SERVER['DOCUMENT_ROOT'] = '/workspace/tool/beer';
require_once __DIR__ . '/../../common/reco/age.php';
require_once __DIR__ . '/../../common/reco/repo.php';

$vid = 'age' . str_repeat('0', 29);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE age_confirmed = 0")->execute([':v' => $vid]);

eq(age_confirmed($vid), false, '初めての訪問者は未確認');
age_confirm($vid);
eq(age_confirmed($vid), true,  '確認したら記録される');
eq(age_confirmed('存在しない訪問者' . str_repeat('0', 16)), false, '未知の訪問者は未確認');

db()->prepare("DELETE FROM visitor WHERE visitor_id = :v")->execute([':v' => $vid]);
