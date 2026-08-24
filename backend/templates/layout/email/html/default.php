<?php
/**
 * Branded HTML email layout.
 *
 * @var \App\View\AppView $this
 */

$title = trim($this->fetch('title')) ?: 'LBA Scouts District Badge Shop';
$preheader = trim($this->fetch('preheader'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title><?= h($title) ?></title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; }
            .email-gutter { padding-right: 20px !important; padding-left: 20px !important; }
            .email-card { padding: 30px 22px !important; }
            .email-heading { font-size: 32px !important; }
            .order-meta-cell { display: block !important; width: 100% !important; padding: 0 0 16px !important; }
            .item-price { width: 74px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f5f8f8; color:#172329; font-family:'Nunito Sans','Segoe UI',Arial,sans-serif; -webkit-text-size-adjust:100%;">
<?php if ($preheader !== '') : ?>
    <div style="display:none; overflow:hidden; max-height:0; max-width:0; opacity:0; color:transparent; mso-hide:all;"><?= h($preheader) ?></div>
<?php endif; ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background:#f5f8f8; border-collapse:collapse;">
    <tr>
        <td class="email-gutter" align="center" style="padding:34px 24px 48px;">
            <table class="email-shell" role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px; max-width:600px; border-collapse:collapse;">
                <tr>
                    <td style="padding:0 4px 24px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;"><tr>
                            <td width="54" height="54" align="center" valign="middle" style="width:54px; height:54px; border-radius:16px 5px 16px 5px; background:#7413dc; color:#ffffff; font-size:13px; font-weight:900; line-height:54px; box-shadow:0 8px 22px rgba(116,19,220,.18);">LBA</td>
                            <td style="padding-left:14px; color:#172329;"><div style="font-size:18px; font-weight:900; line-height:1.15;">LBA Scouts</div><div style="padding-top:5px; color:#66747b; font-size:11px; font-weight:800; letter-spacing:1px; line-height:1.15; text-transform:uppercase;">District Badge Shop</div></td>
                        </tr></table>
                    </td>
                </tr>
                <tr><td class="email-card" style="padding:42px 44px; border:1px solid #dfe7e7; border-radius:18px; background:#ffffff; box-shadow:0 16px 45px rgba(19,54,56,.08);"><?= $this->fetch('content') ?></td></tr>
                <tr><td align="center" style="padding:28px 20px 0; color:#66747b; font-size:12px; line-height:1.65;"><div style="font-weight:800; color:#0c3436;">Letchworth, Baldock &amp; Ashwell Scouts</div><div>Preparing young people with <span style="color:#25b755; font-weight:900;">#SkillsForLife</span></div><div style="padding-top:10px; color:#86aaab;">Registered Charity 279860</div></td></tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
