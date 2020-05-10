<?php

    class cms_um_gui extends cms_std_gui {

        private $userman = null;
        private $du = null; // display user
        private $dl = true; // display list
        private $id = '';
        private $new = false;

        protected function initialize() {
            $this->userman = cms_universe::$puniverse->userman();
            $this->_template->addTab('g0',__('Użytkownicy'));
            $this->_template->addGroup('g0','g',__('Administracja użytkownikami panelu CMS'));   
            $this->_template->selectTab('g0');
        }

        public function ext_a() {
            $this->id = $_POST['id'];

            if ($_POST['action'] != '') {
                if (@$_POST['uid'] == 'cms*' || $this->id == 'cms*' || @$_POST['rname'] == 'cms*') {
                    $this->_template->showMessage(cms_template::$msg_error, '990', array('cms*'));
                    $_POST['action'] = '';
                    $_POST['id'] = '';
                    $this->dl = true;
                }
            }

            if ($_POST['action'] == 'ume_save') {
                if ($_POST['rname'] == '') {
                    $this->_template->showMessage(cms_template::$msg_error, '994');
                    $_POST['action'] = '';
                    $_POST['id'] = '';
                } else {
                    $this->id = $_POST['uid'];        
                    if ($_POST['pass'] == '' && $this->id == '') {
                        $this->_template->showMessage(cms_template::$msg_error, '992');
                        $_POST['action'] = '';
                        $_POST['id'] = '';
                    } else {
                        if ($this->id == '' || ($user = $this->userman->get_user($this->id))) {
                            if ($this->id == '') {
                                $user = new cms_user('', false, '', '', array(), array(), array());
                            }
                            $user->name = $_POST['rname'];
                            $user->valid = ($_POST['valid'] == '1');
                            if ($_POST['pass']!='') {
                                $user->set_pass($_POST['pass']);
                                $user->adminset = true;
                            }
                            $user->desc = $_POST['desc'];
                            $allowall = explode("\n",$_POST['a0']);
                            $denyact = explode("\n",$_POST['a1']);
                            $denyall = explode("\n",$_POST['a2']);
                            $user->allowall = array_map('trim', $allowall);
                            $user->denyact = array_map('trim', $denyact);
                            $user->denyall = array_map('trim', $denyall);                   
                            if ($this->id == '') { // nowy
                                if ($this->userman->get_user($user->name)) {
                                    $this->_template->showMessage(cms_template::$msg_error, '991', array($user->name));
                                    $_POST['action'] = '';
                                    $_POST['id'] = '';    
                                } else {
                                    $this->userman->add_user($user);
                                }
                            } else { // stary
                                $this->userman->remove_user($this->id);
                                $this->userman->add_user($user);                        
                            }
                        } else {
                            $this->_template->showMessage(cms_template::$msg_error, '990', array($this->id));
                        }
                    }
                }
            }

            if ($_POST['action'] == 'ume_new') {
                $_POST['action'] = 'ume_edit';
                $this->id = '';
                $this->new = true;
            }
            
            if ($_POST['action'] == 'ume_edit') {
                if ($this->new) {
                    $this->id = "<nowy>";
                    $user = new cms_user( '', false, ''.mt_rand(123456789,987654321), '', array(), array(), array() );
                } else {
                    $user = $this->userman->get_user($this->id);
                }
                if ($user instanceof cms_user) {
                    $this->du = $user;
                    $this->dl = false;
                }
            }

            if ($_POST['action'] == 'ume_delete') {
                if ($this->userman->remove_user($this->id)) {            
                    $this->_template->showMessage(cms_template::$msg_info, '995', array($this->id));
                } else {
                    $this->_template->showMessage(cms_template::$msg_error, '990', array($this->id));
                }
            }

            if (in_array($_POST['action'], array('ume_save', 'ume_delete'))) {
                $this->userman->write();
            }
            

        }

        public function ext_d() {
            if ($this->du instanceof cms_user) {
                $user = $this->du;
                $this->_template->addGroup('g0','e',__('Edycja użytkownika').' '.$this->id);
                $this->_template->addField('e','', 'uid', 'hidden', null, $user->name);
                $this->_template->addField('e',__('Nazwa (login)'), 'rname', 'text', null, $user->name);
                $this->_template->addField('e',__('Ostatnia zmiana hasła'), '', 'span', null, (($user->adminset)?'ADMIN @ ':'USER @ ').(isset($user->lpcd)?date("Y-m-d H:i",$user->lpcd):'B/D'));
                $this->_template->addField('e','','','span',null, __('Poniżej podaj nowe hasło, aby ustawić. Jeśli pozostawisz pole puste, stare hasło pozostanie bez zmian.'));
                $this->_template->addField('e',__('Hasło'), 'pass', cms_config::$cc_cms_userman_show_password?'text':'pass', null, '');
                $this->_template->addField('e',__('Aktywny'), 'valid', 'simplecheckbox', null, $user->valid?'1':'');
                $this->_template->addField('e',__('Opis'), 'desc', 'text', null, $user->desc);            
                $this->_template->addGroup('g0','f',__('Uprawnienia użytkownika').' '.$this->id);
                $this->_template->addField('f','','','span',null, __('Poniżej podaj które ścieżki w CMS mają być dostępne lub do których dostęp ma być zabroniony, po jednej ścieżce w każdej linii.'));

                $this->_template->addField('f','','','span',null, __('Format ścieżki to xxx\yyy\zzz, gdzie xxx to identyfikator serwisu (identyfikatory nadane są od lewej, pierwszy serwis ma identyfikator 1, drugi 2, trzeci - 3 itd. Administracja ma identyfikator 9999), yyy to kod elementu w serwisie, lub ścieżka uniwersalna, zzz to liczbowy kod języka w CMS, wg instrukcji. Zamiast kodu języka można podać "*".'));

                $this->_template->addField('f','','','span',null, __('Przykładowo, ścieżka tego elementu (zarządzanie użytkownikami) to').' '.cms_config::$cc_admin_site_id.'\\20/1. '.__('Ścieżkę uniwersalną konstruujemy używając znaku * zamiast kodu elementu, lub ** aby określić dowolną ścieżkę. Np. ścieżka do wszystkich funkcji newslettera to').' '.cms_config::$cc_admin_site_id.'\\10/*\\*, '.__('a ścieżka dostępu do wszystkich funkcji administracyjnych to').' '.cms_config::$cc_admin_site_id.'\\**\\*');
                $this->_template->addField('f',__('Dostępne ścieżki w CMS'), 'a0', 'textarea', array('rows'=>5, 'cols'=>30),
                join("\n", $user->allowall));
                $this->_template->addField('f',__('Dozwolony odczyt, zabronione zmiany'), 'a1', 'textarea', array('rows'=>5, 'cols'=>30),
                join("\n", $user->denyact));
                $this->_template->addField('f',__('Zabroniony dostęp do ścieżek'), 'a2', 'textarea', array('rows'=>5, 'cols'=>30),
                join("\n", $user->denyall));
                $this->_template->addField('f','', '', 'button', array('action'=>'ume_save', 'group'=>'1'), $this->new?__('Utwórz'):__('Zapisz zmiany'));
                // proxy
                $uo = cms_universe::$puniverse->userman()->get_user(cms_universe::$puniverse->session()->luser);
                if ($uo->check_access('*', '*', '*') == cms_userman::$right_allow) {
                    $this->_template->addGroup('g0','g',__('Przejęcie loginu').' '.$this->id);
                    $this->_template->addField('g','', 'uid', 'hidden', null, $user->name);
                    $this->_template->addField('g','', '', 'button', array('action'=>'ume_takeover', 'group'=>'2'), __('Zaloguj jako ').$user->name);
                }
            }
            if ($this->dl) {
                // display user table
                $ftab = array();
                $cms_usertable = $this->userman->get_user_list();
                uasort($cms_usertable, function($a, $b) {
                    return strcasecmp($a->name, $b->name);
                });
                reset($cms_usertable);
                while(list($uname,$udata) = each($cms_usertable)) {
                    $ftab[] = array($uname, $udata->name, $udata->valid?__('TAK'):__('NIE'), $udata->desc, (($udata->adminset)?'ADMIN @ ':'USER @ ') . (isset($udata->lpcd)?date("Y-m-d H:i",$udata->lpcd):'B/D'));
                }

                $table = array(
                'actionprefix' => 'ume',
                'button_edit' => true,
                'button_delete' => true,
                'button_up' => false,
                'button_down' => false,
                'button_preview' => false,
                'button_save' => false,
                'legend' => false,
                'boxed' => false,
                'columns' => array (
                array('name'=>__('Nazwa (login)'), 'type'=>'text'),
                array('name'=>__('Aktywny'), 'type'=>'text'),
                array('name'=>__('Opis'), 'type'=>'text'),
                array('name'=>__('Ostatnia zmiana hasła'), 'type'=>'text'),
                ),
                'rows' => $ftab,
                'extra' => array()        
                );

                $this->_template->addField('g','', '', 'button', array('action'=>'ume_new', 'group'=>'1'), __('Dodaj nowego'));
                $this->_template->addField('g','','','autolist',$table,'');
            }
        }
    }
?>