<?php

use WHMCS\Database\Capsule;

$faviconPath = $currentSetting->favicon ?? '';
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/uploads/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['favicon'])) {
        header('Content-Type: application/json');
        $file = $_FILES['favicon'];
        if ($file['error'] === 0) {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filePath = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                Capsule::table('tblticketspamcheckdashboardsettings')->updateOrInsert(
                    ['id' => $currentSetting->id ?? 1],
                    ['favicon' => $fileName]
                );
                echo json_encode(['success' => true]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Upload failed.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error with file upload.']);
            exit;
        }
    }

    if (isset($_POST['language'])) {
        $language = $_POST['language'];
        Capsule::table('tblticketspamcheckdashboardsettings')->updateOrInsert(
            ['id' => $currentSetting->id ?? 1],
            ['language' => $language]
        );
        header('Location: settings.php?success=1');
        exit;
    }

    if (isset($_POST['max_tickets'])) {
        Capsule::table('tblticketspamcheckdashboardsettings')->updateOrInsert(
            ['id' => $currentSetting->id ?? 1],
            ['max_tickets' => intval($_POST['max_tickets'])]
        );
        header('Location: settings.php?success=1');
        exit;
    }

    if (isset($_POST['time_limit'])) {
        Capsule::table('tblticketspamcheckdashboardsettings')->updateOrInsert(
            ['id' => $currentSetting->id ?? 1],
            ['time_limit' => intval($_POST['time_limit'])]
        );
        header('Location: settings.php?success=1');
        exit;
    }
}

if (isset($_GET['remove_favicon']) && $_GET['remove_favicon'] == '1') {
    header('Content-Type: application/json');
    if ($faviconPath && file_exists($uploadDir . $faviconPath)) {
        unlink($uploadDir . $faviconPath);
        Capsule::table('tblticketspamcheckdashboardsettings')->updateOrInsert(
            ['id' => $currentSetting->id ?? 1],
            ['favicon' => null]
        );
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'File not found.']);
    exit;
}

$maxTickets = $currentSetting->max_tickets ?? '5';
$timeLimit = $currentSetting->time_limit ?? '300';

?>