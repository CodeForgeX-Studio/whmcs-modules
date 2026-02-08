<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function ticketspamchecker_config() {
    $authorLink = "https://codeforgex.studio";
    return [
        'name' => 'Ticket Spam Checker',
        'description' => 'Check for spam in ticket submissions based on frequency.',
        'version' => '1.0',
        'author' => '<a href="' . $authorLink . '" style="text-decoration: none; display: inline-flex; align-items: center;">CodeForgeX Studio</a>',
        'fields' => [
            'ticketspam_admin_path' => [
                'FriendlyName' => 'Admin path',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Your admin path (e.g. admin)',
                'Placeholder' => 'e.g. admin',
            ],
        ],
    ];
}

function ticketspamchecker_activate() {
    try {
        $dashboardSettingsColumns = [
            'id', 'language', 'favicon', 'max_tickets', 'time_limit'
        ];

        if (!Capsule::schema()->hasTable('tblticketspamcheckdashboardsettings')) {
            Capsule::schema()->create('tblticketspamcheckdashboardsettings', function ($table) {
                $table->increments('id');
                $table->string('language', 10)->default('en');
                $table->string('favicon');
                $table->string('max_tickets')->default('5');
                $table->string('time_limit')->default('300');
            });
        } else {
            $existingColumns = Capsule::schema()->getColumnListing('tblticketspamcheckdashboardsettings');
            $columnsToAdd = array_diff($dashboardSettingsColumns, $existingColumns);
            $columnsToRemove = array_diff($existingColumns, $dashboardSettingsColumns);

            foreach ($columnsToAdd as $column) {
                Capsule::schema()->table('tblticketspamcheckdashboardsettings', function ($table) use ($column) {
                    if ($column === 'language') {
                        $table->string('language', 10)->default('en');
                    } elseif ($column === 'favicon') {
                        $table->string('favicon');
                    } elseif ($column === 'max_tickets') {
                        $table->string('max_tickets')->default('5');
                    } elseif ($column === 'time_limit') {
                        $table->string('time_limit')->default('300');
                    }
                });
            }

            foreach ($columnsToRemove as $column) {
                Capsule::schema()->table('tblticketspamcheckdashboardsettings', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        $spamReportsColumns = [
            'id', 'client_id', 'ticket_id', 'reason', 'created_at', 'updated_at'
        ];

        if (!Capsule::schema()->hasTable('tblticketspamcheckspamreports')) {
            Capsule::schema()->create('tblticketspamcheckspamreports', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->integer('ticket_id')->unsigned();
                $table->string('reason');
                $table->timestamps();
            });
        } else {
            $existingColumns = Capsule::schema()->getColumnListing('tblticketspamcheckspamreports');
            $columnsToAdd = array_diff($spamReportsColumns, $existingColumns);
            $columnsToRemove = array_diff($existingColumns, $spamReportsColumns);

            foreach ($columnsToAdd as $column) {
                Capsule::schema()->table('tblticketspamcheckspamreports', function ($table) use ($column) {
                    if ($column === 'client_id') {
                        $table->integer('client_id')->unsigned();
                    } elseif ($column === 'ticket_id') {
                        $table->integer('ticket_id')->unsigned();
                    } elseif ($column === 'reason') {
                        $table->string('reason');
                    }
                });
            }

            foreach ($columnsToRemove as $column) {
                Capsule::schema()->table('tblticketspamcheckspamreports', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
            }

            if (!in_array('created_at', $existingColumns)) {
                Capsule::schema()->table('tblticketspamcheckspamreports', function ($table) {
                    $table->timestamp('created_at')->nullable()->defaultCurrent();
                });
            }

            if (!in_array('updated_at', $existingColumns)) {
                Capsule::schema()->table('tblticketspamcheckspamreports', function ($table) {
                    $table->timestamp('updated_at')->nullable()->defaultCurrent()->onUpdate(Capsule::raw('CURRENT_TIMESTAMP'));
                });
            }
        }

        $statusExists = Capsule::table('tblticketstatuses')->where('title', 'Flagged as Spam')->exists();
        if (!$statusExists) {
            Capsule::table('tblticketstatuses')->insert(['title' => 'Flagged as Spam', 'color' => '#FF0000', 'showactive' => 1, 'showawaiting' => 0, 'sortorder' => 99]);
        }

        return ['status' => 'success', 'description' => 'Ticket Spam Check add-on has been activated.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => 'Error creating tables: ' . $e->getMessage()];
    }
}

function ticketspamchecker_deactivate() {
    try {
        return ['status' => 'success', 'description' => 'Ticket Spam Check add-on has been deactivated.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => 'Error dropping tables: ' . $e->getMessage()];
    }
}

function ticketspamchecker_output($vars) {
    echo '<div style="font-family: \'Arial\', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0;">';
    echo '<div style="background-color: #0C0E13; padding: 40px 60px; border-radius: 10px; box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); text-align: center; width: 100%; max-width: 800px;">';
    
    echo '<h2 style="font-size: 32px; margin-bottom: 20px; color: #ffffff; font-weight: 600;">Welcome to the Ticket Spam Checker</h2>';
    
    echo '<p style="font-size: 18px; margin-bottom: 30px; color: #c1c7d0;">Ensure your support tickets are clean and free from spam by accessing the dashboard below.</p>';
    
    echo '<form method="post" action="/modules/addons/ticketspamchecker/dashboard/home.php" style="display: flex; flex-direction: column; align-items: center; gap: 20px;">';
    echo '<button type="submit" style="padding: 16px 32px; font-size: 18px; cursor: pointer; background-color: #2E323D; color: white; border: none; border-radius: 8px; transition: background-color 0.3s ease-in-out; width: 250px;">';
    echo 'Open Dashboard';
    echo '</button>';
    
    echo '<div>';
    echo '<p style="font-size: 14px; color: #c1c7d0;">&copy; ' . date('Y') . ' CodeForgeX Studio. All rights reserved.</p>';
    echo '</div>';
    echo '</form>';
    
    echo '</div>';
    echo '</div>';
}

?>