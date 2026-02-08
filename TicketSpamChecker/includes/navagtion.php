<div class="bg-[#0C0E13] p-4 flex items-center justify-between shadow-md">
        <button @click="open = !open" class="md:hidden focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                <path x-show="open" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h1 class="text-2xl"><?php echo htmlspecialchars($homeTitle); ?></h1>
    </div>

<div class="flex flex-grow">
    <div :class="open ? 'block' : 'hidden'" class="md:block bg-[#0C0E13] w-full md:w-64 p-4 border-r border-[#0C0E13] md:relative absolute z-50 md:z-auto">
        <a href="/modules/addons/ticketspamchecker/dashboard/home.php" class="block p-2 rounded hover:bg-gray-700"><?php echo htmlspecialchars($home); ?></a>
        <a href="/modules/addons/ticketspamchecker/dashboard/spamreports.php" class="block p-2 rounded hover:bg-gray-700"><?php echo htmlspecialchars($spamReports); ?></a>
        <a href="/modules/addons/ticketspamchecker/dashboard/settings.php" class="block p-2 rounded hover:bg-gray-700"><?php echo htmlspecialchars($settings); ?></a>
        <a href="/<?php echo htmlspecialchars($adminPath); ?>/addonmodules.php?module=ticketspamchecker" class="block p-2 rounded hover:bg-gray-700"><?php echo htmlspecialchars($leaveDashboard); ?></a>
    </div>