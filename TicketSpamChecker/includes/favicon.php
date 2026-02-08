<?php

use WHMCS\Database\Capsule;

$faviconPath = $currentSetting->favicon ?? '';
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/uploads/';

$faviconUrl = '/modules/addons/ticketspamchecker/uploads/' . $faviconPath;
$faviconFullPath = $uploadDir . $faviconPath;
$finalFavicon = (file_exists($faviconFullPath) && !empty($faviconPath)) ? $faviconUrl : 'https://i.imgur.com/9ssrqfO.png';

?>