<div class="container">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Resource Usage</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>Disk Space</h4>
                    <div class="progress">
                        {assign var="diskPercent" value=($stats.disk_space.value / $stats.disk_space.max * 100)}
                        {if $stats.disk_space.max == -1}
                            {assign var="diskPercent" value=0}
                        {/if}
                        <div class="progress-bar {if $diskPercent > 80}progress-bar-danger{elseif $diskPercent > 60}progress-bar-warning{else}progress-bar-success{/if}"
                             role="progressbar"
                             style="width: {$diskPercent}%">
                            {$diskPercent|number_format:1}%
                        </div>
                    </div>
                    <p class="text-muted">
                        {($stats.disk_space.value / 1024 / 1024 / 1024)|number_format:2} GB /
                        {if $stats.disk_space.max == -1}
                            Unlimited
                        {else}
                            {($stats.disk_space.max / 1024 / 1024 / 1024)|number_format:2} GB
                        {/if}
                    </p>
                </div>
                <div class="col-md-6">
                    <h4>Traffic</h4>
                    <div class="progress">
                        {assign var="trafficPercent" value=($stats.traffic.value / $stats.traffic.max * 100)}
                        {if $stats.traffic.max == -1}
                            {assign var="trafficPercent" value=0}
                        {/if}
                        <div class="progress-bar {if $trafficPercent > 80}progress-bar-danger{elseif $trafficPercent > 60}progress-bar-warning{else}progress-bar-success{/if}"
                             role="progressbar"
                             style="width: {$trafficPercent}%">
                            {$trafficPercent|number_format:1}%
                        </div>
                    </div>
                    <p class="text-muted">
                        {($stats.traffic.value / 1024 / 1024 / 1024)|number_format:2} GB /
                        {if $stats.traffic.max == -1}
                            Unlimited
                        {else}
                            {($stats.traffic.max / 1024 / 1024 / 1024)|number_format:2} GB
                        {/if}
                    </p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h4>{$stats.domains.value}</h4>
                        <p class="text-muted">
                            Domains
                            {if $stats.domains.max != -1}
                                <br><small>of {$stats.domains.max}</small>
                            {/if}
                        </p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4>{$stats.email_accounts.value}</h4>
                        <p class="text-muted">
                            Email Accounts
                            {if $stats.email_accounts.max != -1}
                                <br><small>of {$stats.email_accounts.max}</small>
                            {/if}
                        </p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4>{$stats.databases.value}</h4>
                        <p class="text-muted">
                            Databases
                            {if $stats.databases.max != -1}
                                <br><small>of {$stats.databases.max}</small>
                            {/if}
                        </p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4>{$stats.ftp_users.value}</h4>
                        <p class="text-muted">
                            FTP Users
                            {if $stats.ftp_users.max != -1}
                                <br><small>of {$stats.ftp_users.max}</small>
                            {/if}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Resources Overview</h3>
        </div>
        <div class="panel-body">
            <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px; border-bottom: none;">
                <li role="presentation" class="active" style="margin-right: 10px;">
                    <a href="#domains" aria-controls="domains" role="tab" data-toggle="tab" style="padding: 12px 20px; font-size: 14px;">
                        <i class="fas fa-globe"></i> Domains ({$stats.domains.value})
                    </a>
                </li>
                <li role="presentation" style="margin-right: 10px;">
                    <a href="#emails" aria-controls="emails" role="tab" data-toggle="tab" style="padding: 12px 20px; font-size: 14px;">
                        <i class="fas fa-envelope"></i> Email Accounts ({$resources.emails|@count})
                    </a>
                </li>
                <li role="presentation" style="margin-right: 10px;">
                    <a href="#databases" aria-controls="databases" role="tab" data-toggle="tab" style="padding: 12px 20px; font-size: 14px;">
                        <i class="fas fa-database"></i> Databases ({$resources.databases|@count})
                    </a>
                </li>
                <li role="presentation">
                    <a href="#ftp" aria-controls="ftp" role="tab" data-toggle="tab" style="padding: 12px 20px; font-size: 14px;">
                        <i class="fas fa-upload"></i> FTP Users ({$resources.ftp_users|@count})
                    </a>
                </li>
            </ul>

            <div class="tab-content">

                <div role="tabpanel" class="tab-pane active" id="domains">
                    {assign var="visibleDomains" value=[]}
                    {foreach $resources.domains as $d}
                        {if !$d.is_subdomain}
                            {assign var="visibleDomains" value=$visibleDomains|@array_merge:[$d]}
                        {/if}
                    {/foreach}

                    {if $visibleDomains|@count > 0}
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Domain</th>
                                        <th>SSL</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $visibleDomains as $domain}
                                        <tr>
                                            <td>{$domain.domain_utf8}</td>
                                            <td>
                                                {if $domain.security.lets_encrypt || $domain.security.id_certificate > 0}
                                                    <span class="label label-success">Yes</span>
                                                {else}
                                                    <span class="label label-default">No</span>
                                                {/if}
                                            </td>
                                            <td>
                                                {if $domain.is_disabled}
                                                    <span class="label label-danger">Disabled</span>
                                                {else}
                                                    <span class="label label-success">Active</span>
                                                {/if}
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {else}
                        <div class="alert alert-info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> No domains found
                        </div>
                    {/if}
                </div>

                <div role="tabpanel" class="tab-pane" id="emails">
                    {if $resources.emails|@count > 0}
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Email Address</th>
                                        <th>Usage</th>
                                        <th>Aliases</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $resources.emails as $email}
                                        <tr>
                                            <td>{$email.email_utf8}</td>
                                            <td>
                                                {($email.size / 1024 / 1024)|number_format:0} MB /
                                                {($email.max_size / 1024 / 1024)|number_format:0} MB
                                            </td>
                                            <td>{$email.aliases|@count}</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {else}
                        <div class="alert alert-info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> No email accounts found
                        </div>
                    {/if}
                </div>

                <div role="tabpanel" class="tab-pane" id="databases">
                    {if $resources.databases|@count > 0}
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Database Name</th>
                                        <th>Username</th>
                                        <th>Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $resources.databases as $db}
                                        <tr>
                                            <td>{$db.database_name}</td>
                                            <td>{$db.database_username}</td>
                                            <td>{($db.size / 1024 / 1024)|number_format:2} MB</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {else}
                        <div class="alert alert-info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> No databases found
                        </div>
                    {/if}
                </div>

                <div role="tabpanel" class="tab-pane" id="ftp">
                    {if $resources.ftp_users|@count > 0}
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Home Directory</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $resources.ftp_users as $ftp}
                                        <tr>
                                            <td>{$ftp.username}</td>
                                            <td>{$ftp.home_directory}</td>
                                            <td>{$ftp.description}</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {else}
                        <div class="alert alert-info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> No FTP users found
                        </div>
                    {/if}
                </div>

            </div>
        </div>
    </div>
</div>