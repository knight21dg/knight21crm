<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <title>
        <?php echo e(get_option('companyname')); ?> - <?php echo _l('admin_auth_login_heading'); ?>
    </title>
    <?php echo app_compile_css('admin-auth'); ?>
    <style>
    body,
    html {
        font-size: 16px;
    }

    body>* {
        font-size: 14px;
    }

    body {
        font-family: "Inter", sans-serif;
        color: #475569;
        margin: 0;
        padding: 0;
    }

    .company-logo {
        padding: 25px 10px;
        display: block;
    }

    .company-logo img {
        margin: 0 auto;
        display: block;
    }

    @media screen and (max-height: 575px),
    screen and (min-width: 992px) and (max-width:1199px) {

        #rc-imageselect,
        .g-recaptcha {
            transform: scale(0.83);
            -webkit-transform: scale(0.83);
            transform-origin: 0 0;
            -webkit-transform-origin: 0 0;
        }
    }

    /* ---- Login page layout: full-viewport centering, no scroll ---- */
    html, body.login_admin {
        height: 100%;
    }

    body.login_admin {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow-x: hidden;
        position: relative;
        background: linear-gradient(150deg, #f5f6ff 0%, #f7f4ff 45%, #fdf5fd 100%);
    }

    /* ---- Brand background: soft gradient wash + 3 large blurred brand-color
       circles (top-left/top-right/bottom-right) + a couple of very faint
       decorative shapes. Heavy blur + low opacity keeps this a soft tint,
       not a colorful page - the login card stays the visual focus. ---- */
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
        filter: blur(95px);
    }

    .login-page-bg-shape--one {
        width: 520px;
        height: 520px;
        top: -180px;
        left: -160px;
        background: #2563EB;
        opacity: .16;
    }

    .login-page-bg-shape--two {
        width: 480px;
        height: 480px;
        bottom: -190px;
        right: -140px;
        background: #7C3AED;
        opacity: .16;
    }

    .login-page-bg-shape--three {
        width: 420px;
        height: 420px;
        top: -120px;
        right: -120px;
        background: #4F46E5;
        opacity: .12;
    }

    .login-page-bg-shape--deco {
        filter: blur(60px);
    }

    .login-page-bg-shape--deco-one {
        width: 220px;
        height: 220px;
        bottom: 10%;
        left: 6%;
        background: #A855F7;
        opacity: .08;
    }

    .login-page-bg-shape--deco-two {
        width: 160px;
        height: 160px;
        top: 12%;
        left: 42%;
        background: #2563EB;
        opacity: .06;
    }

    .login-page-shell {
        position: relative;
        z-index: 1;
        width: 100%;
        padding: 24px 16px;
        box-sizing: border-box;
    }

    .authentication-form-wrapper {
        width: 100%;
    }

    .authentication-form-wrapper .company-logo {
        padding: 0 0 18px;
        display: flex;
        justify-content: center;
    }

    /* No badge/container - just the logo itself, as a plain branding
       element (not clickable - get_dark_company_logo() always renders an
       <a>, so pointer-events:none is what actually neutralizes it rather
       than a routing/hook change). A soft brand-color drop-shadow (not a
       box-shadow, since there's no background shape to cast it from)
       stands in for the old white badge's shadow, following the image's
       own alpha silhouette. */
    .authentication-form-wrapper .company-logo a {
        display: inline-flex;
        cursor: default;
        pointer-events: none;
    }

    .authentication-form-wrapper .company-logo img {
        max-height: 58px;
        width: auto;
        filter: drop-shadow(0 8px 18px rgb(79 70 229 / 0.28));
    }

    .authentication-form-wrapper h1 {
        font-size: 23px;
        font-weight: 700;
        line-height: 1.3;
        color: #0f172a;
        letter-spacing: -.01em;
    }

    .authentication-form-wrapper p {
        font-size: 13.5px;
        color: #64748b;
        margin-top: 4px;
    }

    /* Glass card - translucent white + backdrop blur over the brand wash,
       soft brand-tinted shadow instead of a heavy neutral one. */
    .login-card {
        background: rgb(255 255 255 / 0.78) !important;
        border-radius: 20px !important;
        border: 1px solid rgb(255 255 255 / 0.6) !important;
        box-shadow: 0 20px 45px -16px rgb(79 70 229 / 0.20), 0 8px 18px -10px rgb(37 99 235 / 0.10) !important;
        -webkit-backdrop-filter: blur(20px);
        backdrop-filter: blur(20px);
    }

    .login-card .control-label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    .login-card .form-control {
        height: 45px;
        border-radius: 12px;
        border-color: #e2e0fa;
        background: rgb(255 255 255 / 0.9);
        font-size: 14px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .login-card .form-control:focus {
        border-color: #7C3AED;
        box-shadow: 0 0 0 3.5px rgb(124 58 237 / 0.14);
    }

    /* Brand gradient button, replacing the dark solid fill. */
    .login-card .btn-primary.btn-block {
        height: 46px;
        border-radius: 12px;
        letter-spacing: .01em;
        background: linear-gradient(135deg, #2563EB 0%, #7C3AED 100%) !important;
        border: none !important;
        box-shadow: 0 10px 22px -8px rgb(124 58 237 / 0.45);
        transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
    }

    .login-card .btn-primary.btn-block:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px -8px rgb(124 58 237 / 0.55);
        opacity: .97;
    }

    .login-card .btn-primary.btn-block:active {
        transform: translateY(0);
    }

    .login-card .checkbox label,
    .login-card a.text-muted {
        font-size: 13px;
    }

    .login-card a.text-muted:hover {
        color: #7C3AED;
    }

    @media (max-height: 700px) {
        .authentication-form-wrapper .company-logo img {
            max-height: 46px;
        }

        .authentication-form-wrapper .company-logo {
            padding-bottom: 10px;
        }

        .authentication-form-wrapper .tw-mb-5 {
            margin-bottom: 12px !important;
        }

        .login-card {
            padding-top: 20px !important;
            padding-bottom: 20px !important;
        }
    }

    @media (max-width: 480px) {
        .login-page-shell {
            padding: 16px 10px;
        }

        .authentication-form-wrapper .company-logo img {
            max-height: 46px;
        }

        .login-card {
            padding-left: 18px !important;
            padding-right: 18px !important;
        }
    }
    </style>
    <?php if (show_recaptcha()) { ?>
    <script src='https://www.google.com/recaptcha/api.js'></script>
    <?php } ?>
    <?php if (file_exists(FCPATH . 'assets/css/custom.css')) { ?>
    <link href="<?php echo base_url('assets/css/custom.css'); ?>" rel="stylesheet" id="custom-css">
    <?php } ?>
    <?php hooks()->do_action('app_admin_authentication_head'); ?>
</head>