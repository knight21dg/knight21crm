<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    /* Scoped to this page only - sizes the reused company logo for a
       prominent, centered placement above the login form. The shared
       .navbar-brand rule this same logo markup uses elsewhere caps it at
       34px for the navbar's own layout, which doesn't apply here since
       this page renders with no navbar. */
    .customer-login-logo {
        display: flex;
        justify-content: center;
    }

    .customer-login-logo img {
        max-height: 72px;
        width: auto;
    }

    @media (max-width: 480px) {
        .customer-login-logo img {
            max-height: 56px;
        }
    }
</style>
<div class="mtop40">
    <div class="col-md-4 col-md-offset-4 text-center">
        <div class="customer-login-logo mbot20">
            <?php
            // Reuses the same company logo already shown in the portal's own
            // navbar (template_parts/navigation.php) and on every customer-
            // facing PDF (invoice/estimate/proposal/etc.) - no new asset, no
            // hardcoded path. That navbar output relies on a navbar-specific
            // 34px height rule (assets/themes/perfex/css/style.css), which
            // doesn't apply here since this page has no navbar, so it's sized
            // via a small inline cap instead of a new stylesheet rule.
            get_dark_company_logo('', '');
            ?>
        </div>
        <h1 class="tw-font-bold mbot20 login-heading">
            <?= _l(get_option('allow_registration') == 1 ? 'clients_login_heading_register' : 'clients_login_heading_no_register');
?>
        </h1>
    </div>
    <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2">
        <?= form_open($this->uri->uri_string(), ['class' => 'login-form']); ?>
        <?php hooks()->do_action('clients_login_form_start'); ?>
        <div class="panel_s">
            <div class="panel-body">

                <?php if (! is_language_disabled()) { ?>
                <div class="form-group">
                    <label for="language" class="control-label">
                        <?= _l('language'); ?>
                    </label>
                    <select name="language" id="language" class="form-control selectpicker"
                        onchange="change_contact_language(this)"
                        data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                        data-live-search="true">
                        <?php $selected = (get_contact_language() != '') ? get_contact_language() : get_option('active_language'); ?>
                        <?php foreach ($this->app->get_available_languages() as $availableLanguage) { ?>
                        <option value="<?= e($availableLanguage); ?>"
                            <?= ($availableLanguage == $selected) ? 'selected' : '' ?>>
                            <?= e(ucfirst($availableLanguage)); ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>

                <div class="form-group">
                    <label
                        for="email"><?= _l('clients_login_email'); ?></label>
                    <input type="text" autofocus="true" class="form-control" name="email" id="email">
                    <?= form_error('email'); ?>
                </div>

                <div class="form-group">
                    <label
                        for="password"><?= _l('clients_login_password'); ?></label>
                    <input type="password" class="form-control" name="password" id="password">
                    <?= form_error('password'); ?>
                </div>

                <?php if (show_recaptcha_in_customers_area()) { ?>
                <div class="g-recaptcha tw-mb-4"
                    data-sitekey="<?= get_option('recaptcha_site_key'); ?>">
                </div>
                <?= form_error('g-recaptcha-response'); ?>
                <?php } ?>

                <div class="checkbox">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">
                        <?= _l('clients_login_remember'); ?>
                    </label>
                </div>

                <div class="form-group tw-mt-6">
                    <button type="submit" class="btn btn-primary btn-block">
                        <?= _l('clients_login_login_string'); ?>
                    </button>
                    <?php if (get_option('allow_registration') == 1) { ?>
                    <a href="<?= site_url('authentication/register'); ?>"
                        class="btn btn-default btn-block">
                        <?= _l('clients_register_string'); ?>
                    </a>
                    <?php } ?>
                </div>
                <div class="tw-text-center">
                    <a href="<?= site_url('authentication/forgot_password'); ?>"
                        class="text-muted">
                        <?= _l('customer_forgot_password'); ?>
                    </a>
                </div>
                <?php hooks()->do_action('clients_login_form_end'); ?>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>