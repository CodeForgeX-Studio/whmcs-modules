<?php
// Preparation
require_once __DIR__ . "/../../../../init.php";

use WHMCS\Database\Capsule;

$languages = [
    'en' => 'English',
    'nl' => 'Dutch',
    'de' => 'German',
];

session_start();

if (!isset($_SESSION['adminid'])) {
    $redirectUrl = htmlspecialchars($adminPath) . '/login.php?redirect=addonmodules.php%3Fmodule%ticketspamchecker';
    header('Location: ' . $redirectUrl);
    exit;
}

function get_option($option) {
    $result = Capsule::table('tbladdonmodules')
        ->where('module', 'ticketspamcheck')
        ->where('setting', $option)
        ->first();
    return $result ? $result->value : '';
}

function load_translation($language) {
    $filePath = __DIR__ . "/../translations/{$language}.php";
    if (file_exists($filePath)) {
        return include $filePath;
    }
    return include __DIR__ . "/../translations/en.php";
}
$adminPath = get_option('ticketspam_admin_path') ?: "admin";

$currentSetting = Capsule::table('tblticketspamcheckdashboardsettings')->first();
$language = $currentSetting->language ?? 'en';
$translations = load_translation($language);
// End Preparation

// Translations
// General
$homeTitle = $translations['home_title'];
// End General

// Navagation
$home = $translations['home'];
$spamReports = $translations['spam_reports'];
$settings = $translations['settings'];
$leaveDashboard = $translations['leave_dashboard'];
// End Navagation

// Home Page
$welcomeMessage = $translations['welcome_message'];
$description = $translations['description'];
$versionInfo = $translations['version_info'];
$creatorInfo = $translations['creator_info'];
$featuresTitle = $translations['features_title'];
$recentUpdatesTitle = $translations['recent_updates_title'];
$features = $translations['features'];
$recentUpdates = $translations['recent_updates'];
$supportTickets = $translations['support_tickets'];
// End Home Page

// Spam Reports Page
$Prev = $translations['prev'];
$Next = $translations['next'];
$Page = $translations['page'];
$Of = $translations['of'];
$spam_reports_description = $translations['spam_reports_description'];
$noReportsMessage = $translations['no_reports'];

$noFilter = $translations['no_filter'];
$mostSpam = $translations['most_spam'];
$searchPlaceholder = $translations['search_placeholder'];
$Search = $translations['search'];
$bulkDelete = $translations['bulk_delete'];
$Reopen = $translations['reopen'];
$setSpam = $translations['set_spam'];
$Close = $translations['close'];
$Remove = $translations['remove'];

$clientId = $translations['client_id'];
$Email = $translations['email'];
$ticketId = $translations['ticket_id'];
$Reason = $translations['reason'];
$Status = $translations['status'];
$createdAt = $translations['created_at'];
$Action = $translations['action'];

$TotalTickets = $translations['total_tickets'];
$legitTickets = $translations['legit_tickets'];
$spamTickets = $translations['spam_tickets'];
// End Spam Reports Page

// Settings Page
$settingsHeader = $translations['settings_header'];
$select_language = $translations['select_language'];
$saveSettings = $translations['save_settings'];
$saveUpdatemessage = $translations['settings_update_message'];
$faviconUploadText = $translations['favicon_upload_text'];
$faviconRemoveText = $translations['favicon_remove_text'];
$faviconPreviewText = $translations['favicon_preview_text'];
$MaxTickets = $translations['max_tickets'];
$TimeLimit = $translations['time_limit'];
// End Settings Page
// End Translations
?>