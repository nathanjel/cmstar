<?php
    class cms_pass_gui extends cms_std_gui {

        public function ext_a() {
            if ($_POST['action'] == 'pass') {

                if ($_POST['npass1'] == '') {
                    $this->_template->showMessage(cms_template::$msg_error, '956');
                    return;
                }

                if ($_POST['npass1'] != $_POST['npass2']) {
                    $this->_template->showMessage(cms_template::$msg_error, '952');
                    return;
                }

                if (strlen($_POST['npass1'])>99) {
                    $this->_template->showMessage(cms_template::$msg_error, '958');
                    return;
                }

                $np = $_POST['npass1'];
                $npc = @iconv('UTF-8', 'UTF-8//IGNORE', $np);

                if ($np != $npc) {
                    $this->_template->showMessage(cms_template::$msg_error, '957');
                    return;
                }
                
                $t0 = cms_universe::$puniverse->userman()->get_user('cms*');
                if ($t0 == false) {
                    $this->_template->showMessage(cms_template::$msg_error, '950');
                    return;            
                }
                
                $p0 = cms_universe::$puniverse->userman()->check_login('cms*',$_POST['bpass']);
                $p0 = $p0 && $t0->set_pass($np, $_POST['bpass']);
                
                if ($p0 === FALSE) {
                    $this->_template->showMessage(cms_template::$msg_error, '951');
                    return;            
                }
                
                cms_universe::$puniverse->userman()->write();
                
                $this->_template->showMessage(cms_template::$msg_succ, '954');
            }
        }
        
        public function ext_d() {
            $this->_template->addTab('g0',__('Hasło dla CMS*'));

            $this->_template->addGroup('g0','g',__('Hasło użytkownika cms*'));
            $this->_template->addField('g', __('UWAGA!'), '', 'span', null, __('CMS* to specjalny użytkownik, z pełnymi uprawnieniami. Nie można go usunąć ani modyfikować. Hasło dla cms* powinno być szczególnie chronione. To hasło pozwala na administrację całością serwisu.'));
            $this->_template->addField('g',__('Bieżące hasło'), 'bpass', 'pass', null, '');
            $this->_template->addField('g',__('Nowe hasło'), 'npass1', 'pass', null, '');
            $this->_template->addField('g',__('Ponownie nowe hasło'), 'npass2', 'pass', null, '');
            $this->_template->addField('g','', '', 'button', array('action'=>'pass'), __('Zmień hasło'));
            
            $this->_template->selectTab('g0');
        }
    }
?>