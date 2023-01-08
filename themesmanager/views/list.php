<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
    <?php if($alert_autoload){ ?>
                <div class="alert alert-danger">
                    <p><strong>Module Themes manager need:</strong></br>
                    Please add the line below at the end of the file <strong><?php echo APPPATH.'config/my_autoload.php'; ?></strong><br/></p>
                    <p>
                    <pre>if (file_exists(FCPATH.'/modules/themesmanager/my_autoload.php')) {
    include_once(FCPATH.'/modules/themesmanager/my_autoload.php');
}
                    </pre>
                    </p>
                </div>
                <?php } ?>
        <div class="tw-mb-8">
            <?php echo form_open_multipart(admin_url('themesmanager/upload'), ['id' => 'theme_install_form', 'class' => 'sm:flex sm:items-center']); ?>
            <h3 class="tw-mb-2 tw-text-lg tw-font-medium tw-leading-6 tw-text-neutral-900">Upload Theme</h3>
            <div class="tw-mt-2 tw-max-w-xl tw-text-sm tw-text-neutral-600">
                <p>If you have a theme in a .zip format, you may install it by uploading it here.</p>
            </div>
            <form class="">
                <div class="w-full tw-inline-flex sm:max-w-xs">
                    <input type="file" class="form-control" name="theme">

                    <button type="submit" class="btn btn-primary tw-ml-2">Install</button>
                </div>
                <?php echo form_close(); ?>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table dt-table" data-order-type="asc" data-order-col="0">
                                <thead>
                                    <tr>
                                        <th>
                                            <?php echo _l('theme'); ?>
                                        </th>
                                        <th>
                                            <?php echo _l('theme_description'); ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($themes as $theme) {
    $system_name                  = $theme['system_name'];
    $database_upgrade_is_required = $this->app_themesmanager->is_database_upgrade_required($system_name); ?>
                                    <tr class="<?php if ($theme['activated'] === 1 && !$database_upgrade_is_required) {
        echo 'info';
    } ?><?php if ($database_upgrade_is_required) {
        echo ' warning';
    } ?>">
                                        <td data-order="<?php echo $system_name; ?>">
                                            <p>
                                                <b>
                                                    <?php echo $theme['headers']['theme_name']; ?>
                                                </b>
                                            </p>
                                            <?php
                                            $action_links = [];
    $versionRequirementMet                                = $this->app_themesmanager->is_minimum_version_requirement_met($system_name);
    $action_links                                         = hooks()->apply_filters("theme_{$system_name}_action_links", $action_links);

    if ($theme['activated'] === 0 && $versionRequirementMet) {
        array_unshift($action_links, '<a href="' . admin_url('themesmanager/activate/' . $system_name) . '">' . _l('theme_activate') . '</a>');
    }

    if ($theme['activated'] === 1) {
        array_unshift($action_links, '<a href="' . admin_url('themesmanager/deactivate/' . $system_name) . '">' . _l('theme_deactivate') . '</a>');
    }

    if ($database_upgrade_is_required) {
        $action_links[] = '<a href="' . admin_url('themesmanager/upgrade_database/' . $system_name) . '" class="text-success bol">' . _l('theme_upgrade_database') . '</a>';
    }

    if ($theme['activated'] === 0 && !in_array($system_name, uninstallable_themes())) {
        $action_links[] = '<a href="' . admin_url('themesmanager/uninstall/' . $system_name) . '" class="_delete text-danger">' . _l('theme_uninstall') . '</a>';
    }

    echo implode('&nbsp;|&nbsp;', $action_links);

    if (!$versionRequirementMet) {
        echo '<div class="alert alert-warning mtop5">';
        echo 'This theme requires at least v' . $theme['headers']['requires_at_least'] . ' of the CRM.';
        if ($theme['activated'] === 0) {
            echo ' Hence, cannot be activated';
        }
        echo '</div>';
    }

    if ($newVersionData = $this->app_themesmanager->new_version_available($system_name)) {
        echo '<div class="alert alert-success mtop5">';

        echo 'There is a new version of ' . $theme['headers']['theme_name'] . ' available. ';
        $version_actions = [];

        if (isset($newVersionData['changelog']) && !empty($newVersionData['changelog'])) {
            $version_actions[] = '<a href="' . $newVersionData['changelog'] . '" target="_blank">Release Notes (' . $newVersionData['version'] . ')</a>';
        }

        if ($this->app_themesmanager->is_update_handler_available($system_name)) {
            $version_actions[] = '<a href="' . admin_url('themesmanager/update_version/' . $system_name) . '" id="update-theme-' . $system_name . '">Update</a>';
        }

        echo implode('&nbsp;|&nbsp;', $version_actions);
        echo '</div>';
    } ?>
                                        </td>
                                        <td>
                                            <p>
                                                <?php echo isset($theme['headers']['description']) ? $theme['headers']['description'] : ''; ?>
                                            </p>
                                            <?php

                                            $theme_description_info = [];
    hooks()->apply_filters("theme_{$system_name}_description_info", $theme_description_info);

    if (isset($theme['headers']['author'])) {
        $author = $theme['headers']['author'];
        if (isset($theme['headers']['author_uri'])) {
            $author = '<a href="' . $theme['headers']['author_uri'] . '">' . $author . '</a>';
        }
        array_unshift($theme_description_info, _l('theme_by').' '.$author);
    }
    array_unshift($theme_description_info, _l('theme_version').' '.$theme['headers']['version']);
    echo implode('&nbsp;|&nbsp;', $theme_description_info); 
     ?>
                                        </td>
                                    </tr>
                                    <?php
} ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    appValidateForm($('#theme_install_form'), {
        theme: {
            required: true,
            extension: "zip"
        }
    });
});
</script>
</body>

</html>