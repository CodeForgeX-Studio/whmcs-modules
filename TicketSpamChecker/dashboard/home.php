<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/preparation.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/home/all.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/favicon.php'; ?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>" x-data="{ open: false }" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($homeTitle); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($finalFavicon); ?>" type="image/png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="/modules/addons/ticketspamchecker/includes/css/all.css">
</head>
<body class="bg-[#0E1015] text-white min-h-screen flex flex-col">

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/navagtion.php'; ?>

        <div class="flex-grow p-4">
            <h1 class="text-2xl md:text-3xl"><?php echo htmlspecialchars($welcomeMessage); ?> <span id="username"><?php echo htmlspecialchars($username ?? ''); ?></span></h1>
            <p class="mt-2 text-sm md:text-base"><?php echo htmlspecialchars($description); ?></p>

            <div class="flex flex-col md:flex-row justify-between gap-4 mt-6">
                <div class="bg-[#1E212D] p-4 rounded flex justify-between items-center flex-1">
                    <span class="font-semibold"><?php echo htmlspecialchars($versionInfo); ?>:</span>
                    <span><?php echo htmlspecialchars($moduleVersion); ?></span>
                </div>
                <div class="bg-[#1E212D] p-4 rounded flex justify-between items-center flex-1">
                    <span class="font-semibold"><?php echo htmlspecialchars($creatorInfo); ?>:</span>
                    <span>CodeForgeX Studio</span>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4 mt-6">
                <div class="bg-[#1E212D] p-4 rounded flex-1">
                    <h2 class="text-lg font-semibold border-l-4 border-white pl-2 mb-2"><?php echo htmlspecialchars($featuresTitle); ?></h2>
                    <ul class="list-disc list-inside text-sm">
                        <?php foreach ($features as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="bg-[#1E212D] p-4 rounded flex-1">
                    <h2 class="text-lg font-semibold border-l-4 border-white pl-2 mb-2"><?php echo htmlspecialchars($recentUpdatesTitle); ?></h2>
                    <ul class="list-disc list-inside text-sm">
                        <?php foreach ($recentUpdates as $update): ?>
                            <li><?php echo htmlspecialchars($update); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="bg-[#1E212D] p-4 rounded mt-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold"><?php echo htmlspecialchars($supportTickets); ?></h2>
                    <form method="GET">
                        <select name="date" onchange="this.form.submit()" class="bg-[#0E1015] text-white border border-gray-700 rounded px-2 py-1">
                            <?php foreach ($chartLabels as $date): ?>
                                <option value="<?php echo $date; ?>" <?php echo $date === $selectedDate ? 'selected' : ''; ?>><?php echo $date; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <canvas id="spamChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <script>window.chartLabels = <?php echo json_encode($chartLabels); ?>; window.openData = <?php echo json_encode($openData); ?>; window.spamData = <?php echo json_encode($spamData); ?>; window.closedData = <?php echo json_encode($closedData); ?></script>
    <script src="/modules/addons/ticketspamchecker/includes/home/js/all.js"></script>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/footer.php'; ?>

</body>
</html>