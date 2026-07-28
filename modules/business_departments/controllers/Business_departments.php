<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Business_departments extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('business_departments_model');
    }

    public function index()
    {
        if (staff_cant('view', 'business_departments')) {
            access_denied('business_departments');
        }

        $data['departments']  = $this->business_departments_model->get();
        $data['staff_counts'] = $this->business_departments_model->get_staff_counts();
        $data['title']        = _l('business_departments');
        $this->load->view('manage', $data);
    }

    public function department($id = '')
    {
        if (staff_cant('view', 'business_departments')) {
            access_denied('business_departments');
        }

        if ($this->input->post()) {
            $data = $this->input->post();

            if ($id == '') {
                if (staff_cant('create', 'business_departments')) {
                    access_denied('business_departments');
                }
                $id = $this->business_departments_model->add($data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('business_department')));
                    redirect(admin_url('business_departments/department/' . $id));
                }
            } else {
                if (staff_cant('edit', 'business_departments')) {
                    access_denied('business_departments');
                }
                $success = $this->business_departments_model->update($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('business_department')));
                }
                redirect(admin_url('business_departments/department/' . $id));
            }
        }

        $data['staff'] = $this->staff_model->get('', ['active' => 1]);

        if ($id == '') {
            $data['title'] = _l('new_business_department');
        } else {
            $data['department'] = $this->business_departments_model->get($id);
            if (!$data['department']) {
                show_404();
            }
            $data['selected_staff_ids'] = $this->business_departments_model->get_department_staff_ids($id);
            $data['title']              = _l('edit_business_department');
        }

        $this->load->view('department', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'business_departments')) {
            access_denied('business_departments');
        }

        if (!$id) {
            redirect(admin_url('business_departments'));
        }

        $response = $this->business_departments_model->delete($id);

        if ($response) {
            set_alert('success', _l('deleted', _l('business_department')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('business_department_lowercase')));
        }

        redirect(admin_url('business_departments'));
    }
}
