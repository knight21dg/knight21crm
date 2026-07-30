<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>

<body class="tw-bg-neutral-100 login_admin">

    <div class="login-page-bg" aria-hidden="true">
        <span class="login-page-bg-shape login-page-bg-shape--one"></span>
        <span class="login-page-bg-shape login-page-bg-shape--three"></span>
        <span class="login-page-bg-shape login-page-bg-shape--two"></span>
        <span class="login-page-bg-shape login-page-bg-shape--deco login-page-bg-shape--deco-one"></span>
        <span class="login-page-bg-shape login-page-bg-shape--deco login-page-bg-shape--deco-two"></span>
    </div>

    <div class="login-page-shell">
        <div class="tw-max-w-md tw-mx-auto authentication-form-wrapper tw-relative tw-z-20">
            <div class="company-logo text-center">
                <?php get_dark_company_logo(); ?>
            </div>

            <div class=" text-center tw-mb-5">
                <h1 class="tw-mb-1">
                    <?= _l('admin_auth_login_heading'); ?>
                </h1>
                <p>
                    <?= _l('welcome_back_sign_in'); ?>
                </p>
            </div>

            <div
                class="login-card tw-mx-2 sm:tw-mx-6 tw-py-8 tw-px-6 sm:tw-px-8">

                <?php $this->load->view('authentication/includes/alerts'); ?>

            <?= form_open($this->uri->uri_string()); ?>

            <?= validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

            <?php hooks()->do_action('after_admin_login_form_start'); ?>

            <div class="form-group">
                <label for="email" class="control-label !tw-mb-3">
                    <?= _l('admin_auth_login_email'); ?>
                </label>
                <input type="email" id="email" name="email" class="form-control" autofocus="1">
            </div>

            <div class="form-group tw-mt-8">
                <span class="tw-inline-flex tw-justify-between tw-items-end tw-w-full tw-mb-3">
                    <label for="password" class="control-label !tw-m-0">
                        <?= _l('admin_auth_login_password'); ?>
                    </label>
                    <a href="<?= admin_url('authentication/forgot_password'); ?>"
                        class="text-muted">
                        <?= _l('admin_auth_login_fp'); ?>
                    </a>
                </span>

                <input type="password" id="password" name="password" class="form-control">
            </div>

            <?php if (show_recaptcha()) { ?>
            <div class="g-recaptcha tw-mb-4"
                data-sitekey="<?= get_option('recaptcha_site_key'); ?>">
            </div>
            <?php } ?>

            <div class="form-group">
                <div class="checkbox checkbox-inline">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">
                        <?= _l('admin_auth_login_remember_me'); ?></label>
                </div>
            </div>

            <div class="tw-mt-6">
                <button type="submit" class="btn btn-primary btn-block tw-font-semibold tw-py-2">
                    <?= _l('admin_auth_login_button'); ?>
                </button>
            </div>

            <?php hooks()->do_action('before_admin_login_form_close'); ?>

            <?= form_close(); ?>
            </div>
        </div>
    </div>

</body>

</html>