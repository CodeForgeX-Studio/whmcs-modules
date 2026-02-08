<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function hooks_load_translation($language) {
    $filePath = __DIR__ . "/translations/{$language}.php";
    if (file_exists($filePath)) {
        return include $filePath;
    }
    return include __DIR__ . "/translations/en.php";
}

function add_to_spam_reports($spamReportReason, $clientId, $ticketId) {
    Capsule::table('tblticketspamcheckspamreports')->insert([
        'client_id' => $clientId,
        'ticket_id' => $ticketId,
        'reason' => $spamReportReason,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

function messages_add_to_spam_reports($spamReportReason, $clientId, $ticketId) {
    Capsule::table('tblticketspamcheckspamreports')->insert([
        'client_id' => $clientId,
        'ticket_id' => $ticketId,
        'reason' => $spamReportReason,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

$currentSetting = Capsule::table('tblticketspamcheckdashboardsettings')->first();
$language = $currentSetting->language ?? 'en';
$translations = hooks_load_translation($language);

$GLOBALS['spamcheck_ticketid'] = null;

add_hook('TicketOpen', 1, function ($vars) use ($translations) {
    $settings = Capsule::table('tblticketspamcheckdashboardsettings')->first();
    $userId = $vars['userid'];
    $ticketId = $vars['ticketid'];
    $maxTickets = $settings->max_tickets ?? 5;
    $timeLimit = $settings->time_limit ?? 300;
    $currentTime = time();
    $ticketCount = Capsule::table('tbltickets')
        ->where('userid', $userId)
        ->where('status', 'Open')
        ->where('date', '>=', date('Y-m-d H:i:s', $currentTime - $timeLimit))
        ->count();
    if ($ticketCount >= $maxTickets) {
        Capsule::table('tbltickets')->where('id', $ticketId)->update(['status' => 'Flagged as Spam']);
        $userInfo = Capsule::table('tblclients')->where('id', $userId)->first();
        $email = $userInfo->email;
        $name = $userInfo->firstname . ' ' . $userInfo->lastname;
        add_to_spam_reports($translations['spam_report_reason'], $userId, $ticketId);
    }
});

add_hook('TicketUserReply', 1, function ($vars) use ($translations) {
    $ticketId = $vars['ticketid'];
    $userId = $vars['userid'];
    $recentReplies = Capsule::table('tblticketreplies')
        ->where('userid', $userId)
        ->where('tid', $ticketId)
        ->orderBy('date', 'desc')
        ->limit(5)
        ->get();

    $timestamps = $recentReplies->pluck('date')->map(function ($d) {
        return strtotime($d);
    })->toArray();

    if (count($timestamps) >= 5) {
        $first = end($timestamps);
        $last = reset($timestamps);
        if (($last - $first) <= 60) {
            Capsule::table('tbltickets')->where('id', $ticketId)->update(['status' => 'Flagged as Spam']);
            $userInfo = Capsule::table('tblclients')->where('id', $userId)->first();
            messages_add_to_spam_reports($translations['messages_spam_report_reason'], $userId, $ticketId);
        }
    }
});

add_hook('ClientAreaPageViewTicket', 1, function ($vars) {
    $ticketId = $_GET['tid'] ?? null;
    if ($ticketId) {
        $GLOBALS['spamcheck_ticketid'] = $ticketId;
    }
});

add_hook('ClientAreaHeadOutput', 1, function ($vars) use ($translations) {
    if (!isset($GLOBALS['spamcheck_ticketid'])) {
        return '';
    }

    $ticketId = $GLOBALS['spamcheck_ticketid'];
    $flaggedTitle = addslashes($translations["clients_flagged_title"]);
    $flaggedMessage = addslashes($translations["clients_flagged_message"]);

    return <<<HTML
<script>
(function() {
    fetch('/modules/addons/ticketspamchecker/api/tickets/statuscheck.php?tid={$ticketId}')
        .then(response => response.json())
        .then(data => {
            let shouldHide = false;
            if (data.status === 'Flagged as Spam') shouldHide = true;
            if (data.status === 'Closed' && data.spamReportExists) shouldHide = true;
            if (shouldHide) {
                const style = document.createElement('style');
                style.innerHTML = `
                    #ticketReplyContainer, 
                    #ticketReply, 
                    .card-footer.clearfix, 
                    .alert.alert-warning.text-center {
                        display: none !important;
                    }
                `;
                document.head.appendChild(style);
                const interval = setInterval(() => {
                    const reply = document.getElementById('ticketReplyContainer');
                    if (reply && !document.getElementById('spamWarning')) {
                        const warningMessage = document.createElement('div');
                        warningMessage.id = 'spamWarning';
                        warningMessage.style.backgroundColor = '#f9c74f';
                        warningMessage.style.color = '#000';
                        warningMessage.style.padding = '10px';
                        warningMessage.style.borderRadius = '5px';
                        warningMessage.style.marginTop = '10px';
                        warningMessage.innerHTML = '<strong>{$flaggedTitle}</strong><br>{$flaggedMessage}';
                        reply.parentNode.insertBefore(warningMessage, reply.nextSibling);
                        clearInterval(interval);
                    }
                }, 100);
            }
        });
})();
</script>
HTML;
});