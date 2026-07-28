<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
    // Active-tab detection reads the current method directly rather than
    // requiring every reports_* action to pass an extra $active_tab
    // variable - one less thing for each of the 7 actions to remember.
    $current_method = strtolower($this->router->fetch_method());
    $tabs = [
        'reports'                      => ['label' => _l('manager_portal_reports_productivity'), 'href' => admin_url('manager_portal/reports')],
        'reports_departments'          => ['label' => _l('manager_portal_reports_departments'), 'href' => admin_url('manager_portal/reports_departments')],
        'reports_projects'             => ['label' => _l('manager_portal_reports_projects'), 'href' => admin_url('manager_portal/reports_projects')],
        'reports_attendance_analytics' => ['label' => _l('manager_portal_reports_attendance_analytics'), 'href' => admin_url('manager_portal/reports_attendance_analytics')],
        'reports_dwu_analytics'        => ['label' => _l('manager_portal_reports_dwu_analytics'), 'href' => admin_url('manager_portal/reports_dwu_analytics')],
        'reports_task_analytics'       => ['label' => _l('manager_portal_reports_task_analytics'), 'href' => admin_url('manager_portal/reports_task_analytics')],
        'reports_project_analytics'    => ['label' => _l('manager_portal_reports_project_analytics'), 'href' => admin_url('manager_portal/reports_project_analytics')],
    ];
?>
<ul class="nav nav-tabs nav-tabs-horizontal tw-mb-4">
    <?php foreach ($tabs as $method => $tab) { ?>
    <li class="<?= $current_method === $method ? 'active' : ''; ?>"><a href="<?= $tab['href']; ?>"><?= $tab['label']; ?></a></li>
    <?php } ?>
</ul>
