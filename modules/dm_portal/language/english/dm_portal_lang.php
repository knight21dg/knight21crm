<?php

defined('BASEPATH') or exit('No direct script access allowed');

$lang['dm_portal']            = 'Digital Marketing Portal';
$lang['dm_portal_dashboard']  = 'Dashboard';
$lang['dm_portal_my_work']    = 'My Work';
$lang['dm_portal_daily_update'] = 'Daily Work Update';
$lang['dm_portal_attendance'] = 'Attendance';
$lang['dm_portal_profile']    = 'Profile';

$lang['dm_portal_dashboard_hint']              = 'Your projects, tasks and today\'s activity in the Digital Marketing team.';

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
$lang['dm_portal_dashboard_assigned_projects'] = 'Assigned Projects';
$lang['dm_portal_dashboard_pending_tasks']     = 'Pending Tasks';
$lang['dm_portal_dashboard_completed_tasks']   = 'Completed Tasks';
$lang['dm_portal_dashboard_todays_tasks']      = "Today's Tasks";

$lang['dm_portal_dashboard_today_queue']       = "Today's Work Queue";
$lang['dm_portal_dashboard_no_today_tasks']    = 'No tasks due today.';
$lang['dm_portal_dashboard_upcoming_deadlines'] = 'Upcoming Deadlines';
$lang['dm_portal_dashboard_no_deadlines']      = 'No deadlines in the next 7 days.';
$lang['dm_portal_dashboard_recent_updates']    = 'Recent Daily Work Updates';
$lang['dm_portal_dashboard_no_recent_updates'] = 'No work updates submitted yet.';

$lang['dm_portal_column_project']        = 'Project';
$lang['dm_portal_column_customer']       = 'Customer';
$lang['dm_portal_column_task']           = 'Task';
$lang['dm_portal_column_priority']       = 'Priority';
$lang['dm_portal_column_deadline']       = 'Deadline';
$lang['dm_portal_column_status']         = 'Status';
$lang['dm_portal_column_progress']       = 'Progress';
$lang['dm_portal_column_actions']        = 'Actions';

$lang['dm_portal_edit_progress']         = 'Edit Progress';

$lang['dm_portal_filter_all_projects']   = 'All Projects';
$lang['dm_portal_filter_all_statuses']   = 'All Statuses';
$lang['dm_portal_filter_all_priorities'] = 'All Priorities';

$lang['dm_portal_action_view_details']   = 'View Details';
$lang['dm_portal_action_mark_complete']  = 'Mark Complete';
$lang['dm_portal_action_add_notes']      = 'Add Notes';

$lang['dm_portal_invalid_status']        = 'That status is not available here.';
$lang['dm_portal_note_missing_content']  = 'Please enter a note.';
$lang['dm_portal_status_updated']        = 'Status updated.';
$lang['dm_portal_task_completed']        = 'Task marked as complete.';
$lang['dm_portal_note_added']            = 'Note added.';
$lang['dm_portal_file_uploaded']         = 'File uploaded successfully.';

$lang['dm_portal_modal_project_info']    = 'Project Information';
$lang['dm_portal_modal_assigned_work']   = 'Assigned Work';
$lang['dm_portal_modal_task_details']    = 'Task Details';
$lang['dm_portal_modal_files']           = 'Files';
$lang['dm_portal_modal_comments']        = 'Comments';
$lang['dm_portal_modal_not_linked']      = 'This task is not linked to a project.';
$lang['dm_portal_modal_no_assigned_work'] = 'No assigned work brief for this project.';
$lang['dm_portal_modal_no_files']        = 'No files uploaded yet.';
$lang['dm_portal_modal_no_comments']     = 'No notes yet.';
$lang['dm_portal_modal_add_note_placeholder'] = 'Add a note...';
$lang['dm_portal_modal_upload']          = 'Upload';
$lang['dm_portal_modal_customer']        = 'Customer';
$lang['dm_portal_modal_deadline']        = 'Deadline';
$lang['dm_portal_modal_project_status']  = 'Status';
$lang['dm_portal_modal_description']     = 'Description';
$lang['dm_portal_modal_estimated_hours'] = 'Estimated Hours';

// Daily Work Update strings moved to modules/daily_work_update/language/

/* Dashboard card modals */
$lang['dm_portal_modal_my_assigned_projects']  = 'My Assigned Projects';
$lang['dm_portal_modal_my_pending_tasks']      = 'My Pending Tasks';
$lang['dm_portal_modal_my_completed_tasks']    = 'My Completed Tasks';
$lang['dm_portal_modal_todays_tasks']          = "Today's Tasks";

$lang['dm_portal_col_assigned_date']    = 'Assigned Date';
$lang['dm_portal_col_completed_date']   = 'Completed Date';
$lang['dm_portal_col_final_progress']   = 'Final Progress';
$lang['dm_portal_col_notes']            = 'Notes';
$lang['dm_portal_col_due_date']         = 'Due Date';

$lang['dm_portal_no_projects']          = 'No assigned projects.';
$lang['dm_portal_no_pending_tasks']     = 'No pending tasks.';
$lang['dm_portal_no_completed_tasks']   = 'No completed tasks yet.';
$lang['dm_portal_no_notes']             = 'No Notes';
$lang['dm_portal_no_team_members']      = 'No team members.';
$lang['dm_portal_no_current_tasks']     = 'No current tasks in this project.';
$lang['dm_portal_team_members']         = 'Team Members';
$lang['dm_portal_current_tasks']        = 'Current Tasks';

$lang['dm_portal_back_to_list']         = 'Back';
