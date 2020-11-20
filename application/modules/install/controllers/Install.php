<?php

Class Install extends CI_Controller {

    function __construct()
    {
        parent::__construct();

        //if this system is already installed redirect to the homepage
        if(file_exists(FCPATH.'application/config/setup.php'))
        {
            redirect('admin/login');
        }

		//Destroy All Session
		$this->session->sess_destroy();
    }

    function index()
    {
        //build our checks
        $data = array();
        //check for writable folders
        $data = $this->validate_writable_folder();

        if($this->validate_form() == FALSE)
        {
            $data['errors'] = validation_errors();
            $this->load->view('install', $data);
        }
        else
        {
            if ($this->try_db_connect())
            {
                //setup the database config file
                // $this->setup_db_config_file();

                //setup the CodeIgniter default config file
                // $this->setup_config_file();
				
                //setup the .htaccess file
                // $this->setup_htaccess();

                // load migration
                $this->load_migration();

                //redirect to the admin login
                redirect('register');
            }
            else
            {
                $data['errors'] = '<p>A connection to the database could not be established.</p>';
                $this->load->view('install', $data);
            }
        }
    }

    // Helpers
    protected function validate_writable_folder()
    {
        $data['is_writeable']['root'] = is_writeable(FCPATH);
        $data['is_writeable']['config'] = is_writeable(FCPATH.'application/config/');
        $data['is_writeable']['uploads'] = is_writeable(FCPATH.'uploads/');
        return $data;
    }

    protected function validate_form()
    {
        $this->form_validation->set_rules('hostname', 'Hostname', 'required');
        $this->form_validation->set_rules('database', 'Database Name', 'required');
        $this->form_validation->set_rules('username', 'Username', 'required');
        $this->form_validation->set_rules('password', 'Password', 'trim');
        $this->form_validation->set_rules('prefix', 'Database Prefix', 'trim');

        $this->form_validation->set_rules('ssl_support');
        $this->form_validation->set_rules('mod_rewrite');
        return $this->form_validation->run();
    }

    protected function try_db_connect()
    {
        // Unset any existing DB information
        unset($this->db);

        //generate a dsn string
        $dsn = 'mysqli://'.$this->input->post('username').':'.
                $this->input->post('password').'@'.
                $this->input->post('hostname').'/'.
                $this->input->post('database');

        //connect!
        $this->load->database($dsn);

        return is_resource($this->db->conn_id) OR is_object($this->db->conn_id);
    }

    protected function setup_db_config_file()
    {
        $settings                   = array();
        $settings['hostname']       = $this->input->post('hostname');
        $settings['username']       = $this->input->post('username');
        $settings['password']       = $this->input->post('password');
        $settings['database']       = $this->input->post('database');
        $settings['prefix']         = $this->input->post('prefix');             
        $file_contents              = $this->load->view('templates/database', $settings, true);
        write_file(FCPATH.'application/config/database.php', $file_contents);
        $setup_file = "//This Is Setup File";
        write_file(FCPATH.'application/config/setup.php',$setup_file);
    }

    protected function setup_config_file()
    {
        $config_index               = array('index'=>'index.php');
        if($this->input->post('mod_rewrite'))
        {
            $config_index           = array('index'=>'');
        }
        $file_contents              = $this->load->view('templates/config', $config_index, true);
        write_file(FCPATH.'application/config/config.php', $file_contents);
    }

    protected function setup_htaccess()
    {
        if($this->input->post('mod_rewrite'))
        {
            $subfolder = trim(str_replace($_SERVER['DOCUMENT_ROOT'], '', FCPATH), '/').'/';
            $file_contents              = $this->load->view('templates/htaccess', array('subfolder'=>$subfolder), true);
            write_file(FCPATH.'.htaccess', $file_contents);
        }
    }

    protected function load_migration()
    {
        $this->load->library('migration');

        if ( ! $this->migration->current())
        {
            show_error($this->migration->error_string());
        }
    }
}
