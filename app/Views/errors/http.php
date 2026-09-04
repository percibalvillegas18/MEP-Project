<?php
$pageTitle = "Error {$status}";
$debug = $debug ?? false;
$details = $details ?? [];
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=$escape($pageTitle)?></title>
    <style>
        :root{color-scheme:light;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;color:#172033;background:#f3f6fa}*{box-sizing:border-box}body{margin:0}.error-shell{width:min(1180px,calc(100% - 32px));margin:40px auto}.error-card{background:#fff;border:1px solid #dbe3ee;border-radius:14px;box-shadow:0 14px 35px rgba(21,34,50,.08);overflow:hidden}.error-head{padding:24px 28px;background:#991b1b;color:#fff}.error-head h1{font-size:24px;margin:0 0 8px}.error-head p{margin:0;color:#fee2e2}.debug-banner{padding:10px 28px;background:#fef3c7;color:#713f12;font-weight:700}.error-body{padding:26px 28px}.exact{padding:16px;border-left:5px solid #dc2626;background:#fef2f2;border-radius:8px;margin-bottom:20px}.exact strong{display:block;color:#991b1b;margin-bottom:6px}.exact code{white-space:pre-wrap;word-break:break-word}.facts{display:grid;grid-template-columns:180px minmax(0,1fr);border:1px solid #dbe3ee;border-radius:10px;overflow:hidden;margin:0 0 20px}.facts dt,.facts dd{margin:0;padding:10px 12px;border-bottom:1px solid #e8edf3}.facts dt{font-weight:700;background:#f7f9fc}.facts dd{word-break:break-word}.facts dt:last-of-type,.facts dd:last-of-type{border-bottom:0}h2{font-size:17px;margin:24px 0 10px}.source,.trace{background:#111827;color:#e5e7eb;border-radius:10px;overflow:auto;padding:14px;font:13px/1.6 ui-monospace,SFMono-Regular,Consolas,monospace}.source-line{display:block;white-space:pre}.source-line mark{display:inline-block;width:100%;background:#7f1d1d;color:#fff}.line-no{display:inline-block;width:52px;color:#94a3b8;user-select:none}.trace{white-space:pre-wrap;word-break:break-word;max-height:360px}.cause{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px;color:#1e3a8a}.footer-actions{margin-top:22px}.btn{display:inline-block;padding:10px 15px;border-radius:8px;background:#1d4ed8;color:#fff;text-decoration:none;font-weight:700}.warning{margin-top:20px;color:#7f1d1d;font-weight:700}@media(max-width:700px){.error-shell{margin:16px auto}.error-head,.error-body,.debug-banner{padding-left:18px;padding-right:18px}.facts{grid-template-columns:1fr}.facts dt{border-bottom:0;padding-bottom:3px}.facts dd{padding-top:3px}}
    </style>
</head>
<body><main class="error-shell"><section class="error-card">
    <header class="error-head"><h1>HTTP <?=$escape($status)?> — Application Error</h1><p><?=$escape($message)?></p></header>
    <?php if ($debug): ?><div class="debug-banner">Development diagnostics are ON — disable MEP_APP_DEBUG before production deployment.</div><?php endif; ?>
    <div class="error-body">
    <?php if ($debug && $details): ?>
        <div class="exact"><strong><?=$escape($details['exception'])?></strong><code><?=$escape($details['message'])?></code></div>
        <dl class="facts">
            <dt>File and line</dt><dd><code><?=$escape($details['file'])?>:<?=$escape($details['line'])?></code></dd>
            <dt>Controller / class</dt><dd><code><?=$escape($details['handler'])?></code></dd>
            <dt>Action file</dt><dd><code><?=$escape($details['action_file'])?></code></dd>
            <dt>Form action</dt><dd><code><?=$escape($details['form_action'])?></code></dd>
            <dt>Request</dt><dd><code><?=$escape($details['method'])?> <?=$escape($details['route'])?></code> via <?=$escape($details['script'])?></dd>
            <dt>Submitted fields</dt><dd><code><?=$escape(implode(', ', $details['submitted_fields']) ?: 'None')?></code> (values and sensitive fields are not displayed)</dd>
            <dt>Request ID</dt><dd><code><?=$escape($details['request_id'])?></code></dd>
        </dl>
        <h2>Possible cause</h2><div class="cause"><?=$escape($details['possible_cause'])?></div>
        <?php if ($details['source']): ?><h2>Source syntax near the error</h2><div class="source"><?php foreach ($details['source'] as $sourceLine): ?><span class="source-line"><?php if ($sourceLine['error']): ?><mark><span class="line-no"><?=$escape($sourceLine['number'])?></span><?=$escape($sourceLine['text'])?></mark><?php else: ?><span class="line-no"><?=$escape($sourceLine['number'])?></span><?=$escape($sourceLine['text'])?><?php endif; ?></span><?php endforeach; ?></div><?php endif; ?>
        <h2>Stack trace</h2><div class="trace"><?=$escape($details['trace'] ?: '(No stack trace was supplied for this fatal error.)')?></div>
        <p class="warning">This page may reveal internal paths and database details. Use it only for debugging/testing.</p>
    <?php endif; ?>
        <div class="footer-actions"><a class="btn" href="index.php?route=dashboard">Return to Dashboard</a></div>
    </div>
</section></main></body></html>
