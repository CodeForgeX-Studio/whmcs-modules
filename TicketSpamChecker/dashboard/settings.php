<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/preparation.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/settings/all.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/favicon.php'; ?>

<!DOCTYPE html>
<html lang="<?php echo $currentLanguage; ?>" x-data="{ open: false }" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($homeTitle); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($finalFavicon); ?>" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="/modules/addons/ticketspamchecker/includes/css/all.css">
</head>
<body class="bg-[#0E1015] text-white min-h-screen flex flex-col">

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/navagtion.php'; ?>

        <div class="content flex-grow p-4 mt-4">
            <h1 class="text-3xl"><?php echo htmlspecialchars($settingsHeader); ?></h1>
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="success-message" id="success-message"><?php echo htmlspecialchars($saveUpdatemessage); ?></div>
            <?php endif; ?>

            <div class="flex gap-6 flex-wrap items-start">
                <div class="form-container" style="flex: 1 1 250px; max-width: 600px;">
                    <form method="post">
                        <label for="language"><?php echo htmlspecialchars($select_language); ?></label>
                        <select name="language" id="language" class="custom-select">
                            <?php foreach ($languages as $langCode => $langName): ?>
                                <option value="<?php echo htmlspecialchars($langCode); ?>" <?php echo $language === $langCode ? 'selected' : ''; ?> >
                                    <?php echo htmlspecialchars($langName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="save-button mt-2"><?php echo htmlspecialchars($saveSettings); ?></button>
                    </form>
                </div>

                <div class="form-container" style="flex: 1 1 250px; max-width: 600px;">
                    <h2 class="text-xl mb-4"><?php echo htmlspecialchars($MaxTickets); ?></h2>
                    <form method="post">
                        <input type="number" name="max_tickets" value="<?php echo htmlspecialchars($maxTickets); ?>" placeholder="1" class="custom-select" />
                        <button type="submit" class="save-button mt-2"><?php echo htmlspecialchars($saveSettings); ?></button>
                    </form>
                </div>

                <div class="w-full"></div>

                <div class="form-container" style="flex: 1 1 250px; max-width: 600px;">
                    <h2 class="text-xl mb-4"><?php echo htmlspecialchars($TimeLimit); ?></h2>
                    <form method="post">
                        <input type="number" name="time_limit" value="<?php echo htmlspecialchars($timeLimit); ?>" placeholder="300" class="custom-select" />
                        <button type="submit" class="save-button mt-2"><?php echo htmlspecialchars($saveSettings); ?></button>
                    </form>
                </div>

                <div class="form-container" style="flex: 1 1 250px; max-width: 600px;">
                    <h2 class="text-xl mb-4 text-center"><?php echo htmlspecialchars($faviconUploadText); ?></h2>
                    <div class="upload-area" id="uploadArea">
                        <?php if ($faviconPath): ?>
                            <div class="favicon-preview" id="faviconPreview">
                                <img src="/modules/addons/ticketspamchecker/uploads/<?php echo htmlspecialchars($faviconPath); ?>" alt="Favicon" />
                                <button onclick="removeFavicon(event)" class="save-button bg-red-600 hover:bg-red-700 mt-2"><?php echo htmlspecialchars($faviconRemoveText); ?></button>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-400" id="uploadText"><?php echo htmlspecialchars($faviconPreviewText); ?></p>
                        <?php endif; ?>
                        <input type="file" id="faviconInput" accept=".png,.ico" class="hidden" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/modules/addons/ticketspamchecker/includes/settings/js/all.js"></script>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/footer.php'; ?>

</body>
</html>