<?php
/** AutoPulse — friendly 500 page (production). */
$SEO = $SEO ?? ['title' => 'Something Went Wrong', 'robots' => 'noindex, nofollow'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('site_name', 'AutoPulse')) ?> — Error</title>
<meta name="robots" content="noindex, nofollow">
<style>
body{margin:0;font-family:-apple-system,"Segoe UI",Roboto,sans-serif;background:#0e1b2c;color:#e8edf4;display:grid;place-items:center;min-height:100vh;text-align:center;padding:1rem}
h1{font-size:clamp(2rem,6vw,3rem);margin:0 0 .4em}
p{color:#a7b4c4;line-height:1.6}
</style>
</head>
<body>
<div style="max-width:520px">
    <h1>Engine trouble</h1>
    <p>Something went wrong on our side. The issue has been logged and we're on it. Please try again in a moment.</p>
    <p><a href="/" style="color:#e43849">Back to homepage</a></p>
</div>
</body>
</html>
