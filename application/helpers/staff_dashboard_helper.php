<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Shared Staff Dashboard design system - ONE reusable set of rendering
 * functions used by every department portal's dashboard (dm_portal,
 * dev_portal, follow_up_management's Telecaller dashboard, and any
 * future portal), so the visual layout (greeting header, gradient KPI
 * cards, two-column widget panels) is defined exactly once instead of
 * copy-pasted per portal. Every function here only RENDERS markup from
 * data it's handed - none of them query the database or contain any
 * business logic; each portal's own existing controller/model calls are
 * completely unchanged, they just feed their already-fetched data into
 * these functions instead of a bespoke per-portal view.
 *
 * A portal adapts the dashboard purely by choosing which
 * staff_dashboard_kpi_card()/staff_dashboard_panel() calls to make and
 * what labels/values to pass in - the layout, spacing, colors, and
 * responsive grid are identical everywhere.
 */

/**
 * Central color-token registry - the single source of truth for the
 * "Projects=Blue / Pending=Orange / Completed=Green / Attendance=Teal /
 * Daily Work Update=Purple / Notifications=Indigo / Deadlines=Amber /
 * Performance=Pink" system, so no gradient hex value is ever hardcoded
 * more than once. Deliberately mid-saturation, soft gradients (Tailwind's
 * own 400->600 shade pairs) rather than harsh/neon tones.
 *
 * @param  string $key one of: projects, pending, completed, attendance,
 *                      work_update, notifications, deadlines, performance
 * @return array ['from' => hex, 'to' => hex]
 */
function staff_dashboard_color($key)
{
    $tokens = [
        'projects'      => ['from' => '#60a5fa', 'to' => '#3b82f6'], // blue
        'pending'       => ['from' => '#fbbf24', 'to' => '#f59e0b'], // orange
        'completed'     => ['from' => '#34d399', 'to' => '#10b981'], // green
        'attendance'    => ['from' => '#2dd4bf', 'to' => '#0d9488'], // teal
        'work_update'   => ['from' => '#a78bfa', 'to' => '#7c3aed'], // purple
        'notifications' => ['from' => '#818cf8', 'to' => '#4f46e5'], // indigo
        'deadlines'     => ['from' => '#fcd34d', 'to' => '#d97706'], // amber
        'performance'   => ['from' => '#f472b6', 'to' => '#db2777'], // pink
    ];

    return $tokens[$key] ?? $tokens['projects'];
}

/**
 * Emits the shared <style> block exactly once per page, no matter how
 * many times the other render functions below are called - guarded with
 * a static flag rather than relying on every caller to remember to
 * include it separately.
 *
 * @return void
 */
function staff_dashboard_styles()
{
    static $printed = false;

    if ($printed) {
        return;
    }
    $printed = true;
    ?>
    <style>
        .staff-dash-header {
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            padding: 24px 28px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 55%, #a21caf 100%);
            color: #fff;
        }
        .staff-dash-header .staff-dash-blob {
            position: absolute;
            border-radius: 50%;
            opacity: .18;
            background: #fff;
            pointer-events: none;
        }
        .staff-dash-header .staff-dash-blob-1 { width: 220px; height: 220px; top: -90px; right: -60px; }
        .staff-dash-header .staff-dash-blob-2 { width: 140px; height: 140px; bottom: -70px; right: 120px; }
        .staff-dash-header h2 {
            margin: 0 0 4px 0;
            font-weight: 600;
            position: relative;
        }
        .staff-dash-header .staff-dash-meta {
            position: relative;
            opacity: .92;
            font-size: 13px;
        }
        .staff-dash-header .staff-dash-motivation {
            position: relative;
            margin-top: 10px;
            font-size: 13px;
            opacity: .85;
        }

        .staff-dash-kpi-grid {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -8px 20px -8px;
        }
        .staff-dash-kpi-col {
            padding: 0 8px;
            margin-bottom: 16px;
            flex: 0 0 100%;
            max-width: 100%;
        }
        @media (min-width: 768px) {
            .staff-dash-kpi-col { flex: 0 0 50%; max-width: 50%; } /* tablet: 2 per row */
        }
        @media (min-width: 992px) {
            .staff-dash-kpi-col { flex: 0 0 33.3333%; max-width: 33.3333%; } /* desktop: 3 per row */
        }
        .staff-dash-kpi-card {
            display: block;
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            padding: 18px 20px;
            height: 100%;
            color: #fff;
            box-shadow: 0 4px 14px rgba(0,0,0,.10);
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: default;
            text-decoration: none;
        }
        .staff-dash-kpi-card:hover,
        .staff-dash-kpi-card:focus {
            color: #fff;
            text-decoration: none;
        }
        .staff-dash-kpi-card[data-clickable="1"] { cursor: pointer; }
        .staff-dash-kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px rgba(0,0,0,.16);
        }
        .staff-dash-kpi-card .staff-dash-kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255,255,255,.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            margin-bottom: 10px;
        }
        .staff-dash-kpi-card .staff-dash-kpi-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
        }
        .staff-dash-kpi-card .staff-dash-kpi-label {
            font-size: 12.5px;
            opacity: .92;
            margin-top: 2px;
        }
        .staff-dash-kpi-card .staff-dash-kpi-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            background: rgba(255,255,255,.25);
            border-radius: 999px;
            padding: 2px 9px;
            font-size: 11px;
            font-weight: 600;
        }

        .staff-dash-panel {
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
            margin-bottom: 20px;
            background: #fff;
            height: calc(100% - 20px);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .staff-dash-panel .staff-dash-panel-body { padding: 18px 20px; }
        .staff-dash-panel h5 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .staff-dash-panel .staff-dash-panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }
        .staff-dash-panel-viewall {
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .staff-dash-panel-clickable { cursor: pointer; }
        .staff-dash-panel-clickable:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0,0,0,.10);
        }
    </style>
    <?php
}

/**
 * One-time delegated click handler so an entire "View All" panel
 * (staff_dashboard_panel_open($title, $icon, $view_all_href)) navigates
 * on click anywhere in its body, WITHOUT breaking the per-row links every
 * panel's own list content already renders (a click starting on/inside an
 * <a>, <button>, <input>, or <select> is left alone to do its own thing -
 * checked via .closest() rather than nesting anchors, which HTML forbids).
 * Guarded with a static flag exactly like staff_dashboard_styles() so it
 * still only prints once per page no matter how many clickable panels a
 * dashboard has.
 *
 * @return void
 */
function staff_dashboard_panel_script()
{
    static $printed = false;

    if ($printed) {
        return;
    }
    $printed = true;
    ?>
    <script>
    (function() {
        document.addEventListener('click', function(e) {
            var panel = e.target.closest('.staff-dash-panel-clickable');
            if (!panel) {
                return;
            }
            if (e.target.closest('a, button, input, select, textarea')) {
                return;
            }
            var href = panel.getAttribute('data-staff-dash-panel-href');
            if (href) {
                window.location.href = href;
            }
        });
    })();
    </script>
    <?php
}

/**
 * Greeting header - "Good Morning, <Name> 👋", department, today's date,
 * and a small rotating motivational line. Time-of-day and the
 * motivational message are both derived deterministically (server hour /
 * day-of-year) rather than randomly, so the same page render is
 * consistent if it happens to run twice in the same request.
 *
 * @param  string $staff_name
 * @param  string $department
 * @return void
 */
function staff_dashboard_header($staff_name, $department)
{
    staff_dashboard_styles();

    $hour = (int) date('G');
    if ($hour < 12) {
        $greeting = _l('staff_dashboard_greeting_morning');
    } elseif ($hour < 17) {
        $greeting = _l('staff_dashboard_greeting_afternoon');
    } else {
        $greeting = _l('staff_dashboard_greeting_evening');
    }

    $messages = [
        _l('staff_dashboard_motivation_1'),
        _l('staff_dashboard_motivation_2'),
        _l('staff_dashboard_motivation_3'),
        _l('staff_dashboard_motivation_4'),
    ];
    $motivation = $messages[date('z') % count($messages)];
    ?>
    <div class="staff-dash-header">
        <span class="staff-dash-blob staff-dash-blob-1"></span>
        <span class="staff-dash-blob staff-dash-blob-2"></span>
        <h2><?= e($greeting); ?>, <?= e($staff_name); ?> 👋</h2>
        <div class="staff-dash-meta">
            <?= $department ? e($department) . ' &middot; ' : ''; ?><?= e(_d(date('Y-m-d'))); ?>
        </div>
        <div class="staff-dash-motivation"><?= e($motivation); ?></div>
    </div>
    <?php
}

/**
 * Opens the responsive KPI card row - 3 per row desktop, 2 tablet, 1
 * mobile (media queries in staff_dashboard_styles() above), paired with
 * staff_dashboard_kpi_grid_close().
 *
 * @return void
 */
function staff_dashboard_kpi_grid_open()
{
    staff_dashboard_styles();
    echo '<div class="staff-dash-kpi-grid">';
}

/**
 * @return void
 */
function staff_dashboard_kpi_grid_close()
{
    echo '</div>';
}

/**
 * One gradient KPI card. Every portal's dashboard calls this the same
 * way, just with different label/value/icon/color/link - this is the
 * single piece of markup that makes every department's dashboard look
 * identical while showing different data.
 *
 * @param  string      $label    e.g. "Assigned Projects"
 * @param  int|string  $value    the large number (or already-formatted string)
 * @param  string      $icon     font-awesome class, e.g. 'fa-solid fa-diagram-project'
 * @param  string      $color_key one of staff_dashboard_color()'s keys
 * @param  string      $subtitle small text under the label (optional)
 * @param  string|null $badge    small pill text in the corner (optional)
 * @param  string|null $href     wraps the card in a link if set
 * @param  string|null $onclick  used instead of $href for JS-driven cards (existing modals etc.)
 * @return void
 */
function staff_dashboard_kpi_card($label, $value, $icon, $color_key, $subtitle = '', $badge = null, $href = null, $onclick = null)
{
    $color = staff_dashboard_color($color_key);
    $tag   = ($href || $onclick) ? 'a' : 'div';
    $attrs = ($href || $onclick) ? 'data-clickable="1"' : '';
    if ($href) {
        $attrs .= ' href="' . e($href) . '"';
    }
    if ($onclick) {
        $attrs .= ' href="#" onclick="' . $onclick . '; return false;"';
    }
    ?>
    <div class="staff-dash-kpi-col">
        <<?= $tag; ?> <?= $attrs; ?> class="staff-dash-kpi-card" style="background:linear-gradient(135deg, <?= e($color['from']); ?>, <?= e($color['to']); ?>);">
            <?php if ($badge) { ?>
            <span class="staff-dash-kpi-badge"><?= e($badge); ?></span>
            <?php } ?>
            <div class="staff-dash-kpi-icon"><i class="<?= e($icon); ?>"></i></div>
            <div class="staff-dash-kpi-value"><?= e($value); ?></div>
            <div class="staff-dash-kpi-label"><?= e($label); ?></div>
            <?php if ($subtitle) { ?>
            <div class="staff-dash-kpi-label tw-mt-1" style="opacity:.8;font-size:11.5px;"><?= e($subtitle); ?></div>
            <?php } ?>
        </<?= $tag; ?>>
    </div>
    <?php
}

/**
 * Opens one widget panel (used for both the left column - Today's
 * Work/Pending Tasks/Upcoming Deadlines - and the right column - Recent
 * Notifications/Recent Daily Work Updates/Attendance Summary). Callers
 * render their own existing list/table markup between open() and
 * close(); this only provides the consistent card chrome.
 *
 * $view_all_href turns this into a "preview" panel: a small "View All"
 * link appears in the header, and clicking anywhere in the panel body
 * (except an inner link/button/control the caller's own list content
 * already renders - see staff_dashboard_panel_script()) navigates there
 * too. Optional and backward-compatible - every existing caller that
 * doesn't pass it renders byte-for-byte the same as before.
 *
 * @param  string      $title
 * @param  string|null $icon
 * @param  string|null $view_all_href
 * @param  string|null $view_all_label defaults to _l('staff_dashboard_view_all')
 * @return void
 */
function staff_dashboard_panel_open($title, $icon = null, $view_all_href = null, $view_all_label = null)
{
    staff_dashboard_styles();

    if ($view_all_href) {
        staff_dashboard_panel_script();
    }
    ?>
    <div class="staff-dash-panel<?= $view_all_href ? ' staff-dash-panel-clickable' : ''; ?>"<?= $view_all_href ? ' data-staff-dash-panel-href="' . e($view_all_href) . '"' : ''; ?>>
        <div class="staff-dash-panel-body">
            <div class="staff-dash-panel-heading">
                <h5><?php if ($icon) { ?><i class="<?= e($icon); ?> text-muted"></i><?php } ?> <?= e($title); ?></h5>
                <?php if ($view_all_href) { ?>
                <a href="<?= e($view_all_href); ?>" class="staff-dash-panel-viewall"><?= e($view_all_label ?: _l('staff_dashboard_view_all')); ?> &rarr;</a>
                <?php } ?>
            </div>
    <?php
}

/**
 * @return void
 */
function staff_dashboard_panel_close()
{
    ?>
        </div>
    </div>
    <?php
}
