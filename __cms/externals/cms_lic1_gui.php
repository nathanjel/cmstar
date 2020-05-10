<?php
    class cms_lic1_gui extends cms_std_gui {
        public function ext_a() {
            // no actions
        }

        public function ext_d() {


            $this->_template->addTab('g0',__('Licencja'));

            $this->_template->addGroup('g0','g1',__('Okres ważności licencji'));
            $lic = cms_licence::get();
            
            $this->_template->addField('g1', __('Wyświetlanie'), '', 'span', null, $lic->valid_until==-1?__('bezterminowo'):date("Y-m-d", $lic->valid_until));
            $this->_template->addField('g1', __('Edycja'), '', 'span', null, ($lic->edit_valid_until==-1?__('bezterminowo'):date("Y-m-d", $lic->edit_valid_until)));

            $this->_template->addGroup('g0','g',__('Dane licencji'));
            $this->_template->addField('g', __('Wystawiona przez'), '', 'span', null, $lic->issued_by);
            $this->_template->addField('g', __('Wystawiona dla'), '', 'span', null, $lic->issued_for);            
            $this->_template->addField('g', __('Wystawiona dnia'), '', 'span', null, date('Y-m-d', $lic->issue_date));
            $this->_template->addField('g', __('Limit jednoczesnych użytkowników'), '', 'span', null, $lic->maxcu);
            $this->_template->addField('g', __('Maski adresu DNS serwera WWW'), '', 'textarea', array('rows'=>10, 'cols'=>40), @join("\n",$lic->hosts));
            
            $this->_template->selectTab('g0');
			
            
        }
    }
?>