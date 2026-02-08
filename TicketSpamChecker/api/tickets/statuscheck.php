<?php

require_once '../../../../../init.php';

use WHMCS\Database\Capsule;

header('Content-Type: application/json');

$ticketTid = $_GET['tid'] ?? null;
$response = [];

if ($ticketTid) {
    $ticket = Capsule::table('tbltickets')->where('tid', $ticketTid)->first();

    if ($ticket) {
        $ticketId = $ticket->id;
        $hasSpamReport = Capsule::table('tblticketspamcheckspamreports')->where('ticket_id', $ticketId)->exists();

        $response = [
            'status' => $ticket->status,
            'spamReportExists' => $hasSpamReport,
        ];
    } else {
        $response = ['status' => 'Not Found'];
    }
} else {
    $response = ['status' => 'No Ticket'];
}

echo json_encode($response);