<?php
    class cms_tp_gui extends cms_std_gui {

        private $dispt = '';
        private $dispi = '';
        private $files = null;

        protected function initialize() {
            // establish tpl list
            $dir = cms_config::$cc_cms_site_templates_dir;
            $d = dir($dir);
            $this->files = array();
            while (false !== ($xentry = $d->read())) {
                if (substr($xentry,0,1) == '.')
                    continue;
                $fp = $dir.$xentry;
                if (is_dir($fp)) {
                    // one level deep dive only!
                    if (substr($xentry,0,1) == '.')
                        continue;
                    $d2 = dir($fp);
                    while (false !== ($xentry = $d2->read())) {
                        $fp = $d2->path.'/'.$xentry;
                        if(is_file($fp)) {
                            $this->files[$fp] = $fp;
                        }
                    }
                    $d2->close();
                } else {
                    $this->files[$fp] = $fp;
                }        
            }
            $d->close();
            ksort($this->files);
        }

        public function ext_a() {
            if ($_POST['action'] == 'savetpl') {
                $id = $_POST['tplid'];
                $optdata = $_POST['tpldata'];
                if ($this->files[$id] == $id) {
                    $cf = $id;
                    $rc = $cf.'.wip';
                    @unlink($rc);
                    $j = @file_put_contents($rc, $optdata, LOCK_EX);
                    if ($j==FALSE) {
                        $this->_template->showMessage(cms_template::$msg_error, '963');
                    } else {
                        unlink($cf);
                        rename($rc,$cf);
                        $this->_template->showMessage(cms_template::$msg_succ, '964');
                    }
                }    
            }

            if ($_POST['action'] == 'tpl_edit') {
                $id = $_POST['id'];
                if ($this->files[$id] == $id) {
                    // display single tpl
                    $this->dispt = @file_get_contents($id);
                    $this->dispi = $id;
                }
            }
        }

        public function ext_d() {
            // banner
            $this->_template->addTab('g0',__('Szablony'));
            $this->_template->addGroup('g0','g',__('Szablony wyświetlania serwisu WWW'));
            $this->_template->addField('g',__('UWAGA!'),'warn','span',null,__('Błędne wpisy w poniższych plikach uniemożliwią lub zdestabilizują pracę CMS oraz strony internetowej. Błędy w działaniu serwisu spowodowane nieumiejętną zmianą zawartości szablonów nie są objęte gwarancją.'));

            if ($this->dispt) {

                $this->_template->addField('g',__('Plik szablonu'), '', 'span', null, $this->dispi);
                $this->_template->addField('g','', 'tpldata', 'code', array('rows'=>50, 'cols'=>76, 'mode'=>'php'), $this->dispt);
                $this->_template->addField('g','', '', 'button', array('action'=>'savetpl', 'group'=>'1'), __('Zapisz zmiany'));
                $this->_template->addField('g','', '', 'button', array('action'=>'cancel', 'group'=>'1'), __('Anuluj'));
                $this->_template->addField('g','', 'tplid', 'hidden', null,$this->dispi);
            } else {        

                // display table
                $ftab = array();

                reset($this->files);
                while(list($fid,$file) = each($this->files)) {
                    $ftab[] = array($file, $file);
                }

                $table = array(
                'actionprefix' => 'tpl',
                'button_edit' => true,
                'button_delete' => false,
                'button_up' => false,
                'button_down' => false,
                'button_preview' => false,
                'button_save' => false,
                'legend' => false,
                'boxed' => false,
                'columns' => array (
                array('name'=>__('Nazwa pliku'), 'type'=>'text')
                ),
                'searchfields' => array (),
                'sortfields' => array (),
                'rows' => $ftab,
                'extra' => array()        
                );


                $this->_template->addField('g','','','autolist',$table,'');
                
            }
            $this->_template->selectTab('g0');
        }
    }

?>