<?php
require __DIR__ . '/../vendor/autoload.php';

$app = App\Application::create();

$ok = false;
$code = $_GET['code'] ?? null;
if ($code !== null) {
    $app->authorization($code);
    $ok = true;
}

$authorization_url = $app->get_authorization_url();

?>
<html>
<body>
    <a href="<?= htmlspecialchars($authorization_url) ?>">authorization</a>
    <?php if($ok): ?>
        OK
    <?php endif; ?>
</body>
</html>
