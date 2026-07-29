<?php

defined('BASEPATH') or exit('No direct script access allowed');

$lang['staff_attendance']                        = 'Attendance';
$lang['staff_attendance_my_attendance']          = 'My Attendance';
$lang['staff_attendance_menu_title']             = 'Staff Attendance';
$lang['staff_attendance_add']                    = 'Add Attendance';
$lang['staff_attendance_settings']               = 'Attendance Settings';
$lang['staff_attendance_widget_title']           = "Today's Attendance";
$lang['staff_attendance_view_all']               = 'View All';

$lang['staff_attendance_column_employee']        = 'Employee';
$lang['staff_attendance_column_date']            = 'Date';
$lang['staff_attendance_column_login']           = 'Login Time';
$lang['staff_attendance_column_logout']          = 'Logout Time';
$lang['staff_attendance_column_working_hours']   = 'Working Hours';
$lang['staff_attendance_column_sessions']        = 'Sessions';
$lang['staff_attendance_column_status']          = 'Status';
$lang['staff_attendance_column_remarks']         = 'Remarks';

$lang['staff_attendance_sessions_title']            = 'Attendance Sessions';
$lang['staff_attendance_no_sessions']               = 'No sessions recorded for this day.';
$lang['staff_attendance_session_number']            = 'Session';
$lang['staff_attendance_session_active']            = 'Active';
$lang['staff_attendance_summary_title']             = 'Attendance Summary';
$lang['staff_attendance_summary_first_login']       = 'First Login';
$lang['staff_attendance_summary_last_logout']       = 'Last Logout';
$lang['staff_attendance_summary_sessions']          = 'Total Sessions';
$lang['staff_attendance_summary_total_working_hours'] = 'Total Working Hours';

# Smart Attendance v2 Part 2 - Admin Portal Attendance Module
$lang['staff_attendance_reports']                    = 'Reports';
$lang['staff_attendance_tab_leave_review']            = 'Leave Requests';
$lang['staff_attendance_tab_late_review']             = 'Late Arrivals';
$lang['staff_attendance_tab_early_review']            = 'Early Exits';
$lang['staff_attendance_tab_audit_log']               = 'Audit Log';
$lang['staff_attendance_review_column_employee']      = 'Employee';
$lang['staff_attendance_review_column_status']        = 'Status';
$lang['staff_attendance_review_column_reviewed_by']   = 'Reviewed By';
$lang['staff_attendance_review_column_actions']       = 'Actions';
$lang['staff_attendance_review_empty']                = 'No requests found.';
$lang['staff_attendance_override']                    = 'Override';
$lang['staff_attendance_override_hint']               = 'This request was already decided - approving/rejecting again overrides that decision.';
$lang['staff_attendance_review_remarks']              = 'Remarks';
$lang['staff_attendance_review_submit']               = 'Submit';
$lang['staff_attendance_approve_request']             = 'Approve Request';
$lang['staff_attendance_reject_request']              = 'Reject Request';
$lang['staff_attendance_invalid_decision']            = 'Invalid decision.';
$lang['staff_attendance_review_saved']                = 'Request reviewed successfully.';
$lang['staff_attendance_review_failed']               = 'Could not review this request.';
$lang['staff_attendance_no_audit_log']                = 'No audit log entries yet.';
$lang['staff_attendance_audit_column_when']           = 'When';
$lang['staff_attendance_audit_column_actor']          = 'By';
$lang['staff_attendance_audit_column_action']         = 'Action';
$lang['staff_attendance_audit_column_old']            = 'Old Value';
$lang['staff_attendance_audit_column_new']            = 'New Value';
$lang['staff_attendance_report_employee_wise']        = 'Employee-wise Report';
$lang['staff_attendance_report_department_wise']      = 'Department-wise Report';
$lang['staff_attendance_report_late']                 = 'Late Report';
$lang['staff_attendance_report_leave']                = 'Leave Report';
$lang['staff_attendance_report_column_department']    = 'Department';
$lang['staff_attendance_report_column_employees']     = 'Employees';
$lang['staff_attendance_report_column_present']       = 'Present';
$lang['staff_attendance_report_column_absent']        = 'Absent';
$lang['staff_attendance_report_column_late']          = 'Late';
$lang['staff_attendance_report_column_leave']         = 'Leave';
$lang['staff_attendance_report_column_attendance_rate'] = 'Attendance %';
$lang['staff_attendance_report_column_working_hours'] = 'Working Hours';
$lang['staff_attendance_report_empty']                = 'No data for this period.';
$lang['staff_attendance_widget_avg_working_hours']    = 'Avg. Working Hours';
$lang['staff_attendance_widget_pending_leave']        = 'Pending Leave Requests';
$lang['staff_attendance_widget_pending_late']         = 'Pending Late Requests';
$lang['staff_attendance_widget_pending_early']        = 'Pending Early Exit Requests';

$lang['staff_attendance_filter_all_employees']   = 'All Employees';
$lang['staff_attendance_filter_all_departments'] = 'All Departments';
$lang['staff_attendance_filter_all_statuses']    = 'All Statuses';
$lang['staff_attendance_filter_date_from']       = 'Date From';
$lang['staff_attendance_filter_date_to']         = 'Date To';

$lang['staff_attendance_edit_remarks']           = 'Edit Remarks';
$lang['staff_attendance_click_to_edit_remarks']  = 'Click to edit remarks';
$lang['staff_attendance_no_remarks']             = 'No remarks';

$lang['staff_attendance_add_missing_fields']     = 'Please select an employee and a date.';
$lang['staff_attendance_add_duplicate']          = 'An attendance record already exists for this employee on this date.';

$lang['staff_attendance_month_dashboard']        = 'Monthly Attendance Dashboard';
$lang['staff_attendance_month_prev']             = 'Previous';
$lang['staff_attendance_month_current']          = 'Current Month';
$lang['staff_attendance_month_next']             = 'Next';

$lang['staff_attendance_summary_present']        = 'Present Days';
$lang['staff_attendance_summary_absent']         = 'Absent Days';
$lang['staff_attendance_summary_leave']          = 'Leave Days';
$lang['staff_attendance_summary_half_day']       = 'Half Days';
$lang['staff_attendance_summary_late_login']     = 'Late Logins';
$lang['staff_attendance_summary_working_days']   = 'Working Days';
$lang['staff_attendance_summary_weekends']       = 'Weekends';

$lang['staff_attendance_rate']                   = 'Attendance Rate';
$lang['staff_attendance_rate_hint']               = 'Present Days / Working Days';

$lang['staff_attendance_working_hours_summary']  = 'Working Hours Summary';
$lang['staff_attendance_hours_total']            = 'Total';
$lang['staff_attendance_hours_average']          = 'Average / Day';
$lang['staff_attendance_hours_highest']          = 'Highest';
$lang['staff_attendance_hours_lowest']           = 'Lowest';

$lang['staff_attendance_day_mon']                = 'Mon';
$lang['staff_attendance_day_tue']                = 'Tue';
$lang['staff_attendance_day_wed']                = 'Wed';
$lang['staff_attendance_day_thu']                = 'Thu';
$lang['staff_attendance_day_fri']                = 'Fri';
$lang['staff_attendance_day_sat']                = 'Sat';
$lang['staff_attendance_day_sun']                = 'Sun';

$lang['staff_attendance_not_marked']             = 'Not Marked';
$lang['staff_attendance_no_login']               = 'No Login';

// Attendance Module Enhancement - tab navigation
$lang['staff_attendance_tab_history']            = 'Attendance History';
$lang['staff_attendance_tab_leave_requests']     = 'Leave Requests';
$lang['staff_attendance_tab_holidays']           = 'Holiday Calendar';
$lang['staff_attendance_tab_late_early']         = 'Late/Early Requests';

// Leave Requests
$lang['staff_attendance_request_leave']          = 'Request Leave';
$lang['staff_attendance_leave_type']             = 'Leave Type';
$lang['staff_attendance_leave_start_date']       = 'Start Date';
$lang['staff_attendance_leave_end_date']         = 'End Date';
$lang['staff_attendance_leave_days_hint']        = 'Number of days is calculated automatically from the selected dates.';
$lang['staff_attendance_leave_reason']           = 'Reason';
$lang['staff_attendance_leave_attachment']       = 'Attachment (Optional)';
$lang['staff_attendance_submit_request']         = 'Submit Request';
$lang['staff_attendance_cancel_request']         = 'Cancel Request';
$lang['staff_attendance_cancel_request_confirm'] = 'Are you sure you want to cancel this leave request?';

$lang['staff_attendance_leave_column_type']      = 'Leave Type';
$lang['staff_attendance_leave_column_from']      = 'From Date';
$lang['staff_attendance_leave_column_to']        = 'To Date';
$lang['staff_attendance_leave_column_days']      = 'Number of Days';
$lang['staff_attendance_leave_column_applied']   = 'Applied Date';
$lang['staff_attendance_leave_column_status']    = 'Status';
$lang['staff_attendance_leave_column_remarks']   = 'Operations Manager Remarks';
$lang['staff_attendance_leave_column_reviewed']  = 'Reviewed Date';
$lang['staff_attendance_leave_filter_all_statuses'] = 'All Statuses';

$lang['staff_attendance_leave_request_submitted'] = 'Leave request submitted successfully.';

// Holiday Calendar
$lang['staff_attendance_holiday_list_view']      = 'List View';
$lang['staff_attendance_holiday_calendar_view']  = 'Calendar View';
$lang['staff_attendance_holiday_column_name']    = 'Holiday Name';
$lang['staff_attendance_holiday_column_date']    = 'Date';
$lang['staff_attendance_holiday_column_day']     = 'Day';
$lang['staff_attendance_holiday_column_description'] = 'Description';
$lang['staff_attendance_no_holidays']            = 'No holidays found.';

// Late Arrival / Early Exit Requests
$lang['staff_attendance_late_arrival_detected']     = 'Late Arrival Detected';
$lang['staff_attendance_late_arrival_please_explain'] = 'Please provide a reason for your late arrival today.';
$lang['staff_attendance_early_exit_detected']       = 'Early Exit Detected';
$lang['staff_attendance_early_exit_please_explain'] = 'Please provide a reason for your early exit today.';
$lang['staff_attendance_late_delay']             = 'Delay Duration';
$lang['staff_attendance_early_duration']         = 'Early Exit Duration';
$lang['staff_attendance_minutes']                = 'minutes';
$lang['staff_attendance_late_reason']            = 'Reason';
$lang['staff_attendance_early_reason']           = 'Reason';

$lang['staff_attendance_late_arrival_tab_title']  = 'Late Arrival Requests';
$lang['staff_attendance_early_exit_tab_title']    = 'Early Exit Requests';
$lang['staff_attendance_no_late_arrival_requests'] = 'No late arrival requests found.';
$lang['staff_attendance_no_early_exit_requests']  = 'No early exit requests found.';

$lang['staff_attendance_request_missing_fields']  = 'Please fill in all required fields.';
$lang['staff_attendance_request_invalid_attachment'] = 'The uploaded attachment type is not allowed.';
$lang['staff_attendance_request_invalid_dates']   = 'Please provide valid leave dates.';
$lang['staff_attendance_request_not_eligible']    = 'There is nothing to report for today.';
$lang['staff_attendance_request_already_submitted'] = 'A request has already been submitted for today.';
$lang['staff_attendance_late_arrival_request_submitted'] = 'Late arrival request submitted successfully.';
$lang['staff_attendance_early_exit_request_submitted']   = 'Early exit request submitted successfully.';

// Employee Dashboard widgets
$lang['staff_attendance_widget_present_today']       = 'Present Today';
$lang['staff_attendance_widget_leave_requests']      = 'Leave Requests';
$lang['staff_attendance_widget_pending_requests']    = 'Pending Requests';
$lang['staff_attendance_widget_approved_requests']   = 'Approved Requests';
$lang['staff_attendance_widget_upcoming_holidays']   = 'Upcoming Holidays';
$lang['staff_attendance_widget_yes']                 = 'Yes';
$lang['staff_attendance_widget_no']                  = 'No';

// Holiday Calendar - Admin CRUD
$lang['staff_attendance_holiday_add']                = 'Add Holiday';
$lang['staff_attendance_holiday_edit']               = 'Edit Holiday';
$lang['staff_attendance_holiday_saved']              = 'Holiday saved successfully.';
$lang['staff_attendance_holiday_save_failed']        = 'Unable to save this holiday.';
$lang['staff_attendance_holiday_delete_confirm']     = 'Are you sure you want to delete this holiday?';
