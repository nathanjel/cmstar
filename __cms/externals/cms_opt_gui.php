<?php
    class cms_opt_gui extends cms_std_gui {
        public function ext_a() {
        	if ($_POST['action'] == 'saveconf') {
                $optdata = $_POST['cconf'];
                $ts = substr($optdata,0,5);
                if ($ts == '<?php') {
                	$res = @eval('?>'.$optdata); // naive test
                	$error = error_get_last();
                } else {
                	$res = false;
                	$error = __('Treść musi rozpoczynać się od "<?php"');
                }               
                if ($res !== FALSE) {
                    $cf = cms_config::$cc_cms_customer_config_file;
                    $rc = $cf.'.wip';
                    @unlink($rc);
                    $j = @file_put_contents($rc, $optdata, LOCK_EX);
                    if ($j==FALSE) {
                        $this->_template->showMessage(cms_template::$msg_error, '962');                        
                    } else {
                        unlink($cf);
                        rename($rc,$cf);
                        $this->_template->showMessage(cms_template::$msg_succ, '968');                        
                    }
                    include cms_config::$cc_cms_customer_config_file; // load real version of options, again naive
                } else {
                    $this->_template->showMessage(cms_template::$msg_error, '967', array($error['message']));
                }                
            }
            if ($_POST['action'] == 'saveopt') {
                $optdata = $_POST['optxml'];
                $test = new DOMDocument();
                $tr = @$test->loadXML($optdata);
                if ($tr) {
                    $cf = cms_config::$cc_cms_opt_file;
                    $rc = $cf.'.wip';
                    @unlink($rc);
                    $j = @file_put_contents($rc, $optdata, LOCK_EX);
                    if ($j==FALSE) {
                        $this->_template->showMessage(cms_template::$msg_error, '962');
                    } else {
                        unlink($cf);
                        rename($rc,$cf);
                        $this->_template->showMessage(cms_template::$msg_succ, '961');
                        cms_universe::$puniverse->reload_options();
                    }
                } else {
                    $this->_template->showMessage(cms_template::$msg_error, '960');
                }                
            }
        }

        public function ext_d() {
            $df = @file_get_contents(cms_config::$cc_cms_opt_file);
            $ccf = @file_get_contents(cms_config::$cc_cms_customer_config_file);
            $scf = file_get_contents(cms_config::$cc_cms_system_config_file);

            $this->_template->addTab('g0',__('Konfiguracja witryn'));
            $this->_template->addGroup('g0','ga',__('Konfiguracja witryn - options.xml'));
            $this->_template->addField('ga',__('UWAGA!'),'warn','span',null,__('Błędne wpisy w tym pliku uniemożliwią lub zdestabilizują pracę CMS oraz strony internetowej. Błędy w działaniu serwisu spowodowane nieumiejętną zmianą zawartości konfiguracji nie są objęte gwarancją.'));
            $this->_template->addField('ga','', 'optxml', 'code', array('rows'=>70, 'cols'=>76, 'mode'=>'xml'), $df);
            $this->_template->addField('ga','', '', 'button', array('action'=>'saveopt'), __('Zapisz zmiany witryn'));
            
            $this->_template->addTab('g1',__('Konfiguracja klienta CMS'));
            $this->_template->addGroup('g1','gb',__('Konfiguracja klienta CMS'));
            $this->_template->addField('gb',__('UWAGA!'),'warn','span',null,__('Błędne wpisy w tym pliku uniemożliwią lub zdestabilizują pracę CMS oraz strony internetowej. Błędy w działaniu serwisu spowodowane nieumiejętną zmianą zawartości konfiguracji nie są objęte gwarancją.'));
            $this->_template->addField('gb','', 'cconf', 'code', array('rows'=>70, 'cols'=>76, 'mode'=>'php'), $ccf);
            $this->_template->addField('gb','', '', 'button', array('action'=>'saveconf'), __('Zapisz zmiany konfiguracji'));
            
            $this->_template->addTab('g2',__('Konfiguracja systemowa CMS'));
            $this->_template->addGroup('g2','gc',__('Konfiguracja klienta CMS (do odczytu)'));
            $this->_template->addField('gc','', 'sconf', 'code', array('rows'=>70, 'cols'=>76, 'mode'=>'php'), $scf);
            
            $this->_template->selectTab('g0');
        }
    }
?>