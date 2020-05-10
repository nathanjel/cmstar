<?php
  
  abstract class cms_tpl_gui extends cms_apihost {
      
      protected $_template;
      protected $login;
      
      protected $disabled_map = array();

      protected function initialize() {
          // your own initialization here
      }
      
      public function set_login($loginx) {
          $this->login = $loginx;
      }
      
      abstract public function tpl_name();
      
      public function __construct($site, $lang, $path, $entry, $user) {
          parent::__construct($site, $lang, $path, $entry, $user);
          $this->_template = new cms_template();
          $this->_template->setTemplate($this->tpl_name());
          $this->_template->setvcode($entry->pathl.$user->name);
          $this->initialize();
      }
      
      public function template() {
          return $this->_template;
      }

      public function post_data_validation() {
          if (count($_POST)) {
              $df = $_POST['df'];
              $dv = $_POST['dv'];
              $dl = array_filter(explode(',', $df));
              $ldv = count($dl).cms_config::$cc_cms_misc_e1code.$this->_template->getvcode();
              $ldv = sha1($ldv.$df);
              if ($ldv != $dv) {
                if (strlen($_POST['action'])) {
                  $this->_template->showMessage(cms_template::$msg_error, '999');
                  // print_r($_POST);
                }
                $_POST = array();
              } else {
                $this->disabled_map = array_flip($dl);
              }
          }
      }

      public function input_field_was_disabled($fname) {
          return isset($this->disabled_map[$fname]);
      }
      
  }
  
?>