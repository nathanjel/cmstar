<?php

  class cms_login_gui extends cms_tpl_gui {      
      
      private $newpass = false;

      public function new_pass_mode() {
          $this->newpass = true;
          $this->login->inform_pcr();
      }

      public function actions() {
          if ($_GET['code']) {
            $gapi = cms_universe::$puniverse->googleAPI();
            if ($gapi) {
              $token = $gapi->fetchAccessTokenWithAuthCode($_GET["code"]);
              if (!isset($token['error'])) {
                $gapi->setAccessToken($token['access_token']);
                $gsrv = new Google_Service_Oauth2($gapi);
                $data = $gsrv->userinfo->get();
                $login = $data->email;
                if ($this->login->login($login, '', 'google')) {
                  cms_universe::$puniverse->session()->extauthname = $data->name;
                }
              }
            }
          }
          if ($_POST['actioncode'] == "go") {
                $this->login->login($_POST['login'], $_POST['pass']);
          }
          if ($_POST['actioncode'] == "newpass") {
              if($this->login->is()) {
                $this->login->update_password($_POST['passn1'], $_POST['passn2']);
              }
          }
          $_POST['actioncode'] = '';      
      }
      
      public function display() {
          $ex = cms_licence::get()->edit_valid_until;
          $dx = cms_licence::get()->valid_until;
          
          $this->_template->setP('display-licence', ($dx==-1?__('bezterminowo'):date("Y-m-d",$dx)));
          $this->_template->setP('edit-licence', ($ex==-1?__('bezterminowo'):date("Y-m-d",$ex)));
          
          switch($this->login->login_error()) {
            case cms_login::$bad_cred:
                $this->_template->addMessage('000', __('Nieprawidłowe dane logowania'));
                $this->_template->showMessage(cms_template::$msg_error, '000', array());
                break;
            case cms_login::$timeout:
                $this->_template->addMessage('000', __('Wylogowany automatycznie z powodu nieaktywności'));
                $this->_template->showMessage(cms_template::$msg_error, '000', array());
                break;
            case cms_login::$logout:
                $this->_template->addMessage('000', __('Wylogowano na życzenie użytkownika'));
                $this->_template->showMessage(cms_template::$msg_error, '000', array());
                break;
            case cms_login::$userlimit:
                $this->_template->addMessage('000', __('Osiągnięto maksymalną liczbę jednoczesnych użytkowników ograniczoną licencją'));
                $this->_template->showMessage(cms_template::$msg_error, '000', array());
                break;
            case cms_login::$passchangefail:
                $this->_template->addMessage('000', __('Zmiana hasła nie powiodła się. Hasło musi mieć minimum 6 znaków, musi być inne niż poprzednie.'));
                $this->_template->showMessage(cms_template::$msg_error, '000', array());
                break;
            case cms_login::$passchangereq:
                $this->_template->addMessage('000', __('Twoje hasło wygasło. Ustal nowe hasło, minimum 6 znaków.'));
                $this->_template->showMessage(cms_template::$msg_error, '000', array());
                break;
            default:
                break;
          }
          
          $this->_template->repstr('Licencja oprogramowania', __('Licencja oprogramowania'));
          $this->_template->repstr('Ważność licencji: wyświetlanie', __('Ważność licencji: wyświetlanie'));
          $this->_template->repstr('edycja', __('edycja'));
          $this->_template->repstr('PANEL KLIENTA', __('PANEL KLIENTA'));
          
          $this->_template->repstr('Nazwa konta/Nick:', __('Nazwa konta/Nick:'));
          $this->_template->repstr('Hasło/Pass:', __('Hasło/Pass:'));
          $this->_template->repstr('Język/Language:', __('Język/Language:'));
          
          $lc0 = -100;  // hide langsel                
          $clc = cms_lp::get_session_language_code();
          foreach (cms_lp::available_languages() as $lc=>$ln) {
          		$this->_template->addLoginLanguage($lc,$ln,$lc == $clc);
              $lc0++;
          }
          
          if ($lc0<2) {
            $this->_template->hideClass('langs');
          }
          
          if ($this->newpass) {
            $this->_template->hideClass('logon');
            $this->_template->hideClass('logon_redir_1');
          } else {
            $this->_template->hideClass('newpass');
            // do we have google login?
            $gapi = cms_universe::$puniverse->googleAPI();
            if ($gapi) {
              $g_logon_redir_url = $gapi->createAuthUrl();
              $this->_template->repstr('GOOGLE_LINK_HREF', $g_logon_redir_url);
              $this->_template->repstr('LOGON_REDIR_1_NAME', 'Google');
            } else {
              $this->_template->hideClass('logon_redir_1');
            }
          }

          $this->_template->repstr('LANGCODE', $clc);
          
          // print_r($_POST);
          // print_r($this);

          echo $this->_template;
      }
      
      public function tpl_name() {
          return cms_config::$cc_cms_template_login;
      }
      
  }
  
?>