<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Shared Staff Dashboard design system (application/helpers/staff_dashboard_helper.php)
// - identical keys duplicated per-module lang file since module language
// files are never autoloaded (each module loads only its own).
$lang['staff_dashboard_greeting_morning']   = 'Good Morning';
$lang['staff_dashboard_greeting_afternoon'] = 'Good Afternoon';
$lang['staff_dashboard_greeting_evening']   = 'Good Evening';
$lang['staff_dashboard_motivation_1']       = 'Small steps every day lead to big results.';
$lang['staff_dashboard_motivation_2']       = 'Focus on progress, not perfection.';
$lang['staff_dashboard_motivation_3']       = 'Great work starts with a great plan for today.';
$lang['staff_dashboard_motivation_4']       = 'Consistency is what turns effort into achievement.';

$lang['followup']                        = 'Follow-up';
$lang['followups']                       = 'Follow-ups';
$lang['all_followups']                   = 'All Follow-ups';
$lang['follow_up_management']            = 'Follow-up Management';
$lang['my_cases']                        = 'My Cases';
$lang['my_follow_ups']                   = 'My Follow-ups';
$lang['my_follow_ups_hint']              = 'Your assigned leads currently in Follow-up Management. Open Workspace to call, log outcomes, and update follow-ups without leaving this page.';
$lang['followup_due_today']              = 'Today';
$lang['followup_due_tomorrow']           = 'Tomorrow';
$lang['followup_widget_no_leads']        = 'No follow-up leads right now.';
$lang['followup_column_email']           = 'Email';
$lang['followup_column_last_contact']    = 'Last Contact';
$lang['followup_column_last_activity']   = 'Last Activity';
$lang['followup_column_assigned_date']   = 'Assigned Date';
$lang['followup_column_priority']        = 'Priority';
$lang['followup_column_actions']         = 'Actions';
$lang['followup_column_latest_note']     = 'Latest Note';
$lang['followup_column_lead_name']       = 'Lead Name';
$lang['followup_column_phone']           = 'Phone';
$lang['followup_column_whatsapp']        = 'WhatsApp';
$lang['followup_column_source']          = 'Source';
$lang['followup_column_lead_status']     = 'Lead Status';
$lang['followup_column_call_outcome']    = 'Call Outcome';
$lang['followup_column_workspace']       = 'Workspace';
$lang['followup_workspace_open']         = 'Open Workspace';
$lang['todays_followups']                = "Today's Follow-ups";
$lang['followup_permission_view_department'] = 'View Department Cases (Manager)';
$lang['followups_view_none_admin']       = 'Showing your follow-ups and follow-ups created by you.';
$lang['followup_added_successfully']     = 'Follow-up logged successfully';
$lang['followup_deleted']                = 'Follow-up deleted';
$lang['followup_failed_to_delete']       = 'Failed to delete follow-up';
$lang['followup_related']                = 'Related To';
$lang['followup_type_label']             = 'Follow-up Type';
$lang['followup_outcome_label']          = 'Outcome';
$lang['followup_notes_label']            = 'Notes';
$lang['followup_date_label']             = 'Follow-up Date';
$lang['followup_next_follow_up_date_label'] = 'Next Follow-up Date';
$lang['followup_staff_label']            = 'Staff';
$lang['followup_status_label']           = 'Status';
$lang['followup_history']                = 'Follow-up History';
$lang['followup_history_hint']           = 'Your complete customer interaction history.';
$lang['followup_column_contact_method']      = 'Contact Method';
$lang['followup_column_total_interactions']  = 'Total Interactions';
$lang['followup_column_last_interaction']    = 'Last Interaction';
$lang['followup_widget_title']           = "My Today's Follow-ups";
$lang['followup_view_all']               = 'View all follow-ups';
$lang['followup_reminder_description']   = 'Follow up with %s';
$lang['followup_case']                   = 'Follow-up Case';
$lang['followup_case_auto_created_note'] = 'Case automatically created because the lead status changed to Follow-up. Log what happens next below.';
$lang['followup_task_title']             = 'Follow-up : %s';
$lang['followup_task_auto_created_note'] = 'Case automatically created because Lead entered Follow-up status.';

$lang['followup_type_call']              = 'Call';
$lang['followup_type_email']             = 'Email';
$lang['followup_type_meeting']           = 'Meeting';
$lang['followup_type_whatsapp']          = 'WhatsApp';
$lang['followup_type_other']             = 'Other';

$lang['followup_outcome_interested']     = 'Interested';
$lang['followup_outcome_not_interested'] = 'Not Interested';
$lang['followup_outcome_no_answer']      = 'No Answer';
$lang['followup_outcome_rescheduled']    = 'Rescheduled';
$lang['followup_outcome_converted']      = 'Converted';
$lang['followup_outcome_other']          = 'Other';
$lang['followup_outcome_connected']        = 'Connected';
$lang['followup_outcome_busy']             = 'Busy';
$lang['followup_outcome_switched_off']     = 'Switched Off';
$lang['followup_outcome_wrong_number']     = 'Wrong Number';
$lang['followup_outcome_call_back_later']  = 'Call Back Later';
$lang['followup_outcome_sales_discussion'] = 'Sales Discussion';
$lang['followup_outcome_proposal_sent']    = 'Proposal Sent';
$lang['followup_outcome_negotiation']      = 'Negotiation';

$lang['followup_meeting_type_in_person']  = 'In-Person';
$lang['followup_meeting_type_video_call'] = 'Video Call';
$lang['followup_meeting_type_phone_call'] = 'Phone Call';

$lang['followup_pipeline_lead_assigned']        = 'Lead Assigned';
$lang['followup_pipeline_call_attempted']       = 'Call Attempted';
$lang['followup_pipeline_interested']           = 'Interested';
$lang['followup_pipeline_follow_up_scheduled']  = 'Follow-up Scheduled';
$lang['followup_pipeline_appointment_scheduled'] = 'Appointment Scheduled';
$lang['followup_pipeline_sales_discussion']     = 'Sales Discussion';
$lang['followup_pipeline_proposal_sent']        = 'Proposal Sent';
$lang['followup_pipeline_negotiation']          = 'Negotiation';
$lang['followup_pipeline_won_customer']         = 'Won Customer';
$lang['followup_pipeline_converted_customer']   = 'Converted Customer';

$lang['followup_appointment_meeting_type'] = 'Meeting Type';
$lang['followup_appointment_location']     = 'Location';
$lang['followup_lead_activity_interaction_logged'] = 'Follow-up %s logged';

$lang['followup_workspace_call_outcome']            = 'Call Outcome';
$lang['followup_workspace_call_outcome_hint']       = 'Log the result of your call - stored in Follow-up History.';
$lang['followup_workspace_action_log_call']         = 'Log Call';
$lang['followup_workspace_next_follow_up_section']  = 'Next Follow-up';
$lang['followup_workspace_action_update_follow_up'] = 'Update Next Follow-up';
$lang['followup_workspace_pipeline']                = 'Lead Pipeline';
$lang['followup_workspace_appointment']             = 'Appointment';
$lang['followup_workspace_appointment_datetime']    = 'Date & Time';
$lang['followup_workspace_action_schedule_appointment'] = 'Schedule Appointment';
$lang['followup_workspace_action_request_conversion']   = 'Request Customer Conversion';
$lang['followup_workspace_conversion_requested_note']   = 'Telecaller requested customer conversion for this lead.';

$lang['followup_status_pending']           = 'Pending';
$lang['followup_status_completed']         = 'Completed';
$lang['followup_status_no_further_action'] = 'No Further Action';

$lang['als_reports_followups_submenu']   = 'Follow-ups';
$lang['reports_followups']               = 'Follow-ups Report';
$lang['report_followups_by_outcome']     = 'Follow-ups by Outcome';
$lang['report_followups_by_staff']       = 'Follow-ups by Staff';

$lang['not_activity_new_followup_logged'] = 'logged a new follow-up, performed by %s';

$lang['followup_filter_all_statuses']     = 'All Statuses';
$lang['followup_filter_all_staff']        = 'All Staff';
$lang['followup_filter_all_departments']  = 'All Departments';
$lang['followup_filter_all_outcomes']     = 'All Outcomes';
$lang['followup_filter_all_priorities']     = 'All Priorities';
$lang['followup_filter_all_case_statuses']  = 'All Case Statuses';
$lang['followup_filter_date_from']          = 'From';
$lang['followup_filter_date_to']            = 'To';

// My Cases / Today's Follow-ups KPI cards
$lang['card_my_open_cases']    = 'My Open Cases';
$lang['card_todays_followups'] = "Today's Follow-ups";
$lang['card_overdue_cases']    = 'Overdue Cases';
$lang['card_completed_today']  = 'Completed Today';
$lang['card_high_priority']    = 'High Priority Cases';

// Today's Follow-ups sections
$lang['section_overdue']         = 'Overdue';
$lang['section_due_today']       = 'Due Today';
$lang['section_completed_today'] = 'Completed Today';
$lang['case_no_cases_in_section'] = 'No cases here.';
$lang['case_quick_open']          = 'Open';

// Dashboard
$lang['dashboard']                     = 'Dashboard';
$lang['card_total_open_cases']         = 'Total Open Cases';
$lang['card_my_assigned_cases']        = 'My Assigned Cases';
$lang['card_closed_today']             = 'Closed Today';
$lang['card_completed_this_week']      = 'Completed This Week';
$lang['card_avg_response_time']        = 'Average Response Time';
$lang['chart_cases_by_status']         = 'Cases by Status';
$lang['chart_cases_by_priority']       = 'Cases by Priority';
$lang['chart_cases_created_vs_closed'] = 'Cases Created vs Closed';
$lang['chart_daily_followup_activity'] = 'Daily Follow-up Activity';
$lang['chart_department_performance']  = 'Department Performance';
$lang['chart_monthly_closure_trend']   = 'Monthly Closure Trend';
$lang['chart_created']                 = 'Created';
$lang['chart_closed']                  = 'Closed';
$lang['upcoming_followups']            = 'Upcoming Follow-ups';
$lang['recent_activities']             = 'Recent Activities';
$lang['todays_performance']            = "Today's Performance";
$lang['quick_actions']                 = 'Quick Actions';
$lang['perf_calls_logged_today']       = 'Calls Logged Today';
$lang['perf_meetings_scheduled_today'] = 'Meetings Scheduled Today';
$lang['perf_reminders_created_today']  = 'Reminders Created Today';
$lang['perf_cases_closed_today']       = 'Cases Closed Today';
$lang['perf_cases_reopened_today']     = 'Cases Reopened Today';
$lang['quick_action_calendar']         = 'Calendar';
$lang['quick_action_reports']          = 'Reports';
$lang['quick_action_open_case']        = 'Open Case';
$lang['quick_action_create_reminder']  = 'Create Reminder';
$lang['case_no_upcoming_followups']    = 'No upcoming follow-ups.';
$lang['case_no_recent_activity']       = 'No recent activity.';

// Calendar
$lang['followup_calendar_type_reminder'] = 'Follow-up Reminder';
$lang['followup_calendar_type_meeting']  = 'Meeting';
$lang['followup_calendar_type_call']     = 'Call Schedule';

// My Cases columns
$lang['case_column_case_id']         = 'Case ID';
$lang['case_column_lead_name']       = 'Lead Name';
$lang['case_column_company']         = 'Company';
$lang['case_column_department']      = 'Department';
$lang['case_column_priority']        = 'Priority';
$lang['case_column_case_status']     = 'Case Status';
$lang['case_column_next_follow_up']  = 'Next Follow-up';
$lang['case_column_last_activity']   = 'Last Activity';
$lang['case_column_assigned_staff']  = 'Assigned Staff';
$lang['case_column_created_date']    = 'Created Date';

// Priority
$lang['followup_priority_low']    = 'Low';
$lang['followup_priority_medium'] = 'Medium';
$lang['followup_priority_high']   = 'High';
$lang['followup_priority_urgent'] = 'Urgent';

// Case lifecycle
$lang['followup_case_status_open']   = 'Open';
$lang['followup_case_status_closed'] = 'Closed';

// Timeline (tblfollowup_history) entry types
$lang['followup_history_call']          = 'Call Logged';
$lang['followup_history_meeting']       = 'Meeting Scheduled';
$lang['followup_history_reminder']      = 'Reminder Scheduled';
$lang['followup_history_note']          = 'Note Added';
$lang['followup_history_status_change'] = 'Status Changed';

// Activity Log (tblfollowup_activity) action types
$lang['followup_activity_case_created']     = 'Case Created';
$lang['followup_activity_reassigned']       = 'Reassigned';
$lang['followup_activity_priority_changed'] = 'Priority Changed';
$lang['followup_activity_status_changed']   = 'Status Changed';
$lang['followup_activity_reminder_created'] = 'Reminder Created';
$lang['followup_activity_meeting_created']  = 'Meeting Created';
$lang['followup_activity_meeting_cancelled']  = 'Meeting Cancelled';
$lang['followup_activity_reminder_cancelled'] = 'Reminder Cancelled';
$lang['followup_activity_reminder_overdue']   = 'Reminder Overdue';
$lang['followup_activity_case_closed']      = 'Case Closed';
$lang['followup_activity_case_reopened']    = 'Case Reopened';

// Case Detail page
$lang['case_lead_information']  = 'Lead Information';
$lang['case_case_information']  = 'Case Information';
$lang['case_field_company']         = 'Company';
$lang['case_field_contact_person']  = 'Contact Person';
$lang['case_field_phone']           = 'Phone';
$lang['case_field_email']           = 'Email';
$lang['case_field_department']      = 'Department';
$lang['case_field_assigned_staff']  = 'Assigned Staff';
$lang['case_field_priority']        = 'Priority';
$lang['case_field_case_status']     = 'Case Status';
$lang['case_field_created_date']    = 'Created Date';
$lang['case_field_last_activity']   = 'Last Activity';
$lang['case_field_next_follow_up']  = 'Next Follow-up';
$lang['case_timeline']      = 'Timeline';
$lang['case_activity_log']  = 'Activity Log';
$lang['case_no_history']    = 'No follow-up activity logged yet.';
$lang['case_no_activity']   = 'No activity recorded yet.';
$lang['case_not_set']       = 'Not set';

// Case actions
$lang['case_action_log_call']          = 'Log Call';
$lang['case_action_schedule_meeting']  = 'Schedule Meeting';
$lang['case_action_schedule_reminder'] = 'Schedule Reminder';
$lang['case_action_add_note']          = 'Add Note';
$lang['case_action_change_status']     = 'Change Status';
$lang['case_action_change_priority']   = 'Change Priority';
$lang['case_action_reassign_staff']    = 'Reassign Staff';
$lang['case_action_close_case']        = 'Close Case';
$lang['case_action_reopen_case']       = 'Reopen Case';

// Modal fields / confirms
$lang['case_field_outcome']            = 'Outcome';
$lang['case_field_notes']              = 'Notes';
$lang['case_field_next_follow_up_date'] = 'Next Follow-up Date (optional)';
$lang['case_field_meeting_datetime']   = 'Meeting Date & Time';
$lang['case_field_reminder_datetime']  = 'Reminder Date & Time';
$lang['case_field_new_status']         = 'New Status';
$lang['case_field_new_priority']       = 'New Priority';
$lang['case_field_new_staff']          = 'Reassign To';
$lang['case_confirm_close_case']       = 'Close this case? You can reopen it later.';
$lang['case_confirm_reopen_case']      = 'Reopen this case?';
$lang['case_action_success']           = 'Done';
$lang['case_action_failed']            = 'Action failed';
$lang['case_access_denied']            = 'You do not have access to this case';

// Notification Center (Phase 6) - in-app notification descriptions.
// vsprintf %s placeholders, matching core's own convention
// (see 'not_assigned_lead_to_you'/'not_new_reminder_for' in
// application/language/english/english_lang.php).
$lang['not_followup_case_assigned']       = 'A new Follow-up Case has been assigned to you - %s';
$lang['not_followup_case_reassigned']     = 'A Follow-up Case has been reassigned to you - %s';
$lang['not_followup_case_high_priority']  = 'A Follow-up Case has been marked High Priority - %s';
$lang['not_followup_meeting_scheduled']   = 'A meeting was scheduled for your Follow-up Case - %s';
$lang['not_followup_meeting_updated']     = 'The meeting for your Follow-up Case was updated - %s';
$lang['not_followup_meeting_cancelled']   = 'The meeting for your Follow-up Case was cancelled - %s';
$lang['not_followup_reminder_created']    = 'A reminder was scheduled for your Follow-up Case - %s';
$lang['not_followup_reminder_cancelled']  = 'The reminder for your Follow-up Case was cancelled - %s';
$lang['not_followup_reminder_overdue']    = 'Your Follow-up Case reminder is overdue - %s';
$lang['not_followup_case_closed']         = 'Follow-up Case closed - %s';
$lang['not_followup_case_reopened']       = 'Follow-up Case reopened - %s';

// Notification Center - email subjects/bodies (used with the shared
// Follow_up_case_notification mailable/template, not one template per event).
$lang['followup_email_template_name']            = 'Follow-up Case Notification';
$lang['followup_email_case_assigned_title']       = 'New Follow-up Case Assigned';
$lang['followup_email_case_assigned_body']        = 'A new Follow-up Case has been assigned to you for %s.';
$lang['followup_email_case_reassigned_title']     = 'Follow-up Case Reassigned to You';
$lang['followup_email_case_reassigned_body']      = 'A Follow-up Case has been reassigned to you for %s.';
$lang['followup_email_high_priority_title']       = 'High Priority Follow-up Case';
$lang['followup_email_high_priority_body']        = 'Your Follow-up Case for %s has been marked High Priority.';
$lang['followup_email_meeting_scheduled_title']   = 'Meeting Scheduled';
$lang['followup_email_meeting_scheduled_body']    = 'A meeting was scheduled for %s on %s.';
$lang['followup_email_meeting_updated_title']     = 'Meeting Updated';
$lang['followup_email_meeting_updated_body']      = 'The meeting for %s was updated to %s.';
$lang['followup_email_case_reopened_title']       = 'Follow-up Case Reopened';
$lang['followup_email_case_reopened_body']        = 'Your Follow-up Case for %s has been reopened.';
$lang['followup_email_reminder_overdue_title']    = 'Follow-up Reminder Overdue';
$lang['followup_email_reminder_overdue_body']     = 'Your Follow-up Case for %s was due on %s and is now overdue.';

// Notification Center page
$lang['notification_center']         = 'Notification Center';
$lang['notification_column_icon']    = '';
$lang['notification_column_title']   = 'Title';
$lang['notification_column_description'] = 'Description';
$lang['notification_column_related_lead'] = 'Related Lead';
$lang['notification_column_related_case'] = 'Related Case';
$lang['notification_no_notifications'] = 'No notifications yet.';
$lang['recent_notifications']        = 'Recent Notifications';

// Notification Center - titles shown per notification row (short form of
// the not_followup_* description keys above)
$lang['followup_notification_case_assigned']      = 'New Case Assigned';
$lang['followup_notification_case_reassigned']    = 'Case Reassigned';
$lang['followup_notification_reminder_created']   = 'Reminder Created';
$lang['followup_notification_reminder_overdue']   = 'Reminder Overdue';
$lang['followup_notification_meeting_scheduled']  = 'Meeting Scheduled';
$lang['followup_notification_meeting_updated']    = 'Meeting Updated';
$lang['followup_notification_meeting_cancelled']  = 'Meeting Cancelled';
$lang['followup_notification_reminder_cancelled'] = 'Reminder Cancelled';
$lang['followup_notification_case_closed']        = 'Case Closed';
$lang['followup_notification_case_reopened']      = 'Case Reopened';
$lang['followup_notification_high_priority']      = 'High Priority Case Assigned';

// Performance Dashboard (Phase 7)
$lang['performance_dashboard']       = 'Performance Dashboard';
$lang['staff_performance_detail']    = 'Staff Performance Detail';
$lang['card_total_cases_assigned']   = 'Total Cases Assigned';
$lang['card_total_cases_closed']     = 'Total Cases Closed';
$lang['card_open_cases']             = 'Open Cases';
$lang['card_avg_closure_time']       = 'Average Closure Time';
$lang['card_calls_logged']           = 'Calls Logged';
$lang['card_meetings_scheduled']     = 'Meetings Scheduled';
$lang['card_reminders_created']      = 'Reminders Created';
$lang['card_followups_completed']    = 'Follow-ups Completed';
$lang['card_cases_reopened']         = 'Cases Reopened';
$lang['chart_daily_productivity']    = 'Daily Productivity';
$lang['chart_weekly_productivity']   = 'Weekly Productivity';
$lang['chart_monthly_productivity']  = 'Monthly Productivity';
$lang['chart_calls_vs_meetings']     = 'Calls vs Meetings';
$lang['chart_case_status_trend']     = 'Case Status Trend';
$lang['chart_staff_comparison']      = 'Staff Performance Comparison';
$lang['chart_avg_response_time_trend'] = 'Average Response Time Trend';
$lang['top_performers']              = 'Top Performers';
$lang['leaderboard_rank']            = 'Rank';
$lang['daily_activity']              = 'Daily Activity';
$lang['weekly_activity']             = 'Weekly Activity';
$lang['monthly_activity']            = 'Monthly Activity';
$lang['week_of']                     = 'Week of';
$lang['hours']                       = 'hours';

// Reports (Phase 8)
$lang['followup_report_case_summary']         = 'Case Summary Report';
$lang['followup_report_staff_performance']    = 'Staff Performance Report';
$lang['followup_report_department_performance'] = 'Department Performance Report';
$lang['followup_report_activity']             = 'Activity Report';
$lang['followup_report_sla']                  = 'SLA Report';
$lang['followup_report_productivity']         = 'Productivity Report';
$lang['followup_report_executive_dashboard']  = 'Executive Dashboard Report';
$lang['followup_outcome_trend']               = 'Outcome Trend';
$lang['followup_call_success_rate']           = 'Call Success Rate';
$lang['followup_meeting_conversion_rate']     = 'Meeting Conversion Rate';
$lang['followup_sla_thresholds_note']         = 'SLA thresholds: Response within %s hours, Closure within %s hours.';
$lang['followup_overdue_percent']             = 'Overdue %';
$lang['followup_within_sla']                  = 'Cases Within SLA';
$lang['followup_outside_sla']                 = 'Cases Outside SLA';

// Telecaller Workspace (Task-embedded Follow-up workspace)
$lang['followup_history_reschedule']              = 'Rescheduled';
$lang['followup_lead_activity_completed']         = 'Follow-up marked %s';
$lang['followup_workspace_title']                 = 'Telecaller Workspace';
$lang['followup_workspace_overdue']               = 'Overdue';
$lang['followup_workspace_open_lead']             = 'Open Lead';
$lang['followup_workspace_next_follow_up']        = 'Next Follow-up Date';
$lang['followup_workspace_reminder_status']       = 'Reminder Status';
$lang['followup_workspace_reminder_notified']     = 'Notified';
$lang['followup_workspace_reminder_overdue']      = 'Overdue';
$lang['followup_workspace_reminder_pending']      = 'Pending';
$lang['followup_workspace_notes']                 = 'Notes';
$lang['followup_workspace_no_notes']              = 'No notes yet.';
$lang['followup_workspace_quick_actions']         = 'Quick Actions';
$lang['followup_workspace_action_call']           = 'Call';
$lang['followup_workspace_action_email']          = 'Email';
$lang['followup_workspace_action_reschedule']     = 'Reschedule';
$lang['followup_workspace_action_complete']       = 'Complete Follow-up';
$lang['followup_workspace_action_mark_pending']   = 'Mark Pending';
$lang['followup_workspace_action_cancel']         = 'Cancel';
$lang['followup_workspace_confirm_cancel']        = 'Cancel this follow-up? The linked task will be marked complete.';
$lang['followup_workspace_lead_snapshot']         = 'Lead Snapshot';
$lang['followup_workspace_reminder_time']         = 'Reminder Time';
$lang['followup_workspace_lost_reason']           = 'Lost Reason';
$lang['followup_workspace_action_save']           = 'Save';
$lang['followup_workspace_copied']                = 'Copied to clipboard';
$lang['followup_workspace_next_date_required']    = 'Next Follow-up Date is required for this status.';
$lang['followup_workspace_lost_reason_required']  = 'Lost Reason is required to mark this lead Lost.';
$lang['followup_dashboard_telecaller_hint']       = 'Your leads, follow-ups, and outcomes at a glance.';
$lang['followup_dashboard_my_assigned_leads']     = 'My Assigned Leads';
$lang['followup_dashboard_customer_confirmed']    = 'Customer Confirmed';
$lang['followup_dashboard_lost_leads']            = 'Lost Leads';

// Telecaller Portal enhancements: Calling Summary widget + auto-forward
$lang['followup_calling_summary_title']           = "Today's Calling Summary";
$lang['followup_calling_summary_completed']       = 'Completed Calls';
$lang['followup_calling_summary_pending']         = 'Pending Calls';
$lang['followup_calling_summary_tomorrow']        = "Tomorrow's Calls";
$lang['followup_calling_summary_overdue']         = 'Overdue Calls';
$lang['followup_calling_summary_completion']      = 'Completion Rate';
$lang['followup_calling_summary_view_details']    = 'View Details';
$lang['followup_calling_summary_show_less']       = 'Show Less';
$lang['followup_calling_summary_no_records']      = 'No records.';
$lang['followup_history_auto_forwarded']          = 'Auto-forwarded to tomorrow';

// Step 4 - Telecaller Lead page
$lang['telecaller_no_calls_logged']         = 'No calls logged yet.';
$lang['telecaller_followup_history']        = 'Follow-up History';
$lang['telecaller_no_followup_history']     = 'No follow-up history yet.';
$lang['telecaller_actions']                 = 'Actions';
$lang['telecaller_add_call']                = 'Add Call';
$lang['telecaller_continue_followup']       = 'Continue Follow-up';
$lang['telecaller_customer_ready']          = 'Customer Ready';
$lang['telecaller_needs_another_visit']     = 'Needs Another Visit';
$lang['telecaller_not_interested']          = 'Not Interested';
$lang['telecaller_call_date']               = 'Call Date';
$lang['telecaller_call_time']               = 'Call Time';
$lang['telecaller_call_outcome']            = 'Call Outcome';
$lang['telecaller_customer_response']       = 'Customer Response';
$lang['telecaller_next_followup']           = 'Next Follow-up';
$lang['telecaller_confirm_customer_ready']  = 'Convert this lead to a customer now?';
$lang['telecaller_confirm_needs_another_visit'] = 'Send this lead back to the Field Executive for another visit?';
$lang['telecaller_dashboard_todays_calls']          = "Today's Calls";
$lang['telecaller_dashboard_ready_for_conversion']  = 'Ready for Conversion';
$lang['telecaller_dashboard_converted_customers']   = 'Converted Customers';
$lang['telecaller_dashboard_recent_calls']          = 'Recent Calls';
$lang['telecaller_lead_page']                       = 'Lead Page';
$lang['not_lead_lost_admin_notification']           = 'Lead "%s" was marked Lost by %s';

// Phase 5 Telecaller Portal enhancements
$lang['followup_customers']                    = 'Customers';
$lang['followup_customers_hint']               = 'Leads you have followed up that were converted into customers.';
$lang['followup_convert_to_customer']          = 'Convert to Customer';
$lang['telecaller_confirm_convert_customer']   = 'Convert this lead to a customer?';
$lang['field_portal_keep_current_status']      = 'Keep Current Status';
$lang['followup_last_call']                    = 'Last Call';
$lang['followup_next_follow_up_date']          = 'Next Follow-up';
$lang['followup_filter_today']                 = "Today's Follow-ups";
$lang['followup_filter_tomorrow']              = "Tomorrow's Follow-ups";
$lang['followup_filter_upcoming']              = 'Upcoming Follow-ups';
$lang['followup_filter_overdue']               = 'Overdue Follow-ups';
$lang['followup_filter_completed']             = 'Completed Follow-ups';
$lang['followup_todays_work_update']           = "Today's Work Update";
$lang['followup_calls_made_today']             = 'Calls Made Today';
$lang['followup_followups_done_today']         = 'Follow-ups Done Today';
$lang['followup_attendance']                   = 'Attendance';
$lang['followup_present_today']                = 'Present Today';
$lang['followup_login_time']                   = 'Login Time';

// Reused from field_portal - duplicated here so this module is self-contained
$lang['field_portal_filter_all_statuses']        = 'All Statuses';
$lang['field_portal_filter_select_date']         = 'Select Date';
$lang['field_portal_column_lead']                = 'Lead';
$lang['field_portal_column_company']             = 'Company';
$lang['field_portal_column_phone']               = 'Phone';
$lang['field_portal_column_status']              = 'Status';
$lang['field_portal_column_priority']            = 'Priority';
$lang['field_portal_column_next_followup']       = 'Next Follow-up';
$lang['field_portal_column_field_executive']     = 'Field Executive';
$lang['field_portal_column_latest_note']         = 'Latest Note';
$lang['field_portal_column_actions']             = 'Actions';
$lang['field_portal_column_customer']            = 'Customer';
$lang['field_portal_column_created_from_lead']   = 'From Lead';
$lang['field_portal_column_converted_date']      = 'Converted Date';
$lang['field_portal_column_current_status']      = 'Current Status';
$lang['field_portal_column_telecaller']          = 'Telecaller';
$lang['field_portal_view_details']               = 'View Details';
$lang['field_portal_converted']                  = 'Converted';
$lang['field_portal_field_phone']                = 'Phone';
$lang['field_portal_field_email']                = 'Email';
$lang['field_portal_field_address']              = 'Address';
$lang['field_portal_field_requirements']         = 'Requirements';
$lang['field_portal_field_discussion_summary']   = 'Discussion Summary';
$lang['field_portal_field_remarks']              = 'Remarks';
$lang['field_portal_field_attachments']          = 'Attachments';
$lang['field_portal_lost_reason']                = 'Lost Reason';
$lang['field_portal_convert_success']            = 'Customer conversion successful.';
$lang['field_portal_already_converted']          = 'This lead is already a customer.';
$lang['field_portal_convert_failed']             = 'Failed to convert to customer.';
$lang['field_portal_keep_current_status']        = 'Keep Current Status';
$lang['field_portal_meeting_time_placeholder']   = 'e.g. 10:30 AM';
$lang['field_portal_timeline']                   = 'Timeline';
$lang['field_portal_no_activity']                = 'No activity yet.';
$lang['field_portal_timeline_note_added']        = 'Note added';
$lang['field_portal_followup_history_entry']     = '%s - %s';
$lang['field_portal_meetings']                   = 'Meetings';
$lang['field_portal_log_meeting']                = 'Log Meeting';
$lang['field_portal_no_meetings']                = 'No meetings scheduled.';
$lang['field_portal_meeting_notes_attachments']  = 'Notes & Attachments';
$lang['field_portal_notes']                      = 'Notes';
$lang['field_portal_attachments']                = 'Attachments';
$lang['field_portal_add_note_placeholder']       = 'Add a note...';
$lang['field_portal_lead_overview']              = 'Lead Overview';
$lang['field_portal_original_field_executive']   = 'Original Field Executive';
$lang['field_portal_current_handler']            = 'Current Handler';
$lang['field_portal_lead_age']                   = 'Lead Age';
$lang['field_portal_days']                       = 'days';
$lang['field_portal_total_meetings']             = 'Total Meetings';
$lang['field_portal_total_calls']                = 'Total Calls';
$lang['field_portal_total_notes']                = 'Total Notes';
$lang['field_portal_total_attachments']          = 'Total Attachments';
$lang['field_portal_field_next_followup']        = 'Next Follow-up';
$lang['field_portal_column_last_activity']       = 'Last Activity';
$lang['field_portal_converted_by']               = 'Converted By';
$lang['field_portal_lead_progress']              = 'Lead Progress';
$lang['field_portal_customer_summary']           = 'Customer Summary';
$lang['field_portal_field_company']              = 'Company';
$lang['field_portal_managed_by_admin']           = 'Managed By';
$lang['field_portal_followup_history']           = 'Follow-up History';
$lang['field_portal_no_followup_history']        = 'No follow-up history yet.';
$lang['field_portal_column_date']                = 'Date';
$lang['field_portal_column_outcome']             = 'Outcome';
$lang['field_portal_column_customer_response']   = 'Customer Response';
$lang['field_portal_column_discussion_summary']  = 'Discussion Summary';
$lang['field_portal_column_remarks']             = 'Remarks';

// Phase 6 - Admin Sales CRM
$lang['followup_admin_sales_dashboard']        = 'Admin Sales Dashboard';
$lang['followup_sales_pipeline']               = 'Sales Pipeline';
$lang['followup_employee_performance']         = 'Employee Performance';
$lang['followup_lead_reports']                 = 'Lead Reports';
$lang['followup_total_leads']                  = 'Total Leads';
$lang['followup_new_leads_today']              = 'New Leads Today';
$lang['followup_leads_with_fe']                = 'Leads with Field Executive';
$lang['followup_leads_with_tc']                = 'Leads with Telecaller';
$lang['followup_todays_followups']             = "Today's Follow-ups";
$lang['followup_converted_customers']          = 'Converted Customers';
$lang['followup_lost_leads']                   = 'Lost Leads';
$lang['followup_conversion_rate']              = 'Conversion Rate';
$lang['followup_active_customers']             = 'Active Customers';
$lang['followup_meetings_scheduled_today']     = 'Meetings scheduled today';
$lang['followup_customers_from_leads']         = 'Customers originating from leads';
$lang['followup_refresh']                      = 'Refresh';
$lang['followup_pipeline_hint']                = 'Leads grouped by their current pipeline stage. Click a stage to view filtered leads.';
$lang['followup_pipeline_no_data']             = 'No lead data available yet.';
$lang['followup_pipeline_leads']               = 'leads';
$lang['followup_view_leads']                   = 'View Leads';
$lang['followup_total']                        = 'Total';
$lang['followup_field_executives']             = 'Field Executives';
$lang['followup_telecallers']                  = 'Telecallers';
$lang['followup_staff_name']                   = 'Name';
$lang['followup_leads_created']                = 'Leads Created';
$lang['followup_meetings']                     = 'Meetings';
$lang['followup_assigned_to_tc']               = 'Assigned to TC';
$lang['followup_converted']                    = 'Converted';
$lang['followup_lost']                         = 'Lost';
$lang['followup_conversion_pct']               = 'Conversion %';
$lang['followup_assigned_leads']               = 'Assigned Leads';
$lang['followup_calls_made']                   = 'Calls Made';
$lang['followup_followups_done']               = 'Follow-ups Done';
$lang['followup_avg_followup_time_hours']       = 'Avg Follow-up Time (hrs)';
$lang['followup_no_data']                      = 'No data available.';
$lang['followup_export_csv']                   = 'Export CSV';
$lang['followup_filter_date_from']             = 'From Date';
$lang['followup_filter_date_to']               = 'To Date';
$lang['followup_filter_by_staff']              = 'Filter by Staff';
$lang['followup_filter_by_role']               = 'Filter by Role';
$lang['followup_filter_by_status']             = 'Filter by Status';
$lang['followup_filter_by_source']             = 'Filter by Source';
$lang['followup_filter_status']                = 'Lead Status';
$lang['followup_filter_converted']             = 'Converted';
$lang['followup_filter_lost']                  = 'Lost';
$lang['followup_filter_all_roles']             = 'All Roles';
$lang['followup_apply_filters']                = 'Apply Filters';
$lang['followup_clear_filters']                = 'Clear';
$lang['followup_lead_name']                    = 'Lead Name';
$lang['followup_company']                      = 'Company';
$lang['followup_phone']                        = 'Phone';
$lang['followup_email']                        = 'Email';
$lang['followup_date_added']                   = 'Date Added';
$lang['followup_status']                       = 'Status';
$lang['followup_source']                       = 'Source';
$lang['followup_field_executive']              = 'Field Executive';
$lang['followup_telecaller']                   = 'Telecaller';
$lang['followup_converted_date']               = 'Converted Date';
$lang['followup_lost_reason']                  = 'Lost Reason';
$lang['followup_yes']                          = 'Yes';
$lang['followup_customer_origin']              = 'Origin';
$lang['followup_no_origin_data']               = 'This customer was not created from a lead conversion.';
$lang['followup_original_lead']                = 'Original Lead';
$lang['followup_lead_company']                 = 'Lead Company';
$lang['followup_lead_status']                  = 'Lead Status';
$lang['followup_lead_source']                  = 'Lead Source';
$lang['followup_conversion_date']              = 'Conversion Date';
$lang['followup_converted_by']                 = 'Converted By';
$lang['followup_original_field_executive']     = 'Field Executive';
$lang['followup_lead_phone']                   = 'Lead Phone';
$lang['followup_lead_email']                   = 'Lead Email';
$lang['followup_lead_added_date']              = 'Lead Added Date';

// Phase 6 - Admin Notifications
$lang['not_admin_lead_converted']              = 'Lead "%s" was converted to a Customer by %s';
$lang['not_admin_high_priority_lead']          = 'High Priority Alert: Lead "%s" was set to High Priority by %s';
