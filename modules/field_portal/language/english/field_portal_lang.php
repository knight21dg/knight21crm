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
$lang['staff_dashboard_view_all']           = 'View All';

$lang['field_portal']           = 'Field Executive Portal';
$lang['field_portal_dashboard'] = 'Dashboard';
$lang['field_portal_my_leads']          = 'My Leads';
$lang['field_portal_create_lead']       = 'Create Lead';
$lang['field_portal_meetings']          = 'Meetings';
$lang['field_portal_followups']         = 'Follow-ups';
$lang['field_portal_customers']         = 'Customers';
$lang['field_portal_daily_work_update'] = 'Daily Work Update';
$lang['field_portal_attendance']        = 'Attendance';
$lang['field_portal_profile']           = 'Profile';

$lang['field_portal_card_todays_leads']            = "Today's Leads";
$lang['field_portal_card_total_leads']             = 'Total Leads';
$lang['field_portal_card_todays_meetings']        = "Today's Meetings";
$lang['field_portal_card_pending_followups']      = 'Pending Follow-ups';
$lang['field_portal_card_assigned_to_telecaller'] = 'Assigned to Telecaller';
$lang['field_portal_card_converted_customers']    = 'Converted Customers';
$lang['field_portal_card_attendance']             = 'Attendance';
$lang['field_portal_card_work_update']            = "Today's Work Update";

$lang['field_portal_not_marked']            = 'Not Marked';
$lang['field_portal_submitted']             = 'Submitted';
$lang['field_portal_pending']               = 'Pending';
$lang['field_portal_recent_conversions']    = 'Recent Customer Conversions';
$lang['field_portal_no_recent_conversions'] = 'No customer conversions yet.';
$lang['field_portal_followups_coming_soon'] = 'Telecaller Assignment and follow-up scheduling arrive in Step 4 of this portal\'s build.';

$lang['field_portal_todays_new_leads']       = "Today's New Leads";
$lang['field_portal_no_todays_new_leads']    = 'No leads created today yet.';
$lang['field_portal_upcoming_followups']     = 'Upcoming Follow-ups';
$lang['field_portal_no_upcoming_followups']  = 'No upcoming follow-ups scheduled.';
$lang['field_portal_recent_lead_activity']   = 'Recent Lead Activity';
$lang['field_portal_no_recent_activity']     = 'No recent activity yet.';
$lang['field_portal_quick_actions']          = 'Quick Actions';

// My Leads
$lang['field_portal_my_leads_hint']              = "Leads you've created or that are assigned to you.";
$lang['field_portal_filter_all_statuses']        = 'All Statuses';
$lang['field_portal_column_lead']                = 'Lead';
$lang['field_portal_column_company']             = 'Company';
$lang['field_portal_column_phone']               = 'Phone';
$lang['field_portal_column_status']              = 'Status';
$lang['field_portal_column_priority']            = 'Priority';
$lang['field_portal_column_meeting_date']        = 'Meeting Date';
$lang['field_portal_column_next_followup']       = 'Next Follow-up';
$lang['field_portal_column_assigned_telecaller'] = 'Assigned Telecaller';
$lang['field_portal_column_actions']             = 'Actions';
$lang['field_portal_view_details']               = 'View Details';

// Create Lead
$lang['field_portal_create_lead_hint']       = 'Quickly log a new business prospect from the field.';
$lang['field_portal_field_company']          = 'Company Name';
$lang['field_portal_field_contact_person']   = 'Contact Person';
$lang['field_portal_field_phone']            = 'Phone Number';
$lang['field_portal_field_alt_phone']        = 'Alternate Phone';
$lang['field_portal_field_email']            = 'Email';
$lang['field_portal_field_address']          = 'Address';
$lang['field_portal_field_maps_location']    = 'Google Maps Location';
$lang['field_portal_field_source']           = 'Lead Source';
$lang['field_portal_field_business_category'] = 'Business Category / Service Required';
$lang['field_portal_field_requirements']     = 'Requirements';
$lang['field_portal_field_priority']         = 'Priority';
$lang['field_portal_field_budget']           = 'Expected Budget';
$lang['field_portal_field_remarks']          = 'Remarks';
$lang['field_portal_field_attachments']      = 'Attachments';
$lang['field_portal_field_next_followup']    = 'Next Follow-up Date';
$lang['field_portal_save_lead']              = 'Save Lead';
$lang['field_portal_lead_name_required']     = 'Contact Person is required.';
$lang['field_portal_lead_create_failed']     = 'Could not create the lead. Please try again.';

// Lead Details
$lang['field_portal_converted']              = 'Converted';
$lang['field_portal_view_customer']          = 'View Customer';
$lang['field_portal_convert_to_customer']    = 'Convert to Customer';
$lang['field_portal_confirm_convert']        = 'Convert this lead to a customer? This cannot be undone.';
$lang['field_portal_convert_success']        = 'Lead converted to customer successfully.';
$lang['field_portal_already_converted']      = 'This lead has already been converted.';
$lang['field_portal_convert_failed']         = 'Could not convert this lead. Please try again.';
$lang['field_portal_next_followup_scheduled'] = 'Next follow-up scheduled for';

// Step 4 - Telecaller Integration
$lang['field_portal_with_telecaller']        = 'With Telecaller: %s';
$lang['field_portal_assign_to_telecaller']   = 'Assign to Telecaller';
$lang['field_portal_telecaller']             = 'Telecaller';
$lang['field_portal_telecaller_required']    = 'Please select a Telecaller.';
$lang['field_portal_priority']               = 'Priority';
$lang['field_portal_assign_reason']          = 'Reason';
$lang['field_portal_already_with_telecaller'] = 'This lead is already with a Telecaller.';
$lang['field_portal_mark_lost']              = 'Mark Lost';
$lang['field_portal_lost_reason_required']   = 'A reason is required to mark this lead as Lost.';
$lang['not_lead_assigned_to_telecaller']     = 'assigned this lead to Telecaller %s';
$lang['not_lead_marked_lost']                = 'marked this lead as Lost: %s';
$lang['not_lead_returned_to_field_executive'] = 'Telecaller %s returned this lead to you for another visit';
$lang['not_lead_converted_admin_notification'] = 'Lead "%s" was converted to a customer by %s';
$lang['field_portal_notes']                  = 'Notes';
$lang['field_portal_add_note_placeholder']   = 'Add a note about this lead...';
$lang['field_portal_no_notes']               = 'No notes yet.';
$lang['field_portal_note_required']          = 'Note cannot be empty.';
$lang['field_portal_confirm_delete_note']    = 'Are you sure you want to delete this note?';
$lang['field_portal_attachments']            = 'Attachments';
$lang['field_portal_upload']                 = 'Upload';
$lang['field_portal_no_attachments']         = 'No attachments yet.';
$lang['field_portal_confirm_delete_attachment'] = 'Are you sure you want to remove this attachment?';
$lang['field_portal_timeline']                = 'Timeline';
$lang['field_portal_no_activity']            = 'No activity yet.';
$lang['field_portal_timeline_note_added']    = 'added a note';

// Lead Overview (Step 4, shared with the Telecaller Lead page)
$lang['field_portal_lead_overview']              = 'Lead Overview';
$lang['field_portal_original_field_executive']   = 'Original Field Executive';
$lang['field_portal_current_handler']            = 'Current Handler';
$lang['field_portal_total_meetings']             = 'Total Meetings';
$lang['field_portal_total_calls']                = 'Total Calls';
$lang['field_portal_lead_age']                   = 'Lead Age';
$lang['field_portal_days']                       = 'days';
$lang['field_portal_converted_by']               = 'Converted By';
$lang['field_portal_lost_reason']                = 'Lost Reason';

// Meetings (Step 3)
$lang['field_portal_meetings_hint']              = 'Meetings logged across all of your leads.';
$lang['field_portal_no_meetings']                = 'No meetings logged yet.';
$lang['field_portal_log_meeting']                = 'Log Meeting';
$lang['field_portal_save_meeting']               = 'Save Meeting';
$lang['field_portal_meeting_date']               = 'Meeting Date';
$lang['field_portal_meeting_date_required']      = 'Meeting Date is required.';
$lang['field_portal_meeting_time']               = 'Meeting Time';
$lang['field_portal_meeting_time_placeholder']   = 'e.g. 3:30 PM';
$lang['field_portal_meeting_type']               = 'Meeting Type';
$lang['field_portal_meeting_task_name']          = 'Meeting';
$lang['field_portal_meeting_create_failed']      = 'Could not log this meeting. Please try again.';
$lang['field_portal_meeting_notes_attachments']  = 'Notes & Attachments';
$lang['field_portal_meeting_status_updated']     = 'Meeting status updated.';
$lang['field_portal_field_discussion_summary']   = 'Discussion Summary';
$lang['field_portal_field_products_discussed']   = 'Products Discussed';
$lang['field_portal_field_meeting_budget']       = 'Budget';
$lang['field_portal_field_competitor_info']      = 'Competitor Info';
$lang['field_portal_field_next_action']          = 'Next Action';
$lang['field_portal_card_overdue_meetings']      = 'Overdue Meetings';
$lang['field_portal_card_tomorrows_meetings']    = "Tomorrow's Meetings";
$lang['field_portal_no_todays_meetings']         = 'No meetings scheduled for today.';
$lang['field_portal_no_tomorrows_meetings']      = 'No meetings scheduled for tomorrow.';
$lang['field_portal_no_overdue_meetings']        = 'No overdue meetings.';

// Customers
$lang['field_portal_customers_hint']        = "Customers converted from your leads.";
$lang['field_portal_no_customers']          = 'No customers converted yet.';
$lang['field_portal_column_customer']       = 'Customer';
$lang['field_portal_column_address']        = 'Address';
$lang['field_portal_column_converted_date'] = 'Converted Date';
$lang['field_portal_column_converted_by']   = 'Converted By';
$lang['field_portal_column_projects']       = 'Projects';

// Customers - Conversion Report (KPI cards, date filter, live count)
$lang['field_portal_customers_kpi_total']      = 'Total Converted Customers';
$lang['field_portal_customers_kpi_today']      = "Today's Customers";
$lang['field_portal_customers_kpi_this_month'] = 'This Month';
$lang['field_portal_customers_kpi_this_year']  = 'This Year';
$lang['field_portal_customers_date_from']      = 'From Date';
$lang['field_portal_customers_date_to']        = 'To Date';
$lang['field_portal_customers_custom_range']   = 'Custom Range';
$lang['field_portal_customers_showing']        = 'Showing __COUNT__ Customers';
$lang['field_portal_customers_showing_of']     = 'Showing __SHOWN__ of __TOTAL__ Customers';

// Dashboard navigation / drill-down pages
$lang['field_portal_refresh']                    = 'Refresh';
$lang['field_portal_breadcrumb_dashboard']       = 'Dashboard';
$lang['field_portal_filter_select_date']          = 'Select Date';
$lang['field_portal_filter_all_time']            = 'All Time';
$lang['field_portal_filter_today']               = 'Today';
$lang['field_portal_filter_when']                = 'When';
$lang['field_portal_filter_tomorrow']             = 'Tomorrow';
$lang['field_portal_filter_overdue']              = 'Overdue';
$lang['field_portal_column_location']            = 'Location';
$lang['field_portal_filter_converted']            = 'Converted';
$lang['field_portal_column_created_time']        = 'Created Time';
$lang['field_portal_column_telecaller']          = 'Telecaller';
$lang['field_portal_column_assigned_date']       = 'Assigned Date';
$lang['field_portal_column_current_status']      = 'Current Status';
$lang['field_portal_column_last_activity']       = 'Last Activity';

$lang['field_portal_all_followups']              = 'All Follow-ups';
$lang['field_portal_all_followups_hint']         = 'Every follow-up across your leads - use the tabs to narrow the list.';
$lang['field_portal_no_followups']               = 'No follow-ups found for this filter.';
$lang['field_portal_filter_tab_all']             = 'All';
$lang['field_portal_filter_tab_pending']         = 'Pending';
$lang['field_portal_filter_tab_upcoming']        = 'Upcoming';
$lang['field_portal_filter_tab_completed']       = 'Completed';

$lang['field_portal_assigned_to_telecaller']         = 'Assigned to Telecaller';
$lang['field_portal_assigned_to_telecaller_hint']    = 'Your leads currently being worked by a Telecaller.';
$lang['field_portal_no_assigned_to_telecaller']      = 'No leads currently assigned to a Telecaller.';

$lang['field_portal_edit']                    = 'Edit';
$lang['field_portal_edit_lead']               = 'Edit Lead';
$lang['field_portal_edit_lead_hint']          = 'Update the lead details below.';
$lang['field_portal_save_changes']            = 'Save Changes';
$lang['field_portal_cancel']                  = 'Cancel';
$lang['field_portal_back_to_lead']            = 'Back to Lead';
$lang['field_portal_lead_updated']            = 'Lead updated successfully.';
$lang['field_portal_lead_edit_failed']        = 'Could not update the lead. Please try again.';
$lang['field_portal_locked_assigned']         = 'Some fields are locked because this lead has been assigned to a Telecaller.';
$lang['field_portal_locked_converted']        = 'Some fields are locked because this lead has been converted to a customer.';

$lang['field_portal_lead_activity']           = 'Lead Activity';
$lang['field_portal_lead_activity_hint']      = 'Full activity timeline across all of your leads.';
$lang['field_portal_column_latest_note']        = 'Latest Note';
$lang['field_portal_column_created_from_lead']   = 'Created From Lead';
$lang['field_portal_total_notes']                = 'Total Notes';
$lang['field_portal_total_attachments']          = 'Total Attachments';
$lang['field_portal_column_date']                = 'Date';
$lang['field_portal_column_outcome']             = 'Outcome';
$lang['field_portal_column_customer_response']   = 'Customer Response';
$lang['field_portal_column_discussion_summary']  = 'Discussion Summary';
$lang['field_portal_column_remarks']             = 'Remarks';
$lang['field_portal_followup_history']           = 'Follow-up History';
$lang['field_portal_no_followup_history']        = 'No follow-up history yet.';
$lang['field_portal_followup_history_entry']     = 'Follow-up by %s - %s';
$lang['field_portal_lead_progress']              = 'Lead Progress';
$lang['field_portal_customer_summary']           = 'Customer Summary';
$lang['field_portal_managed_by_admin']           = 'Managed By';

// Daily Expenses
$lang['field_portal_daily_expenses']             = 'Daily Expenses';
$lang['field_portal_expenses_hint']              = 'Record travel, fuel, food, and other expenses incurred during field visits.';
$lang['field_portal_expenses_today']             = "Today's Expenses";
$lang['field_portal_expenses_this_month']        = 'This Month';
$lang['field_portal_expenses_this_year']         = 'This Year';
$lang['field_portal_expenses_total']             = 'Total Expenses';
$lang['field_portal_expenses_all_categories']    = 'All Categories';
$lang['field_portal_expenses_all_months']        = 'All Months';
$lang['field_portal_expenses_all_years']         = 'All Years';
$lang['field_portal_expenses_from_date']         = 'From Date';
$lang['field_portal_expenses_to_date']           = 'To Date';
$lang['field_portal_expenses_add']               = 'Add Expense';
$lang['field_portal_expenses_edit']              = 'Edit Expense';
$lang['field_portal_expenses_view']              = 'View Expense';
$lang['field_portal_expenses_save']              = 'Save Expense';
$lang['field_portal_expenses_update']            = 'Update Expense';
$lang['field_portal_expenses_col_date']          = 'Date';
$lang['field_portal_expenses_col_lead']          = 'Lead Name';
$lang['field_portal_expenses_col_category']      = 'Category';
$lang['field_portal_expenses_col_categories']    = 'Categories';
$lang['field_portal_expenses_col_total_amount']  = 'Total Amount';
$lang['field_portal_expenses_col_description']   = 'Description';
$lang['field_portal_expenses_col_amount']        = 'Amount';
$lang['field_portal_expenses_col_payment']       = 'Payment Method';
$lang['field_portal_expenses_col_receipt']       = 'Receipt';
$lang['field_portal_expenses_view_day']          = 'Daily Expense Details';
$lang['field_portal_expenses_breakdown']         = 'Expense Breakdown';
$lang['field_portal_expenses_select_category']   = 'Select Category';
$lang['field_portal_expenses_select_payment']    = 'Select Payment Method';
$lang['field_portal_expenses_no_lead']           = 'No Lead (Optional)';
$lang['field_portal_expenses_summary']           = 'Expense Summary';
$lang['field_portal_expenses_grand_total']       = 'Grand Total';
$lang['field_portal_expenses_added']             = 'Expense added successfully.';
$lang['field_portal_expenses_add_failed']        = 'Could not add the expense. Please try again.';
$lang['field_portal_expenses_updated']           = 'Expense updated successfully.';
$lang['field_portal_expenses_update_failed']     = 'Could not update the expense. Please try again.';
$lang['field_portal_expenses_deleted']           = 'Expense deleted successfully.';
$lang['field_portal_expenses_not_found']         = 'Expense not found.';
$lang['field_portal_expenses_validation']        = 'Category is required and amount must be greater than zero.';
$lang['field_portal_expenses_delete_confirm']    = 'Are you sure you want to delete this expense?';
$lang['field_portal_expenses_current_receipt']   = 'Current Receipt';
$lang['field_portal_expenses_view_receipt']      = 'View Receipt';
$lang['field_portal_expenses_delete_receipt']    = 'Delete Receipt';
$lang['field_portal_expenses_receipt_deleted']      = 'Receipt deleted successfully.';
$lang['field_portal_expenses_add_day']              = 'Add Daily Expenses';
$lang['field_portal_expenses_edit_day']             = 'Edit Daily Expenses';
$lang['field_portal_expenses_save_day']             = 'Save Daily Expenses';
$lang['field_portal_expenses_update_day']           = 'Update Daily Expenses';
$lang['field_portal_expenses_day_amounts']          = 'Expense Amounts';
$lang['field_portal_expenses_remarks_placeholder']  = 'Any additional notes for the day...';
$lang['field_portal_close']                         = 'Close';
$lang['field_portal_edit_day']                      = 'Edit Day';
$lang['field_portal_expenses_attachments']          = 'Attachments';
$lang['field_portal_expenses_attachments_count']    = 'Attachments (%s)';
$lang['field_portal_expenses_attachments_loading']  = 'Loading attachments...';
$lang['field_portal_expenses_add_attachment']       = 'Add Attachment';
$lang['field_portal_expenses_attachment_file']      = 'File';
$lang['field_portal_expenses_attachment_title']     = 'Title';
$lang['field_portal_expenses_attachment_description'] = 'Description';
$lang['field_portal_expenses_edit_attachment']      = 'Edit Attachment';
$lang['field_portal_expenses_attachment_updated']   = 'Attachment updated successfully.';
$lang['field_portal_expenses_attachment_deleted']   = 'Attachment deleted successfully.';
$lang['field_portal_expenses_col_created']          = 'Upload Date';
$lang['field_portal_no_attachments']                = 'No attachments found.';
$lang['field_portal_confirm_delete']             = 'Confirm Delete';
$lang['field_portal_delete']                     = 'Delete';
