<?php

defined('BASEPATH') or exit('No direct script access allowed');

$lang['dev_portal']            = 'Development Portal';
$lang['dev_portal_dashboard']  = 'Dashboard';
$lang['dev_portal_my_projects'] = 'My Projects';
$lang['dev_portal_my_tasks']   = 'My Tasks';
$lang['dev_portal_daily_work_update'] = 'Daily Work Update';
$lang['dev_portal_attendance'] = 'Attendance';
$lang['dev_portal_calendar']   = 'Calendar';
$lang['dev_portal_support']    = 'Support';
$lang['dev_portal_profile']    = 'Profile';

$lang['dev_portal_dashboard_hint']               = 'Your projects, tasks and today\'s activity across Web Development, App Development, UI/UX Design and Testing.';

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
$lang['dev_portal_dashboard_todays_tasks']       = "Today's Tasks";
$lang['dev_portal_dashboard_assigned_projects']  = 'Assigned Projects';
$lang['dev_portal_dashboard_completed_tasks']    = 'Completed Tasks';
$lang['dev_portal_dashboard_attendance_summary'] = 'Attendance Summary';
$lang['dev_portal_dashboard_no_attendance_today'] = 'No attendance record for today yet.';
$lang['dev_portal_dashboard_upcoming_deadlines'] = 'Upcoming Deadlines';
$lang['dev_portal_dashboard_no_deadlines']       = 'No deadlines in the next 7 days.';

$lang['dev_portal_column_project_name']     = 'Project Name';
$lang['dev_portal_column_client']           = 'Client';
$lang['dev_portal_column_project_type']     = 'Project Type';
$lang['dev_portal_column_assigned_date']    = 'Assigned Date';
$lang['dev_portal_column_due_date']         = 'Due Date';
$lang['dev_portal_column_status']           = 'Status';
$lang['dev_portal_column_progress']         = 'Progress';
$lang['dev_portal_column_priority']         = 'Priority';
$lang['dev_portal_column_note']             = 'Note';
$lang['dev_portal_column_task']             = 'Task';
$lang['dev_portal_column_project']          = 'Project';
$lang['dev_portal_column_estimated_hours']  = 'Estimated Hours';
$lang['dev_portal_column_hours_worked']     = 'Hours Worked';
$lang['dev_portal_column_actions']          = 'Actions';
$lang['dev_portal_column_assigned_team']    = 'Assigned Team';
$lang['dev_portal_open']                   = 'Open';

$lang['dev_portal_filter_today']     = "Showing: Today's Tasks";
$lang['dev_portal_filter_completed'] = 'Showing: Completed Tasks';
$lang['dev_portal_filter_clear']     = 'Clear filter';

$lang['dev_portal_workspace_start_date']         = 'Start Date';
$lang['dev_portal_workspace_department']         = 'Assigned Department';
$lang['dev_portal_workspace_assigned_employee']  = 'Assigned Employee';
$lang['dev_portal_workspace_assigned_work']      = 'Assigned Work';
$lang['dev_portal_workspace_progress']           = 'Progress';
$lang['dev_portal_workspace_update_progress']    = 'Update Progress';
$lang['dev_portal_workspace_change_status']      = 'Change Status';
$lang['dev_portal_workspace_cancelled_readonly'] = 'This project was cancelled by Admin and cannot be reopened here.';
$lang['dev_portal_workspace_work_updates']       = 'Daily Work Updates';
$lang['dev_portal_workspace_work_performed']     = 'Work Performed';
$lang['dev_portal_workspace_hours_worked']       = 'Hours Worked';
$lang['dev_portal_workspace_remarks']            = 'Remarks';
$lang['dev_portal_workspace_no_work_updates']    = 'No work updates logged yet.';
$lang['dev_portal_workspace_files']              = 'Files';
$lang['dev_portal_workspace_upload']             = 'Upload';
$lang['dev_portal_workspace_no_files']           = 'No files uploaded yet.';
$lang['dev_portal_workspace_comments']           = 'Comments';
$lang['dev_portal_workspace_add_comment']        = 'Add a comment...';
$lang['dev_portal_workspace_no_comments']        = 'No comments yet.';
$lang['dev_portal_workspace_notes']              = 'Notes';
$lang['dev_portal_workspace_latest_note']        = 'Latest Note';
$lang['dev_portal_workspace_note_placeholder']   = 'Add a note...';
$lang['dev_portal_workspace_add_note']           = 'Add Note';
$lang['dev_portal_workspace_note_history']       = 'Note History';
$lang['dev_portal_workspace_no_notes']           = 'No notes yet.';
$lang['dev_portal_workspace_note_by']            = 'by %s';
$lang['dev_portal_note_enter_note']              = 'Please enter a note.';
$lang['dev_portal_workspace_project_information'] = 'Project Information';
$lang['dev_portal_workspace_save_changes']       = 'Save Changes';
$lang['dev_portal_workspace_saving']             = 'Saving...';
$lang['dev_portal_workspace_save_success']       = 'Changes saved successfully.';
$lang['dev_portal_workspace_nothing_to_save']    = 'Nothing to save - change Progress, Status, or add a Note first.';

$lang['dev_portal_status_pending']     = 'Pending';
$lang['dev_portal_status_in_progress'] = 'In Progress';
$lang['dev_portal_status_on_hold']     = 'On Hold';
$lang['dev_portal_status_completed']   = 'Completed';
$lang['dev_portal_status_cancelled']   = 'Cancelled';

$lang['dev_portal_invalid_progress']         = 'Please provide a valid progress value.';
$lang['dev_portal_invalid_status']           = 'That status is not available in the Development Portal.';
$lang['dev_portal_worklog_missing_fields']   = 'Please provide a date and a description of the work performed.';
$lang['dev_portal_comment_missing_content']  = 'Please enter a comment.';
