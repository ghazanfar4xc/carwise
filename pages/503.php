<?php
/** AutoPulse — maintenance / 503 page. */
$SEO = $SEO ?? ['title' => 'Under Maintenance', 'robots' => 'noindex, nofollow'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('site_name', 'AutoPulse')) ?> — Under maintenance</title>
<meta name="robots" content="noindex, nofollow">
<style>
body{margin:0;font-family:-apple-system,"Segoe UI",Roboto,sans-serif;background:#0e1b2c;color:#e8edf4;display:grid;place-items:center;min-height:100vh;text-align:center;padding:1rem}
.box{max-width:520px}
h1{font-size:clamp(2rem,6vw,3rem);margin:0 0 .4em}
p{color:#a7b4c4;line-height:1.6}
.wheel{font-size:3rem;display:inline-block;animation:spin 3s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="box">
    <span class="wheel">🔧</span>
    <h1>We'll be right back</h1>
    <p><?= e(setting('site_name', 'AutoPulse')) ?> is undergoing scheduled maintenance. Everything will be back on the road shortly — please try again in a little while.</p>
</div>
</body>
</html>
