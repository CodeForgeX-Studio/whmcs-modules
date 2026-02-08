<?php

use WHMCS\Database\Capsule;

$moduleVersionRow = Capsule::table('tbladdonmodules')
    ->where('module', 'ticketspamchecker')
    ->where('setting', 'version')
    ->first();
$moduleVersion = $moduleVersionRow->value ?? 'Underfind';

$reportData = Capsule::table('tbltickets')
    ->selectRaw("DATE(updated_at) as report_date, 
        SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count, 
        SUM(CASE WHEN status = 'Flagged as Spam' THEN 1 ELSE 0 END) as spam_count, 
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
    ->groupBy('report_date')
    ->orderBy('report_date', 'asc')
    ->get();

$chartLabels = [];
$openData = [];
$spamData = [];
$closedData = [];

foreach ($reportData as $row) {
    $chartLabels[] = $row->report_date;
    $openData[] = $row->open_count;
    $spamData[] = $row->spam_count;
    $closedData[] = $row->closed_count;
}

$selectedDate = end($chartLabels);
if (isset($_GET['date']) && in_array($_GET['date'], $chartLabels)) {
    $selectedDate = $_GET['date'];
}

?>