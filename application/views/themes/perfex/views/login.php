<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    /* ---- Login page layout: fill the space between the theme's nav/footer, center the card, no page scroll ---- */
    body.customers_login {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    body.customers_login .navbar.header,
    body.customers_login footer.footer {
        flex-shrink: 0;
    }

    body.customers_login #wrapper,
    body.customers_login #content {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }

    body.customers_login #content {
        position: relative;
        background: linear-gradient(160deg, #fbfbfc 0%, #f4f6f9 60%, #eef1f6 100%);
    }

    body.customers_login #content > .container:last-of-type {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        position: relative;
        z-index: 1;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    body.customers_login #content > .container:last-of-type > .row {
        width: 100%;
    }

    .login-page-bg {
        position: fixed;
        inset: 0;
        overflow: hidden;
        z-index: 0;
        pointer-events: none;
    }

    .login-page-bg-shape {
        position: absolute;
        display: block;
        border-radius: 999px;
        filter: blur(70px);
        opacity: .5;
    }

    .login-page-bg-shape--one {
        width: 380px;
        height: 380px;
        top: -130px;
        left: -110px;
        background: #ffedd5;
    }

    .login-page-bg-shape--two {
        width: 340px;
        height: 340px;
        bottom: -150px;
        right: -100px;
        background: #e9edf3;
    }

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
        max-height: 56px;
        width: auto;
        filter: drop-shadow(0 6px 14px rgb(37 99 235 / 0.16));
        transition: filter .2s ease;
    }

    /* Logo doubles as the portal-switch control (see the logo_href filter
       just before the get_dark_company_logo() call below) - a subtle
       pointer + scale lift indicates it's clickable, mirroring the same
       hover treatment on the Admin Login logo. The idle drop-shadow above
       is the same soft brand glow Admin Login uses in place of a white
       badge container; it just deepens slightly on hover here. */
    .customer-login-logo a {
        display: inline-flex;
        cursor: pointer;
        transition: transform .2s ease;
    }

    .customer-login-logo a:hover {
        transform: scale(1.03);
    }

    .customer-login-logo a:hover img {
        filter: drop-shadow(0 8px 18px rgb(37 99 235 / 0.26));
    }

    @media (max-width: 480px) {
        .customer-login-logo img {
            max-height: 42px;
        }
    }

    .login-heading {
        font-size: 21px;
    }

    .login-card {
        border-radius: 16px !important;
        box-shadow: 0 10px 25px -5px rgb(15 23 42 / 0.08), 0 4px 8px -4px rgb(15 23 42 / 0.06);
        border: 1px solid rgb(15 23 42 / 0.06);
    }

    .login-card .panel-body {
        padding: 30px 28px;
    }

    .login-card .control-label {
        font-size: 13px;
        font-weight: 600;
    }

    .login-card .form-control {
        height: 44px;
        border-radius: 10px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .login-card .form-control:focus {
        border-color: #F97316;
        box-shadow: 0 0 0 3px rgb(249 115 22 / 0.15);
    }

    .login-card .btn-block {
        height: 44px;
        border-radius: 10px;
        font-weight: 600;
        transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
    }

    .login-card .btn-primary.btn-block:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px -4px rgb(249 115 22 / 0.4);
    }

    .login-card .btn-default.btn-block:hover {
        transform: translateY(-1px);
    }

    .login-card .checkbox label,
    .login-card a.text-muted {
        font-size: 13px;
    }

    @media (max-height: 700px) {
        .customer-login-logo img {
            max-height: 42px;
        }

        .login-card .panel-body {
            padding: 20px 24px;
        }
    }

    @media (max-width: 480px) {
        .login-card .panel-body {
            padding: 22px 18px;
        }
    }
</style>
<div class="login-page-bg" aria-hidden="true">
    <span class="login-page-bg-shape login-page-bg-shape--one"></span>
    <span class="login-page-bg-shape login-page-bg-shape--two"></span>
</div>
<div class="login-page-content">
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
            //
            // Portal switch: get_company_logo() normally points this logo at
            // site_url() (the customer area itself); overriding via the
            // logo_href filter sends it to Admin Login instead, mirroring
            // the Admin Login logo's own link back to here.
            //
            // Scoped to just this one call, not the navbar's own logo
            // (template_parts/navigation.php uses the same helper): despite
            // navigation.php appearing "before" this file in index.php's
            // markup, ClientsController::layout() actually renders this
            // view (theme_template_view()) to a string FIRST, then renders
            // index.php - which is what runs get_template_part('navigation')
            // - afterward. So a filter left registered here would still be
            // active when the navbar's own logo call runs and hijack it too
            // (caught live: both links pointed at Admin Login). Removing it
            // right after this call, using the same closure reference,
            // keeps the effect scoped to only this logo.
            $portal_switch_logo_href = function () {
                return admin_url('authentication');
            };
            hooks()->add_filter('logo_href', $portal_switch_logo_href);
            get_dark_company_logo('', '');
            hooks()->remove_filter('logo_href', $portal_switch_logo_href);
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
        <div class="panel_s login-card">
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