<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/preparation.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/spamreports/all.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/favicon.php'; ?>

<!DOCTYPE html>
<html lang="<?php echo $currentLanguage; ?>" x-data="{ open: false }" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
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
            <h1 class="text-3xl mb-2"><?php echo htmlspecialchars($spamReports); ?></h1>
            <p class="mb-4"><?php echo htmlspecialchars($spam_reports_description); ?></p>

            <form method="GET" class="mb-4 flex flex-wrap gap-4 items-center">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo htmlspecialchars($searchPlaceholder); ?>" class="p-2 rounded bg-gray-800 text-white">
                <select name="filter" class="p-2 rounded bg-gray-800 text-white">
                    <option value=""><?php echo htmlspecialchars($noFilter); ?></option>
                    <option value="most_spam" <?php echo $filter === 'most_spam' ? 'selected' : ''; ?>><?php echo htmlspecialchars($mostSpam); ?></option>
                </select>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded"><?php echo htmlspecialchars($Search); ?></button>
            </form>

            <?php if ($spamReportsData->isEmpty()): ?>
                <div class="bg-[#1E212D] p-4 rounded-lg shadow mb-4">
                    <p class="text-left text-gray-300 text-base"><?php echo htmlspecialchars($noReportsMessage); ?></p>       
                </div>
            <?php else: ?>
            <form method="POST">
                <div class="bg-[#1E212D] p-4 rounded-lg shadow mb-4">
                    <div class="mt-4 flex justify-end">
                      <button type="submit" name="bulk_delete" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded" id="toggleBulkDeleteButton"><?php echo htmlspecialchars($bulkDelete); ?></button>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                                <th><?php echo htmlspecialchars($clientId); ?></th>
                                <th><?php echo htmlspecialchars($Email); ?></th>
                                <th><?php echo htmlspecialchars($ticketId); ?></th>
                                <th><?php echo htmlspecialchars($Reason); ?></th>
                                <th><?php echo htmlspecialchars($Status); ?></th>
                                <th><?php echo htmlspecialchars($createdAt); ?></th>
                                <th><?php echo htmlspecialchars($Action); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($spamReportsData as $report): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_reports[]" value="<?php echo $report->id . ':' . $report->ticket_id; ?>"></td>
                                    <td><?php echo htmlspecialchars($report->client_id); ?></td>
                                    <td><?php echo htmlspecialchars($report->client_email); ?></td>
                                    <td><?php echo htmlspecialchars($report->ticket_id); ?></td>
                                    <td><?php echo htmlspecialchars($report->reason); ?></td>
                                    <td><?php echo htmlspecialchars($report->status); ?></td>
                                    <td><?php echo htmlspecialchars($report->created_at); ?></td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="report_id" value="<?php echo $report->id; ?>">
                                            <input type="hidden" name="ticket_id" value="<?php echo $report->ticket_id; ?>">
                                            <button type="submit" name="reopen_ticket" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded"><?php echo htmlspecialchars($Reopen); ?></button>
                                            <button type="submit" name="flag_ticket" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded"><?php echo htmlspecialchars($setSpam); ?></button>
                                            <button type="submit" name="close_ticket" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded"><?php echo htmlspecialchars($Close); ?></button>
                                            <button type="submit" name="remove_report" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded"><?php echo htmlspecialchars($Remove); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination mt-4">
                        <span><?php echo htmlspecialchars($Page . ' ' . $page . ' ' . $Of . ' ' . $totalPages); ?></span>
                    </div>
                    <div class="pagination mt-4">
                        <a class="prev" href="?page=<?php echo max(1, $page - 1); ?>"><?php echo htmlspecialchars($Prev); ?></a>
                        <a class="next" href="?page=<?php echo min($totalPages, $page + 1); ?>"><?php echo htmlspecialchars($Next); ?></a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex gap-4 flex-wrap">
                <div class="bg-[#1E212D] p-4 rounded-lg shadow w-72">
                    <p class="text-lg font-semibold"><?php echo htmlspecialchars($TotalTickets); ?>: <?php echo $totalTickets; ?></p>
                    <p><?php echo htmlspecialchars($spamTickets); ?>: <?php echo $spamCount; ?> (<?php echo $spamPercentage; ?>%)</p>
                    <p><?php echo htmlspecialchars($legitTickets); ?>: <?php echo $legitCount; ?> (<?php echo $legitPercentage; ?>%)</p>
                </div>
                <div class="bg-[#1E212D] p-4 rounded-lg shadow w-72">
                    <canvas id="spamChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>window.spamCount = <?php echo $spamCount; ?>; window.legitCount = <?php echo $legitCount; ?>; </script>
    <script src="/modules/addons/ticketspamchecker/includes/spamreports/js/all.js"></script>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/addons/ticketspamchecker/includes/footer.php'; ?>
    
</body>
</html>