<?php

$preload = new cms_eev(null);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

    class cms_mail_gui extends cms_std_gui {
        public function ext_a() {
            if ($_POST['action'] == 'sendmail-tt') {
                // inputs
                $subj = $_POST['mail_subject'];
                $text = $_POST['ctext'];
                $text = '
    <html>
    <head>
    <base href="http://'.$_SERVER['SERVER_NAME'].'/" />
    </head>
    <body>'.$text.'
    </body>
    </html>';
                $to = $_POST['mail_rec'];
                // read config
                $ms = cms_universe::$puniverse->site_by_filter(cms_universe::$site_filter_id,'9999');
                $entry = $ms->get('5',127);
                
                $usemail = ($entry->fusemail?true:false);
                
                $frommail = $entry->fsmail;
                $fromname = $entry->fsname;
                $replyto = $entry->fsrm;

                $serv = $entry->femailh;
                $user = $entry->femailu;
                $pass = $entry->femailp;                
                $auth = $entry->femaila;
                // send mail
                require '../vendor/autoload.php';
                $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
                try {
                    //Server settings
                    $mail->SMTPDebug = 0;                                 // Enable verbose debug output
                    if(!$usemail) {
                        $mail->isSMTP();                                      // Set mailer to use SMTP
                        $mail->Host = $serv;  // Specify main and backup SMTP servers
                        $mail->SMTPAuth = true;                               // Enable SMTP authentication
                        $mail->Username = $user;                 // SMTP username
                        $mail->Password = $pass;                           // SMTP password
                        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
                        $mail->Port = 587;                                    // TCP port to connect to
                    }
                    //Recipients
                    $mail->setFrom($frommail, $fromname);
                    $mail->addAddress($to);     // Add a recipient
                    $mail->addReplyTo($replyto);

                    //Attachments
                    // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
                    // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

                    //Content
                    $mail->isHTML(true);                                  // Set email format to HTML
                    $mail->Subject = $subj;
                    $mail->Body    = $text;
                    $mail->AltBody = strip_tags($text);

                    $mail->send();
                    $this->_template->addMessage('x91',__('&0 - Wysyłka maila udana'));
                    $this->_template->showMessage(cms_template::$msg_info,'x91', array(date("Y-m-d H:i:s")));
                } catch (Exception $e) {
                    $this->_template->addMessage('x91',__('&0 - Wysyłka maila nie powiodła się. Informacja dodatkowa : &1.'));
                    $this->_template->showMessage(cms_template::$msg_error,'x91', array(date("Y-m-d H:i:s"), $mail->ErrorInfo));
                }
            }
        }

        public function ext_d() {
            parent::ext_d();

            $this->_template->addTab('g0',__('Test opcji e-mail'));
            $this->_template->addGroup('g0','g',__('Wysyłka testowego e-mail'));
            $this->_template->addField('g',__('UWAGA!'),'warn','span',null,__('Po zmianie opcji e-mail w zakładce dane dodatkowe, upewnij się że zostały zapisane przed uruchomieniem testu.'));
            $this->_template->addField('g',__('Odbiorcy (oddziel średnikiem)'), 'mail_rec', 'text', null, '');    
            $this->_template->addField('g',__('Temat'), 'mail_subject', 'text', null, '');    
            $this->_template->addField('g','', 'ctext', 'textareaeditorfull', array('rows'=>16, 'cols'=>60), '');
            $this->_template->addField('g',__('Załącznik 1 (opcjonalnie)'), 'mail_att1', 'file', array('downloadkey'=>''), '');
            $this->_template->addField('g',__('Załącznik 2 (opcjonalnie)'), 'mail_att2', 'file', array('downloadkey'=>''), '');
            $this->_template->addField('g',__('Załącznik 3 (opcjonalnie)'), 'mail_att3', 'file', array('downloadkey'=>''), '');
            $this->_template->addField('g',__('Załącznik 4 (opcjonalnie)'), 'mail_att4', 'file', array('downloadkey'=>''), '');

            $this->_template->addField('g','', '', 'button', array('action'=>'sendmail-tt', 'group'=>'1'), __('Wyślij testowy'));            
        }
    }
?>