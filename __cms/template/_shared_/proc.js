// stałe

MAX_EDITORS = 250;

// komunikaty

function date_picker_locale() {
    return {
        days: [_("Niedziela"), _("Poniedziałek"), _("Wtorek"), _("Środa"), _("Czwartek"), _("Piątek"), _("Sobota"), _("Niedziela")],
        daysShort: [_("Nie"), _("Pon"), _("Wto"), _("Śro"), _("Czw"), _("Pią"), _("Sob"), _("Nie")],
        daysMin: [_("Nd"), _("Pn"), _("Wt"), _("Śr"), _("Cz"), _("Pt"), _("So"), _("Nd")],
        months: [_("Styczeń"), _("Luty"), _("Marzec"), _("Kwiecień"), _("Maj"), _("Czerwiec"), _("Lipiec"), _("Sierpień"), _("Wrzesień"), _("Październik"), _("Listopad"), _("Grudzień")],
        monthsShort: [_("Sty"), _("Lut"), _("Mar"), _("Kwi"), _("Maj"), _("Cze"), _("Lip"), _("Sie"), _("Wrz"), _("Paź"), _("Lis"), _("Gru")],
        weekMin: _('ty')
    };
}

var message_changes = _('Wprowadziłeś zmiany, które nie zostały zapisane.\n\nCzy chcesz przejść do innej strony i utracić niezapisane zmiany ?');

var message_autopastefail = _('Automatyczne wklejenie danych nie powiodło się.\n\nDomyślnie uprawnienia dla dostępu skryptu do schowka są w przeglądarkach z rodziny Mozilla/Netscape wyłączone. Prawdopodobnie tak stało się w tym przypadku.\n\nPomaga włączenie ustawienia "signed.applets.codebase_principal_support" na wartość typu Boolean "true" przy pomocy strony "about:config".\n\nKomunikat przekazany przez przeglądarkę:') + ' ';

var message_del_element1 = _('Czy napewno chcesz usunąć ten element?');
var message_del_element2 = _('\n\nKliknięcie OK spowoduje bezpowrotne\nusunięcie elementu!');

var message_del_bpl1 = _('Czy napewno chcesz usunąć element "');
var message_del_bpl2 = _('" ?\n\nKliknięcie OK spowoduje bezpowrotne\nusunięcie elementu!');

var message_del_file = _('Czy napewno chcesz usunąć wybrany plik?\n\nKliknięcie OK spowoduje bezpowrotne\nusunięcie pliku!');

var message_del_photo = _('Czy napewno chcesz usunąć wybrane zdjęcie?\n\nKliknięcie OK spowoduje bezpowrotne\nusunięcie zdjęcia!');
var message_del_photom = _('Czy napewno chcesz usunąć wybraną miniaturę?\n\nKliknięcie OK spowoduje bezpowrotne\nusunięcie miniatury!');

var message_already_working = _('Proszę czekać, trwa przetwarzanie poprzedniej akcji');
var already_working_lock = false;

function already_called_check(set_lock) {
	var rc = false;
	if (already_working_lock) {
		alert(message_already_working);
		rc = true;
	}
	if (set_lock)
		already_working_lock = true;
	return rc;
}

/// DIRTY + autosave

var action_driven_exit = false;
var is_dirty = false;
var auto_save_on_idle = false;
var idle_time = 0;

function setauto_save_on_idle(val) {
	auto_save_on_idle = val;
}

function isDirty() {
	var loc_dirty = false;
	for (i=0; i<MAX_EDITORS; i++) {
		var obj = ofckeditors[i];
		if (obj) {
			loc_dirty |= obj.checkDirty();
		}
	}
	return is_dirty | loc_dirty;
}

function getDirty() {
	is_dirty = true;
}

function auto_save_timer() {
	idle_time += 1;
	if (auto_save_on_idle && (idle_time > auto_save_on_idle) && isDirty())
		return cmsmaster_call(0,"update,autosave");
}

function page_timer() {
	auto_save_timer();
}

function install_page_timer() {
	setInterval(page_timer, 1000);
}

$(window).on('beforeunload', function(event) {
	if (!action_driven_exit && isDirty()) {
		return message_changes;
	}
});

install_page_timer();

// konfiguracja
var fck = false;
var fck_lock = true;
var fck_defname = 'contentText';
var fck_tb_main = '__CMS_Main';
var fck_tb_basic = '__CMS_Basic';
var fck_basepath = "ckeditor/";
var formid="cmsmasteredit";

// akcje

var ofckeditors = new Array();

function key_press_dont_submit_on_enter(e) { //e is event object passed from function invocation
    var characterCode;
    if(e && e.which) {
        e = e;
        characterCode = e.which;
    }
    else
        {
        e = event;
        characterCode = e.keyCode;
    }     
    if(characterCode == 13) {
        return false;
    }
    else
        {
        return true;
    }
}

function mm_sec1(e) {
    alert(message_autopastefail+e);
}

function clipTableGet(name, rc, cc, form) {
    is_ie = false;
    if (window.clipboardData) {
        exceld = window.clipboardData.getData('Text');
        is_ie = true;
    } else {
        var suc = false; 
        try {
            try{
                netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect")
            } catch(e) {
                mm_sec1(e);
                return;
            }
            var clip = Components.classes["@mozilla.org/widget/clipboard;1"].getService(Components.interfaces.nsIClipboard);
            if (clip) suc = true;
            var trans = Components.classes["@mozilla.org/widget/transferable;1"].createInstance(Components.interfaces.nsITransferable);
            if (trans) suc = true;
            if (suc) {
                trans.addDataFlavor("text/unicode");
                clip.getData(trans, clip.kGlobalClipboard);
                var str       = new Object();
                var strLength = new Object();
                trans.getTransferData("text/unicode", str, strLength);
                if (str) str       = str.value.QueryInterface(Components.interfaces.nsISupportsString);
                if (str) pastetext = str.data.substring(0, strLength.value / 2);
                exceld = pastetext;
            }
        } catch (e) {
            if (!suc) {
                mm_sec1(e)
                return;
            }
        }
    } 
    exrows = exceld.split("\n");
    var re2 = new RegExp(["/[\n\t\r]/", ["g"]]);
    for(r=0; r<(Math.min(rc,exrows.length)); r++) {
        exrow = exrows[r].split("\t");
        for(c=0; c<(Math.min(cc,exrow.length)); c++) {
            excol = exrow[c];
            if (is_ie) {
                col = excol.replace("\n",'').replace("\r",'').replace("\t",'');
            } else {
                col = excol.replace(re2,'');
            }
            col = col.replace(/\\/g,'\\\\').replace(/'/g,'\\\'');
            p1 = "document.getElementById('"+form+"').t2d_"+name+"_r"+r+"c"+c+".value='"+col+"';";
            eval(p1);
        }
    }
}

function tableClean(name, rc, cc, form) {
    // alert(window.clipboardData.getData('Text'));
    for(r=0; r<rc; r++) {
        for(c=0; c<cc; c++) {
            // t2d_{$name}_r{$r}c{$c}
            p1 = "document.getElementById('"+form+"').t2d_"+name+"_r"+r+"c"+c+".value='';";
            eval(p1);
        }
    }
}

function lc_confirm_del(what) {
    return confirm(message_del_element1 + '\n(nazwa elementu: '+what+')\n' + message_del_element2);
}                          

function bpl_aldp_confirm_del(what) {
    return confirm(message_del_bpl1 + what + message_del_bpl2);
}

function lc_confirm_del_p() {   
    return confirm(message_del_photo);
}

function lc_confirm_del_pm() {   
    return confirm(message_del_photom);
}

function lc_confirm_del_f() {
    return confirm(message_del_file);
}

function cmsmaster_late_initialization() {
    $("input[name='f_state']").on('click', function() {
        t = $(this);
        control = $('input[name=f_lesscontrol]').val();
        controls = control.split(',');
        if (t.val() == 2) {
            for (var i = controls.length - 1; i >= 0; i--) {
                check = controls[i];
                $("input[name='" + check + "']").each(function() {
                    var t = $(this);
                    if (t.prop('disabled')) {
                        t.prop('disabled', false);
                        t.prop('checked', t.data('val0'));
                    }
                });
                $('input[name=f_topic]').each(function() {
                    var t = $(this);
                    if (t.prop('disabled')) {
                        t.prop('disabled', false);
                        t.val(t.data('val0'));
                    }
                });
            }
        } else {
            for (var i = controls.length - 1; i >= 0; i--) {
                check = controls[i];
                $("input[name='" + check + "']").each(function() {
                    var t = $(this);
                    if (!t.prop('disabled')) {
                        t.data('val0',t.prop('checked'));
                        t.prop('checked', false);
                        t.prop('disabled', true);
                    }
                });
                $('input[name=f_topic]').each(function() {
                    var t = $(this);
                    if (!t.prop('disabled')) {
                        t.data('val0',t.val());
                        t.val('');
                        t.prop('disabled', true);
                    }
                });
            }
        }
    });
}

function cmsmaster_call(idx, actionx, not_caller) {	
    // validation for updates
    if (actionx.startsWith('update')) {
        // tryb programu walidacji
        f_mode = $("input[name='f_mode']").val();
        if (f_mode == 'val_lesson_teacher') {
            f_state = 0;
            f_state_read = parseInt($("input[name='f_state']:checked").val());
            if (f_state_read)
                f_state += f_state_read;
            if (f_state < 2) {
                alert("Ustaw poprawny status lekcji");
                return;
            }
            topic = $('input[name=f_topic]').val();
            control = $('input[name=f_lesscontrol]').val();
            controls = control.split(',');
            total = 0;
            present = 0;
            for (var i = controls.length - 1; i >= 0; i--) {
                check = controls[i];
                total += $("input[name='" + check + "']:checked").length;
                present += $("input[name='" + check + "']:checked").filter(function() { return parseInt($(this).val()) == 1; }).length;
            };
            if (f_state == 2) {
                // topic
                if (topic.length == 0) {
                    alert(_("Podaj temat lekcji"));
                    return;
                }
                // obecnosc
                if (controls.length > 0) {
                    if (total != controls.length) {
                        alert(_("Zaznacz obecność dla wszystkich uczniów"));
                        return;
                    }
                    var maxp = parseInt($('input[name=f_maxp]').val());
                    if (present > maxp) {
                        alert(_("Maksymalnie ") + maxp + _(" uczniów może być obecnych na tej lekcji"));
                        return;
                    }
                }
            }
            if (f_state > 2) {
                if (topic.length > 0) {
                    alert(_("Nie można ustawić tematu dla odwołanej lekcji"));
                    return;
                }
            }
        }
        // alert("tryb testowy");
        // return;
    }
	if (not_caller) {
		not_caller = true;
	} else {
		not_caller = false;
	}
    // handle some special cases
    if (actionx == 'dbe_edit') {
        window.location = idx;
        return;
    }
    if (actionx == 'dbe_edit_nw') {
        var win = window.open(idx, '_blank');
        if (win) {
            //Browser has allowed it to be opened
            win.focus();
        } else {
            //Browser has blocked it
            alert(_('Proszę wyraź zgodę na otwieranie przez tę stronę nowych okien'));
        }
        return;
    }
	if (actionx == 'dbe_delete') {
		if (!confirm(message_del_element1))
			return;
	}
    // make sure we only send data ONCE
    if (already_called_check(!not_caller)) {
    	return false;
    }
	action_driven_exit = true;	
	//
	if (actionx == 'dbe_preview') {
		$('#'+formid).prop('target','_blank');
	}	
    // remove empty file boxes and other pre-send actions on template
    $t.presend();
    // next
    fck = false;
    $('.datepickerdate').attr('readonly', false);
    // this will fail on disabled
    try { 
        $('.xadvrel').each(function() {
            var th = $(this);
            var pa = th.siblings('input');
            var y = th.attr('title');
            var x = th.sortable("serialize", {
                attribute: 'title',
                expression: /(v)_(.+)/
            });
            pa.attr({
                name: y,
                value: x
            });
        });
    }
    catch (err) {
        // ignore
    }
    for(i=0; i<MAX_EDITORS; i++) {
    	if (ocodeeditors[i] != undefined) {
    		ocodeeditors[i].save();
    	}
    }
    if (actionx == 'dbe_preview') {
        $('#'+formid).prop('target','_blank');
    }
    document.getElementById(formid).action.value = actionx;
    document.getElementById(formid).id.value = idx;
    document.getElementById(formid).submit();
    return true;
}

function cmsmaster_relate_ud(name, id, zerodown) {
	return cmsmaster_call(name+''+id , "update_and_relate_"+((zerodown == 0)?"down":"up") );
}  

function cmsmaster_removefotom(id) {
    if (lc_confirm_del_pm())
    	return cmsmaster_call(id,"update,removefotom");
    return false;
}

function cmsmaster_removefoto(id) {
    if (lc_confirm_del_p())
    	return cmsmaster_call(id,"update,removefoto");
    return false;
}

function cmsmaster_updatefoto(id) {
	return cmsmaster_call(id,"update,updatefoto");
}

function cmsmaster_updatefotom(id) {
	return cmsmaster_call(id,"update,updatefotom");
}

function cmsmaster_foto_up(id) {
	return cmsmaster_call(id,"update,fotoup");
}

function cmsmaster_foto_down(id) {
	return cmsmaster_call(id,"update,fotodown");
}

function cmsmaster_clearfoto(id) {
    if (lc_confirm_del_p())
    	return cmsmaster_call(id,"update,clearfoto");
    return false;
}

function cmsmaster_removeobj(kind) {
    if (lc_confirm_del_p())
        return cmsmaster_call(kind,"update,removegraph");
    return false;
}

function cmsmaster_updateobj(kind) {
	return cmsmaster_call(kind,"update,updategraph");
}

function cmsmaster_addfoto() {
	return cmsmaster_call(0,"update,addfoto");
}

function cmsmaster_addfotos() {

    if ($('#outerUploaderContainer').is(":visible") && Y.Uploader.TYPE != "none" && !Y.UA.ios) {
        return;
    }
    
    if (already_called_check(false)) {
    	return;
    }
    
    $('#outerUploaderContainer').show();
        
    YUI({base:"template/_shared_/yui/",lang:"pl"}).use("uploader", function(Y) {
//        Y.one("#overallProgress").set("text", "Multiupload engine: " + Y.Uploader.TYPE);
        if (Y.Uploader.TYPE != "none" && !Y.UA.ios) { 
            var uploadUrl = Y.one("#t-site").get("value")+'.'+Y.one("#t-path").get("value").replace(/\//g,"-")+'.'+Y.one("#t-lang").get("value")+'/picture-upload';
            Y.Uploader.SELECT_FILES_BUTTON = '<a id="selectFilesButton" class="button fiolet" aria-label="{selectButtonLabel}" tabindex="{tabIndex}"><span>+</span>  {selectButtonLabel}</a>';
            var uploader = new Y.Uploader({
                    width: "125px", 
                    height: "21px", 
                    multipleFiles: true,
                    swfURL: "template/_shared_/yui/uploader/assets/flashuploader.swf",
                    uploadURL: uploadUrl,
                    simLimit: 2,
                    selectButtonLabel: _('Wybierz zdjęcia')
            });
            var uploadDone = false;
            
            if (Y.Uploader.TYPE == "html5") {
                uploader.set("dragAndDropArea", "body");
                
                Y.one("#ddmessage").setHTML("<strong>"+_('Możesz tu przeciągnąć i upuścić zdjęcia.')+"</strong>");   
                
                uploader.on(["dragenter", "dragover"], function (event) {
                        var ddmessage = Y.one("#ddmessage");
                        if (ddmessage) {
                            ddmessage.setHTML("<strong>"+_('Wykryto pliki, upuść je tutaj!')+"</strong>");
                            ddmessage.addClass("yellowBackground");
                        }
                });
                
                uploader.on(["dragleave", "drop"], function (event) {
                        var ddmessage = Y.one("#ddmessage");
                        if (ddmessage) {
                            ddmessage.setHTML("<strong>"+_('Możesz tu przeciągnąć i upuścić zdjęcia.')+"</strong>");
                            ddmessage.removeClass("yellowBackground");
                        }
                });
            } else {
                uploader.set("fileFilters", [{description:"Images", extensions:"*.jpg;*.png;*.gif"}]);
            }
            
            uploader.render("#selectFilesButtonContainer");
            
            uploader.after("fileselect", function (event) {
                    
                    var fileList = event.fileList;
                    var fileTable = Y.one("#filenames tbody");
                    if (fileList.length > 0 && Y.one("#nofiles")) {
                        Y.one("#nofiles").remove();
                    }
                    
                    if (uploadDone) {
                        uploadDone = false;
                        fileTable.setHTML("");
                    }
                    
                    var error_shown = false;
                    var to_remove = [];
                    Y.each(fileList, function (fileInstance) {
                        if (__tpl.pictures_left <= 0) {
                            if (!error_shown) {
                                error_shown = true;
                                alert(_('Przekroczono limit ilości zdjęć, obrazek') + ' ' + fileInstance.get("name") + ' ' + _('i kolejne nie zostaną dodane do kolejki wysyłki.'));
                            }
                            to_remove.push(fileInstance);
                        } else {
                            fileTable.append("<tr id='" + fileInstance.get("id") + "_row" + "'>" + 
                                "<td class='filename'>" + fileInstance.get("name") + "</td>" + 
                                "<td class='filesize'>" + fileInstance.get("size") + "</td>" + 
                                "<td class='percentdone'>"+_('Nie wysłane')+"</td>");
                            __tpl.pictures_left -= 1;
                        }
                    });
                    
                    // remove excessive files
                    Y.each(to_remove, function (fileInstance) {
                            // remove fileInstance from the upload list
                            fileList.splice(fileList.indexOf(fileInstance), 1);
                    });
            });
            
            uploader.on("uploadprogress", function (event) {
                    var fileRow = Y.one("#" + event.file.get("id") + "_row");
                    fileRow.one(".percentdone").set("text", event.percentLoaded + "%");
            });
            
            uploader.on("uploadstart", function (event) {
            		already_called_check(true);
                    uploader.set("enabled", false);
                    Y.one("#uploadFilesButton").addClass("yui3-button-disabled");
                    Y.one("#uploadFilesButton").detach("click");
            });
            
            uploader.on("uploadcomplete", function (event) {
                    var fileRow = Y.one("#" + event.file.get("id") + "_row");
                    var message = (event.data == '1') ? _("Zakończone!") : _("BŁĄD"); 
                    fileRow.one(".percentdone").set("text", message);
            });
            
            uploader.on("totaluploadprogress", function (event) {
                    Y.one("#overallProgress").setHTML(_("Wysłano:")+" <strong>" + 
                        event.percentLoaded + "%" + 
                        "</strong>");
            });
            
            uploader.on("alluploadscomplete", function (event) {
                    uploader.set("enabled", true);
                    uploader.set("fileList", []);
                    Y.one("#uploadFilesButton").removeClass("yui3-button-disabled");
                    Y.one("#uploadFilesButton").on("click", function () {
                            if (!uploadDone && uploader.get("fileList").length > 0) {
                                uploader.uploadAll();
                            }
                    });
                    Y.one("#overallProgress").set("text", _("Zdjęcia wysłane!"));
                    uploadDone = true;
                    
                    // reload
                    already_working_lock = false;
                    cmsmaster_update();
            });
            
            uploader.on("uploaderror", function (event) {
                    var fileRow = Y.one("#" + event.file.get("id") + "_row");
                    fileRow.one(".percentdone").set("text", _("BŁĄD!"));
            });
            
            
            Y.one("#uploadFilesButton").on("click", function () {
                    if (!uploadDone && uploader.get("fileList").length > 0) {
                        uploader.uploadAll();
                    }
            });      
        } 
        else {
            Y.one("#uploaderContainer").set("text", _("Przeglądarka nie obsługuje wysyłania") + ' ' + 
                _("wielu plików na raz."));
            
            return cmsmaster_call(0,"update,addfotos");
        }
        
        
    });

}

function cmsmaster_moveup() {
	return cmsmaster_call(0,"update,moveup");
}

function cmsmaster_movedown() {
	return cmsmaster_call(0,"update,movedown");
}

function cmsmaster_delete(what) {
    if (lc_confirm_del(what))                      
    	return cmsmaster_call(0,"delete");
    return false;
}

function cmsmaster_addtop() {
	return cmsmaster_call(0,"addtop");
}

function cmsmaster_update() {
	return cmsmaster_call(0,"update");
}

function cmsmaster_preview() {
	cmsmaster_call(0,"dbe_preview", true);
}

function cmsmaster_goto_lang(id) {
	return cmsmaster_call(id,"switch_lang");
}

function cmsmaster_add() {
	return cmsmaster_call(0,"add");
}            

var ocodeeditors = new Array();

$(document).ready(function()
{
	$(this).mousemove(function (e) {
        idle_time = 0;
    });
	
    $(this).keypress(function (e) {
        idle_time = 0;
    });
	
	$('#t-name').on('change', getDirty);
	$('#t-slug').on('change', getDirty);
	
	// any code editors?
	for (i=0; i<MAX_EDITORS; i++) {
		var pname = 'codeText' + i;
        var obj = $('#'+pname);
        if(obj.length > 0) {
        	var cdata = obj.data('data');
        	var mode = {
        		mode: "text/plain",
        		lineNumbers : true
        	};
        	if (cdata.params.mode == 'php') {
        		mode = {
        				matchBrackets: true,
        		        mode: "application/x-httpd-php",
        		        indentUnit: 4,
        		        indentWithTabs: true,
        		        enterMode: "keep",
        		        tabMode: "shift",
        		        lineNumbers : true
        		};
        	}
        	if (cdata.params.mode == 'xml') {
        		mode = {
        		        mode: "xml",        		        
        		        lineNumbers : true
        		};
        	}
        	if (cdata.params.mode == 'html') {
        		mode = {
        				mode: "text/html", 
        				tabMode: "indent",
        				lineNumbers : true
        		};
        	}        	
        	var cme = CodeMirror.fromTextArea(obj.get()[0], mode);		      
        	cme.setSize(cdata.params.cols * 7, cdata.params.rows * 11);
        	cme.setValue(cdata.data);
			cme.on("changes", getDirty);
            ocodeeditors[i] = cme;
        }
	}
	
    // any text editors?
    for (i=0; i<MAX_EDITORS; i++) {
        var pname = fck_defname + i;
        var obj = $('#'+pname);
        if (obj.length > 0) {
            var elem0 = obj.first();
            // start fck ?
            var rws = parseInt(elem0.attr('rows'));
            if (rws <= 0)
                rws = 3;
            if (elem0.hasClass('editor')) {
//                var oFCKeditor = new CKeditor( pname ) ;
                var options = {};
                
                if (i==0) {
                    options.height   = 93+(rws*16);
                    options.toolbar = fck_tb_main;
                    fck = true;
                } else {
                    options.height   = 63+(rws*16);
                    options.toolbar = fck_tb_basic;
                }
                if (fckeditor_bodyid.length) {
                    options.bodyId = fckeditor_bodyid;
                }
                if (fckeditor_bodyclass.length) {
                    options.bodyClass = fckeditor_bodyclass;
                }
                
                options.language = ckeditor_language_code;
                options.contentsLanguage = cms_contents_language_code;
                
                var oFCKeditor = CKEDITOR.replace( pname, options ) ;
                
                // ensure images work properly
                oFCKeditor.on('mode', function(e){
                        if (e.editor.mode == 'wysiwyg') {
                                var doc = $(e.editor.container.$).find('iframe')[0].contentWindow.document;
                                $(doc.head).append('<base href="../" />');
                                $(doc).find('img').each(function(i, o){o.src = o.src})
                        }
                });
                
                // save button
                oFCKeditor.on('beforeCommandExec', function(e){
                        if (e.data.name === 'save') {
                                cmsmaster_update();
                        }
                });
                
                if (fckeditor_editorcss.length) {
                    oFCKeditor.config["contentCss"] = fckeditor_editorcss;
                }
                oFCKeditor.BasePath = fck_basepath ;

                ofckeditors[i] = oFCKeditor;
            }
        } else {
            if (i>0)
                break;
        }
    }

	$("#guide-close").click(function() {
	  $("#guide").hide();
	});
	
	$("#browse-button").click(function() {
	  $("#guide").hide();
	});

	$("#new-button").click(function() {
	  cmsmaster_add();
	});
	
});
