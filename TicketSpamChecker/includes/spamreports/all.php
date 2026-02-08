<?php

use WHMCS\Database\Capsule;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_report'])) {
        $reportId = (int) $_POST['report_id'];
        $ticketId = (int) $_POST['ticket_id'];
        Capsule::table('tblticketspamcheckspamreports')->where('id', $reportId)->delete();
        $ticket = Capsule::table('tbltickets')->where('id', $ticketId)->first();
        if ($ticket && $ticket->status === 'Flagged as Spam') {
            Capsule::table('tbltickets')->where('id', $ticketId)->update(['status' => 'Open']);
        }
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        header('Location: spamreports.php?page=' . $page);
        exit;
    }

    if (isset($_POST['reopen_ticket'])) {
        $reportId = (int) $_POST['report_id'];
        $ticketId = (int) $_POST['ticket_id'];
        Capsule::table('tbltickets')->where('id', $ticketId)->update(['status' => 'Open']);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        header('Location: spamreports.php?page=' . $page);
        exit;
    }

    if (isset($_POST['flag_ticket'])) {
        $reportId = (int) $_POST['report_id'];
        $ticketId = (int) $_POST['ticket_id'];
        Capsule::table('tbltickets')->where('id', $ticketId)->update(['status' => 'Flagged as Spam']);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        header('Location: spamreports.php?page=' . $page);
        exit;
    }

    if (isset($_POST['close_ticket'])) {
        $reportId = (int) $_POST['report_id'];
        $ticketId = (int) $_POST['ticket_id'];
        Capsule::table('tbltickets')->where('id', $ticketId)->update(['status' => 'Closed']);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        header('Location: spamreports.php?page=' . $page);
        exit;
    }

    if (isset($_POST['bulk_delete']) && isset($_POST['selected_reports'])) {
        foreach ($_POST['selected_reports'] as $entry) {
            [$reportId, $ticketId] = explode(':', $entry);
            Capsule::table('tblticketspamcheckspamreports')->where('id', (int)$reportId)->delete();
            Capsule::table('tbltickets')->where('id', (int)$ticketId)->update(['status' => 'Open']);
        }
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        header('Location: spamreports.php?page=' . $page);
        exit;
    }
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$totalReports = Capsule::table('tblticketspamcheckspamreports')->count();
$totalPages = ceil($totalReports / $limit);
$rawReports = Capsule::table('tblticketspamcheckspamreports')
    ->orderBy('created_at', 'desc')
    ->offset($offset)
    ->limit($limit)
    ->get();

$spamReportsData = collect($rawReports)->map(function ($report) {
    $ticket = Capsule::table('tbltickets')->where('id', $report->ticket_id)->first();
    $client = Capsule::table('tblclients')->where('id', $report->client_id)->first();
    $report->status = $ticket ? $ticket->status : 'Deleted/Unknown';
    $report->client_email = $client ? $client->email : 'Deleted/Unknown';
    return $report;
});

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($search) {
    $searchLower = strtolower($search);
    $spamReportsData = $spamReportsData->filter(function ($item) use ($searchLower) {
        return strpos((string)$item->client_id, $searchLower) !== false ||
               strpos(strtolower($item->client_email), $searchLower) !== false ||
               strpos((string)$item->ticket_id, $searchLower) !== false;
    });
}

if ($filter === 'most_spam') {
    $mostSpamClient = Capsule::table('tblticketspamcheckspamreports')
        ->select('client_id', Capsule::raw('COUNT(*) as total'))
        ->groupBy('client_id')
        ->orderByDesc('total')
        ->first();

    if ($mostSpamClient) {
        $spamReportsData = $spamReportsData->filter(function ($item) use ($mostSpamClient) {
            return $item->client_id === $mostSpamClient->client_id;
        });
    }
}

$allSpamTicketIDs = Capsule::table('tblticketspamcheckspamreports')->pluck('ticket_id')->toArray();
$totalTickets = Capsule::table('tbltickets')->count();
$spamCount = Capsule::table('tbltickets')->whereIn('id', $allSpamTicketIDs)->where('status', '!=', 'Open')->count();
$legitCount = $totalTickets - $spamCount;
$spamPercentage = $totalTickets > 0 ? round(($spamCount / $totalTickets) * 100) : 0;
$legitPercentage = $totalTickets > 0 ? round(($legitCount / $totalTickets) * 100) : 0;

?>