<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * CSV log per campaign
 */
function log_csv($campaign_id, $email, $status, $reason=''){
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $file = $dir . '/campaign_' . $campaign_id . '.csv';
    $isNew = !file_exists($file);
    $fh = fopen($file, 'a');
    if ($isNew) {
        fputcsv($fh, ['date_time','email','status','reason']);
    }
    fputcsv($fh, [date('Y-m-d H:i:s'), $email, $status, $reason]);
    fclose($fh);
}

/**
 * DB log per recipient
 */
function log_db($campaign_id, $email, $status, $reason=''){
     $pdo = db();
     $check = $pdo->prepare('SELECT id FROM campaign_logs WHERE campaign_id=? AND email=?');
     $check->execute([$campaign_id, $email]);
     if($check->fetch()){ return true; } else{
    $st = $pdo->prepare('INSERT INTO campaign_logs (campaign_id,email,status,error,logged_at) VALUES (?,?,?,?,NOW())');
    $st->execute([$campaign_id, $email, $status, $reason ?: null]);
     }
}

/**
 * Mark sent
 */
function mark_sent($cr_id) {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE campaign_recipients SET status='sent', sent_at=NOW(), last_error=NULL WHERE id=?");
    $stmt->execute([$cr_id]);
}

/**
 * Mark failed
 */
function mark_failed($cr_id, $err) {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE campaign_recipients SET status='failed', last_error=? WHERE id=?");
    $stmt->execute([mb_substr($err,0,1000), $cr_id]);
}

/**
 * Replace public image URLs with CID and embed those images.
 * - Scans for <img src="/uploads/campaign_{id}/images/...">
 * - Adds each file with AddEmbeddedImage
 * - Replaces src with cid:imgN
 * Returns array: [patchedHtml, [cidMap]]
 */
function embed_campaign_images_with_cid(PHPMailer $mail, array $campaign, string $html): array {
    $id = (int)$campaign['id'];

    // Accept both absolute and relative URLs that point into campaign images dir
    // Examples:
    //   /uploads/campaign_5/images/logo.png
    //   ../uploads/campaign_5/images/logo.png
    //   https://your.host/uploads/campaign_5/images/logo.png
    $pattern = '#(?P<full><img\s[^>]*?src=["\'](?P<src>[^"\']*/uploads/campaign_' . $id . '/images/[^"\']+)["\'][^>]*>)#i';

    $matches = [];
    if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
        // nothing to embed
        return [$html, []];
    }

    $cidMap = [];      // url => cid
    $used   = [];      // to avoid duplicate embedded images
    $index  = 1;

    foreach ($matches as $m) {
        $src = html_entity_decode($m['src'], ENT_QUOTES);

        if (isset($cidMap[$src])) {
            // already processed
            continue;
        }

        // Compute local filesystem path for this URL
        // Normalize to path relative to project root
        $urlPath = parse_url($src, PHP_URL_PATH);
        if (!$urlPath) $urlPath = $src;

        $local = realpath(__DIR__ . '/..' . $urlPath); // projectRoot + /uploads/campaign_X/images/...
        if (!$local || !is_file($local)) {
            // Try without realpath fallback (in case of symlinks)
            $fallback = __DIR__ . '/..' . $urlPath;
            if (!is_file($fallback)) {
                // Can't find fileskip CID for this image
                continue;
            }
            $local = $fallback;
        }

        // Avoid embedding same physical file twice
        if (isset($used[$local])) {
            $cid = $used[$local];
            $cidMap[$src] = $cid;
            continue;
        }

        $cid = 'img' . $index++;
        $filename = basename($local);

        try {
            $mail->AddEmbeddedImage($local, $cid, $filename);
            $cidMap[$src] = $cid;
            $used[$local] = $cid;
        } catch (\Throwable $e) {
            // If embedding fails, just skip CID for this image (will fall back to public URL)
            continue;
        }
    }

    if (!$cidMap) return [$html, []];

    // Replace all occurrences of URLs in HTML with cid:...
    $patched = $html;
    foreach ($cidMap as $url => $cid) {
        // Replace only inside src=""
        $patched = preg_replace(
            '#(<img\b[^>]*\bsrc=["\'])' . preg_quote($url, '#') . '(["\'][^>]*>)#i',
            '$1cid:' . $cid . '$2',
            $patched
        );
        // Optionally keep original URL for future reference (not rendered by clients)
        // e.g., add data-fallback="https://..." (clients ignore but you keep reference)
        $patched = preg_replace(
            '#(<img\b[^>]*\bsrc=["\']cid:' . preg_quote($cid, '#') . '["\'])#i',
            '$1 data-fallback="' . htmlspecialchars($url, ENT_QUOTES) . '"',
            $patched
        );
    }

    return [$patched, $cidMap];
}

/**
 * Send a batch
 */
function send_batch($campaign, $rows) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host        = envv('SMTP_HOST');
    $mail->SMTPAuth    = true;
    $mail->Username    = envv('SMTP_USER');
    $mail->Password    = envv('SMTP_PASS');
    $mail->SMTPSecure  = envv('SMTP_SECURE','tls');
    $mail->Port        = (int)envv('SMTP_PORT',587);
    $mail->SMTPAutoTLS = false;
    $mail->isHTML(true);

    $fromEmail = $campaign['from_email'] ?: envv('MAIL_FROM_ADDRESS');
    $fromName  = $campaign['from_name']  ?: envv('MAIL_FROM_NAME');
    $replyTo   = $campaign['reply_to_email'] ?: envv('MAIL_REPLY_TO');

    $mail->setFrom($fromEmail, $fromName);
    if (!empty($replyTo)) {
        $mail->addReplyTo($replyTo, $fromName);
    }

    // Preload attachments (PDF, etc.)
    $attachDir = __DIR__ . '/../uploads/campaign_' . $campaign['id'] . '/attachments';
    $attachFiles = [];
    if (is_dir($attachDir)) {
        foreach (scandir($attachDir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $attachDir . '/' . $f;
            if (is_file($path)) $attachFiles[] = $path;
        }
    }

    // Prepare HTML with CID-embedded images once (same body for all recipients)
    // Its okay to compute this once per batch as body is same for all.
    list($bodyWithCid, $cidMap) = embed_campaign_images_with_cid($mail, $campaign, $campaign['html']);
    $altBody = strip_tags($bodyWithCid);

    foreach ($rows as $r) {
        try {
            // Clean per message
            $mail->clearAllRecipients();
            $mail->clearCustomHeaders();
            $mail->clearAttachments();

            // Re-add embedded images each time (PHPMailer clears them with clearAttachments())
            if ($cidMap) {
                // Re-embed because clearAttachments() clears embedded as well.
                foreach ($cidMap as $url => $cid) {
                    $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;
                    $local = realpath(__DIR__ . '/..' . $urlPath);
                    if (!$local || !is_file($local)) {
                        $fallback = __DIR__ . '/..' . $urlPath;
                        if (!is_file($fallback)) continue;
                        $local = $fallback;
                    }
                    $mail->AddEmbeddedImage($local, $cid, basename($local));
                }
            }

            // Add recipient + content
            $mail->addAddress($r['email']);
            $mail->Subject = $campaign['subject'];
            $mail->Body    = $bodyWithCid;
            $mail->AltBody = $altBody;

            // SES headers
            $cfgset = envv('SES_CONFIG_SET','');
            if (!empty($cfgset)) {
                $mail->addCustomHeader('X-SES-CONFIGURATION-SET', $cfgset);
            }
            $mail->addCustomHeader('X-SES-MESSAGE-TAGS', 'campaign_id='.$campaign['id']);

            // Attach PDFs, etc.
            foreach ($attachFiles as $filePath) {
                $mail->addAttachment($filePath, basename($filePath));
            }

            // Send
            $mail->send();

            mark_sent($r['cr_id']);
            log_db($campaign['id'], $r['email'], 'sent', '');
            log_csv($campaign['id'], $r['email'], 'SENT', '');

        } catch (Exception $e) {
            $err = trim($mail->ErrorInfo ?: $e->getMessage());
            mark_failed($r['cr_id'], $err);
            log_db($campaign['id'], $r['email'], 'failed', $err);
            log_csv($campaign['id'], $r['email'], 'FAILED', $err);
            continue;
        }
    }
}
/**
 * SIMPLE MAIL FUNCTION
 * Used by Forgot Password / Inventory / System emails
 */
function sendMail($to, $subject, $htmlBody)
{
    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = envv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = envv('SMTP_USER');
        $mail->Password   = envv('SMTP_PASS');
        $mail->SMTPSecure = envv('SMTP_SECURE', 'tls');
        $mail->Port       = (int)envv('SMTP_PORT', 587);

        $mail->setFrom(
            envv('MAIL_FROM_ADDRESS'),
            envv('MAIL_FROM_NAME')
        );

        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('sendMail error: ' . $e->getMessage());
        return false;
    }
}
