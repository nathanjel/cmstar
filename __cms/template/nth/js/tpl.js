var __tpl = function() {};

__tpl.prototype = {
    // display message
    // mclass = 1 - error
    // mclass = 2 - info
    // mclass = 3 - success
    sites : new Array(),
    menu : new Array(),
    hide : false,
    ecnt : 1,
    ccnt : 0,
    selectedTab : '',
    ftab : '',
    cajax : null,
    langvisible : false,
	pictures_left : 0,

    addLoginLang : function(lcode, lang, select){
    	var obj = $("#t-lang-select");
    	var obj2 = $('<option value="'+lcode+'" '+(select?'selected':'')+' >'+lang+'</option>');
    	obj.append(obj2);
    },
    
    addType : function(block, name, type) {
    	var obj = $('div.t-' + block + ' select.typeselect').first();
    	var obj2 = $('<option value="'+type+'">'+name+'</option>');
    	obj.append(obj2);
    },
    
    addLang : function(id, name, curr, avail) {
    	var obj = $('#t-lang-select');
    	var obj2 = $('<option value="'+id+'">'+name+'</option>');
    	if (avail) {
    		obj2.addClass('lang-available');
    	}
    	if (curr) {
    		obj2.addClass('lang-current');
    	}
    	obj.append(obj2); 
		var obj_ = $('#t-lang-select2');
    	var obj2_ = $('<option value="'+id+'">'+name+'</option>');
    	if (avail) {
    		obj2_.addClass('lang-available');
    	}
    	if (curr) {
    		obj2_.addClass('lang-current');
    	}
    	obj_.append(obj2_);     	
    	this.langvisible = true;
    },

    addField : function(fdef) {
        /*
        'parentid' => $parentid,
        'label' => $fieldlabel,
        'name' => $fieldname,
        'type' => $fieldtype,
        'params' => $fieldtypepara,
        'data' => $fielddata
        */
        if (fdef.data == null) {
            fdef.data = '';
        }
        var obj = $('<div/>');
        switch(fdef.type) {
            case 'text':
            case 'texti':
            case 'textf':
            case 'textic':
            case 'code':
            case 'textarea':
            case 'textareaeditormini':
            case 'simplecheckbox':
            case 'simpleselect':
            case 'radio':
            case 'multiselectbox':
            case 'multicheckbox':
            case 'file':            
            case 'table':
            case 'pass':
            case 'password':
            case 'button':
            case 'date':
            case 'advrel':
			case 'tag':
            case 'span':
            case 'spanhtml':               
                obj = $($('#stab-field').html());
                $('.hrfn', obj).text(fdef.label);
                $('.mrfn', obj).text(fdef.name);
                break;
            default:
                break;
        };
        switch(fdef.type) {
            case 'hidden':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'hidden',
                    value : fdef.data 
                });
                obj.append(so);
                break;

            case 'calendar':
            case 'autolist':
                var p = $('<div/>');
                if (fdef.type == 'calendar') {
                    if (fdef.params.clickable) {
                        fdef.params.eventClick = function (ev, jsev, view) {
                            if (ev.link)
                                window.location = ev.link;
                            else 
                                alert('Ta lekcja nie została jeszcze utworzona');
                        }
                    }
                    fdef.params.eventRender = function (ev, elem) {
                        elem.prop('title', ev.__tooltip);
                    }
                    p.addClass('calendarNeedsRefresh');
                    p.fullCalendar(fdef.params);
                } else {
                    p.ajaxtable(fdef.params);
                }
                if (fdef.params.boxed) {
                    obj = $($('#stab-field').html());
                    $('.hrfn', obj).text(fdef.label);
                    $('.mrfn', obj).text(fdef.name);
                    $('.cl_ftd', obj).append(p);
                } else {
                    obj = p;
                }
                break;

            case 'button':
            var so = $('<input/>').attr({
                type : 'button',
                value : fdef.data,
                'class' : 'input_button'
            });
            so.data('action',fdef.params.action);
            so.data('aid',fdef.params.id);
            so.data('nc',fdef.params.not_caller?true:false);
            so.click(function() {
                var p = $(this);
                cmsmaster_call(p.data('aid'),p.data('action'),p.data('nc'));
            });
            var par = [];
            if (fdef.params.group != undefined) {
                var pg = 'paragg-'+fdef.params.group;
                par = $('input.'+pg);
                so.addClass(pg);
            }
            if (par.length) {
                par.last().after(so);
                return;
            } else {
                $('.cl_ftd', obj).append(so);
            }
            break;
            case 'visual':
                obj = $($('#stab-visual').html());
                $('.LABEL', obj).text(fdef.label);
                $('.input_file', obj).attr({
                    name : 'graphxx_' + fdef.params.kind
                });
                $('img.DIMAGE', obj).attr({
                    alt : fdef.params.type + ' ' +fdef.params.size + ' '+fdef.params.dimensions,
                    src : '../__i/-100/-100/./__cms/' + fdef.params.min
                });
                $('a.DLINK', obj).attr({
                    rel : 'prettyPhoto',
                    href: fdef.params.src,
                    title: fdef.label
                }).append('<br/>').append(fdef.params.type + ' ' +fdef.params.size + ' '+fdef.params.dimensions
                ).append('<br/>').append(
                fdef.params.limit
                );
                $('img[class^="b_b"]', obj).css('cursor','pointer').data('kind',fdef.params.kind);
                $('.b_bdel', obj).click(function() {
                    cmsmaster_removeobj($(this).data('kind'));
                });
                $('.b_bsend', obj).click(function() {
                    cmsmaster_updateobj($(this).data('kind'));
                });
                break;
            case 'link':
                obj = $($('#stab-link').html());
                $('.LABEL', obj).text(fdef.label);
                $('.CNT', obj).attr({
                    name : fdef.name,
                    value : fdef.data
                });
                break;
			case 'tag':
				var so = $('<ul/>').attr({
					id : fdef.name
				});
				if (fdef.params.data)
					while(fdef.params.data.length) {
						var li = $('<li/>').text(fdef.params.data.pop());
						so.append(li);
					}
				$('.cl_ftd', obj).append(so);
				$(so).tagit({
					fieldName: fdef.name + '[]',
					allowDuplicates: !fdef.params.uniq,
					tagLimit: fdef.params.n,
					autocomplete: {
						source: fdef.params.tfp + '/rac-search-tag?relation=f_tag'
					},
					afterTagAdded: getDirty,
					afterTagRemoved: getDirty
				});
				break;
            case 'date':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    readonly : fdef.params.inputreadonly == null?false:true,
                    value : fdef.data,
                    type : 'text',
                    'class' : 'text datepickerdate',                    
                });
                $('.cl_ftd', obj).append(so);
                $(so).DatePicker({
                    format: fdef.params.format,
                    date: fdef.params.date == null?'':fdef.params.date,
                    starts: 1,
                    calendars: fdef.params.mode!='single'?3:1,
                    mode: fdef.params.mode,
                    locale: date_picker_locale(),
                    onChange: function(formated, dates) {
						getDirty();
                        var pstr = '' + formated;
                        if (!pstr.match(/Na/))
                            $('input[name='+fdef.name+']').val(pstr);
                    }
                });
                break;
            case 'picture':
                obj = $($('#stab-photo').html());
                $('.SEQ', obj).text(fdef.params.ord + ' / ' + fdef.data);
                $('.PLABEL', obj).text(fdef.params.photolabel);
                $('a.PLINK', obj).attr({
                    rel : 'prettyPhoto[mpg]',
                    href: fdef.params.src,
                    title: fdef.params.photolabeltext
                }).append('<br/>').append(fdef.params.type + ' ' +fdef.params.size + ' '+fdef.params.dimensions
                ).append('<br/>').append(
                fdef.params.limit
                );
                $('img.PMINI', obj).attr({
                    alt : fdef.params.type + ' ' +fdef.params.size + ' '+fdef.params.dimensions,
                    src : '../__i/-100/-100/./__cms/' + fdef.params.src
                });
                $('.DLABEL', obj).text(fdef.params.photolabellabel);
                $('.DESC', obj).attr({
                    name : 'filelabel'+fdef.name
                }).val(fdef.params.photolabeltext);
                $('.input_file', obj).attr({
                    name :'pxxfile'+fdef.name
                });
                $('img[class^="a_b"]', obj).css('cursor','pointer').data('id', fdef.name);
                $('.a_bkosz', obj).click(function() {
                    cmsmaster_removefoto($(this).data('id'));
                });
                $('.a_bup', obj).click(function() {
                    cmsmaster_foto_up($(this).data('id'));
                });
                $('.a_bdn', obj).click(function() {
                    cmsmaster_foto_down($(this).data('id'));
                });
                $('.a_bsend', obj).click(function() {
                    cmsmaster_updatefoto($(this).data('id'));
                });
                break;
            case 'span':
                var so = null;
                if (fdef.params.link) {
                    so = $('<span/>');
                    link = $('<a/>').text(fdef.data);
                    link.attr('href', fdef.params.link);
                    so.append(link);
                } else {
                    so = $('<span/>').text(fdef.data);
                }
                $('.cl_ftd', obj).append(so);
                break;
            case 'spanhtml':
                var so = $('<span/>').html(fdef.data);
                $('.cl_ftd', obj).append(so);
                break;
            case 'pass':
            case 'password':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'password',
                    size : 20,
                    value : fdef.data 
                });
                $('.cl_ftd', obj).append(so);
				so.on('change', getDirty);
                break;
            case 'text':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'text',
                    size : 76,
                    value : fdef.data 
                });
                $('.cl_ftd', obj).append(so);
				so.on('change', getDirty);
                break;
            case 'texti':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'text',
                    size : 10,
                    value : fdef.data
                });
                so.change(function() {
                    var orgv = $(this).val();
                    var iv = parseInt($(this).val());
                    if (isNaN(iv))
                        iv = 0;
                    $(this).val(iv);
                    if (orgv != iv) {
                        alert(_("Ostatnio podana wartość liczbowa została poprawiona."));
                    }
                });
                $('.cl_ftd', obj).append(so);
				so.on('change', getDirty);
                break;
            case 'textf':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'text',
                    size : 10,
                    value : fdef.data 
                });
                so.change(function() {
                    var orgv = $(this).val();
                    var iv = parseFloat($(this).val());
                    if (isNaN(iv))
                        iv = 0;
                    $(this).val(iv);
                    if (orgv != iv) {
                        alert(_("Ostatnio podana wartość liczbowa została poprawiona."));
                    }
                });
                $('.cl_ftd', obj).append(so);
				so.on('change', getDirty);
                break;
            case 'textic':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'text',
                    'class' : 'textic',
                    size : 10,
                    value : fdef.data,
                    readonly : true
                });                
                so.change(function() {
                    var iv = parseInt($(this).val());
                    if (isNaN(iv))
                        iv = 0;
                    $(this).val(iv);
					getDirty();
                });
                so.attr('title',fdef.params.cfname);
                so.click(function(){
                    if (confirm('Czy chcesz zmienić zawartość tego pola')) {                        
                        var p = $(this);
                        p.unbind('click');
                        p.attr({
                            readonly : false
                        });
                        p.removeClass('textic');
                        var cfname = so.attr('title');
                        var fname = so.attr('name');
                        $('<input type="hidden" name="'+cfname+'" value="'+fname+'"/>').appendTo(p.parent());
                    }
                });
                $('.cl_ftd', obj).append(so);
                break;
            case 'code':
            	var par = $('.cl_ftd', obj);
                var so = $('<textarea/>').attr({
                    name : fdef.name,
                    id : 'codeText' + (this.ccnt++)                    
                });
                par.removeClass("ramka");
                so.data('data',fdef);
                so.appendTo(par);				
                break;
            case 'textarea':
                var so = $('<textarea/>').attr({
                    name : fdef.name,
                    rows : fdef.params.rows,
                    cols : fdef.params.cols
                });
                so.val(fdef.data);
                $('.cl_ftd', obj).append(so);
				so.on('change', getDirty);
                break;
            case 'textareaeditorfull':
                obj = $($('#stab-textareaeditorfull').html());
                var so = $('<textarea/>').attr({
                    name : fdef.name,
                    rows : 50,
                    cols : 78,
                    'class' : 'editor',
                    id : 'contentText0'
                });
                so.val(fdef.data);
                $('.cl_data', obj).append(so);
                break;
            case 'textareaeditormini':
                var so = $('<textarea/>').attr({
                    name : fdef.name,
                    rows : fdef.params.rows,
                    cols : fdef.params.cols,
                    'class' : 'editor',
                    id : 'contentText' + (this.ecnt++)
                });
                so.val(fdef.data);
                $('.cl_ftd', obj).append(so);
                break;
            case 'simplecheckbox':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'checkbox',
                    value : '1',
                    checked : fdef.data.length > 0
                });
                $('.cl_ftd', obj).append(so);
				so.on('change', getDirty);
                break;
            case 'simpleselect':
            var so = $('<select/>').attr({
                name : fdef.name
            });
            if (fdef.params.size) {
                so.attr('size',fdef.params.size);
            }
            for(k=0;k<fdef.params.k.length;k++) {
                $('<option/>').text(fdef.params.v[k]).attr({
                    'value':fdef.params.k[k],
                    'selected':fdef.data == fdef.params.k[k]
                }).appendTo(so);
            }
            $('.cl_ftd', obj).append(so);
			so.on('change', getDirty);
            break;
            case 'radio':
            for(k=0;k<fdef.params.k.length;k++) {
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'radio',
                    'value':fdef.params.k[k],
                    'checked':fdef.data == fdef.params.k[k]
                });
                var st = $('<span/><br/>');
                st.first().text(fdef.params.v[k]);
                $('.cl_ftd', obj).append(so);
                $('.cl_ftd', obj).append(st);
				so.on('change', getDirty);
            }
            break;

            case 'advrel':
                var no = $('#stab-advrel').clone();
                no.attr('id','');
                var co = no.find('.dlist');
                var so = no.find('.slist');
                co.attr('title',fdef.name);
                co.addClass('xadvrel');
                so.addClass('xsender');
                so.addClass('data-'+fdef.name);
                co.addClass('data-'+fdef.name);
                so.data({
                    uniq: fdef.params.uniq,
					liveupdate: fdef.params.liveupdate,
                    n: fdef.params.n,
                    d: co
                });
                for(var ob in fdef.params.data) {
                    var p1 = fdef.params.data[ob];
                    co.append('<li title="v_'+p1.k+'" class="ilev'+p1.l+'">'+p1.v+'</li>');
                }
                $('.sfield',no).data('rid',fdef.params.tfp);
                $('.sfield',no).keyup(function(){
                    var st = $(this);
                    if (st.data('xhr') != null)
                        st.data('xhr').abort();
                    st.data('xhr',$.ajax({
                       url: $(this).data('rid') + '/rac-search',
                       dataType: 'json' ,
                       data: {
                           search: $(this).val(),
                           relation: fdef.params.relationdef
                       },
                       context: this,
                       success: function(data) {
                           var m = $(this).parent().parent();
                           $('.slist',m).children().remove();
                           var n = $('.slist',m);
                           for(var ob in data) {
                               var p1=data[ob];
                               n.append('<li title="v_'+p1.k+'" class="ilev'+p1.l+'">'+p1.v+'</li>');
                           }
                           $('.slist',m).sortable('refresh');
                           $(this).data('xhr',null);
                       },
                       error: function(a,b,c) {
                           $(this).data('xhr',null);
                       }
                    })
                    );
                });
                if (fdef.params.disabled != 1) {
                    $('.slist, .dlist', no).sortable({
                        connectWith: ".data-"+fdef.name,
                        receive: function(event, ui) { 
    						getDirty();
                            var p = $(ui.sender);
                            if (p.hasClass('xsender')) {
                                var uni = p.data('uniq');
                                var n = p.data('n');
                                var c = p.data('d').children('li').size();
                                if (c > n) {
                                    alert('Nie można dodać. Przekroczono określony limit ' + n);
                                    $(ui.sender).sortable('cancel');
                                    return;
                                }
                                if (uni) {
                                    var t = $(ui.item).attr('title');
                                    if (p.data('d').children('li[title="'+t+'"]').size() > 1) {
                                        alert('Nie można dodać. Taki element już jest na liście.');
                                        $(ui.sender).sortable('cancel');
                                        return;
                                    }    
                                }
    							// lajf apdejt
    							var ludata = p.data('liveupdate');							
    							if (Array.isArray(ludata)) {
    								var pacjent = p.data('d').children('li').first().attr('title');
    								pacjent = pacjent.replace('v_','').replace(/:/g,'.').replace(/\//g,'-');
    								pola = '';
    								for(var j = 0; j<ludata.length; j++) {
    									pola += ludata[j].s;
    									pola += ','; 
    								}
    								$('body').css('cursor','wait');
    								$.ajax({
    			                       url: pacjent + '/dump',
    			                       dataType: 'json' ,
                			           data: {
                            			   fields: pola
    			                       },
                			           context: p,
    			                       success: function(data) {
                			               var lu = $(this).data('liveupdate');
                			               for(var j = 0; j<lu.length; j++) {
                            			       var src = lu[j].s;
    										   var tar = lu[j].t;
    										   $("input[name='"+tar+"']").val(data[src]);
    										   $("textarea.editor[name='"+tar+"']").each(function(index,elem) {
    											   $(elem).val(data[src]);
    											   CKEDITOR.instances[$(elem).attr('id')].setData(data[src]);
    										   });
                			               }
    									   $('body').css('cursor','default');
            			               },
                        			   error: function(a,b,c) {
    			                           // alert(a + b + c);
    									   $('body').css('cursor','default');
                			           }
    			                    });								
    							}
                            }
                        }
                    });
                    $('.sfield',no).trigger('keyup');
                }
                $('.cl_ftd', obj).append(no);
            break;

            case 'multiselectbox': 
            var so = $('<select/>').attr({
                name : fdef.name+'[]',
                multiple : true
            });
            so.attr('size',fdef.params.size?fdef.params.size:6);			
            for(k=0;k<fdef.params.k.length;k++) {
                $('<option/>').text(fdef.params.v[k]).attr({
                    'value':fdef.params.k[k],
                    'selected':fdef.params.s[k]
                }).appendTo(so);
            }
            $('.cl_ftd', obj).append(so);
			so.on('change', getDirty);
            break;
            
            case 'multicheckbox':
            for(k=0;k<fdef.params.k.length;k++) {
                var so = $('<input/>').attr({
                    name : fdef.name + ':' + fdef.params.k[k],
                    type : 'checkbox',
                    'value': 'X',
                    'checked': fdef.params.s[k]
                });
                var st = $('<span/><br/>');
                st.first().text(fdef.params.v[k]);
                $('.cl_ftd', obj).append(so);
                $('.cl_ftd', obj).append(st);
				so.on('change', getDirty);
            }
            break;
            
            case 'file':
                var so = $('<input/>').attr({
                    name : fdef.name,
                    type : 'file',
                    'class' : 'input_file',
                    size : 40,
                    value : fdef.data 
                });
                var des = $('<div><a target="_blank" href="../__f/'+fdef.params.downloadkey+'">'+fdef.params.filename+ ' (' +  fdef.params.filetype + ' ' + fdef.params.filesize+'kB)</a></div>');
                $('.cl_ftd', obj).append(so);
                if (fdef.params.downloadkey.length) {
                    $('.cl_ftd', obj).append(des);
					var des2 = $('<div style="clear:both;"><input name="delfile_'+fdef.name+'" value="x" type="checkbox">'+_('Skasuj')+'</div>');
    	            $('.cl_ftd', obj).append(des2);
				}
			    so.on('change', getDirty);
                break;
            case 'table':
            var st = $('<table/>');
            for(r=0;r<fdef.params.rows;r++) {
                var sr = $('<tr/>');
                for(c=0;c<fdef.params.cols;c++) {
                    var sd = $('<td/>');
                    var so = $('<input/>').attr({
                        name : 't2d_'+fdef.name+'_r'+r+'c'+c,
                        type : 'text',
                        size : 8,
                        value : fdef.data[r][c]
                    });
                    so.appendTo(sd);
                    sd.appendTo(sr);
					so.on('change', getDirty);
                }
                sr.appendTo(st);
            }
            $('.cl_ftd', obj).append(st);
            break;
            default:
                return;
        }
        if(fdef.type != 'hidden' && fdef.params.disabled) {
            $('input', obj).add('select', obj).add('textarea', obj).prop('disabled', 'disabled');
        }
        var parid1 = '#cms_tid_d_'+fdef.parentid;
        var parid2 = '#cms_gid_'+fdef.parentid;
        var parent = null;
        if ($(parid2).length) {
            parent = $(parid2);
            parent.nextAll('.cl_fttend').first().before(obj);
        } else if ($(parid1).length) {
            parent = $(parid1);
            parent.append(obj);
        } else {
            parent = $('#cmsmasteredit');
            parent.append(obj);
        }
    },
    
    markFieldBad : function(fname, msg) {
    	$('*[name="'+fname+'"]').addClass('fielderror').parent().append('<br/>&nbsp;<span class=\"fielderrortxt\">'+msg+'</span>');
    },

    addTab : function(tabid, tabname) {
        if (this.ftab == '') {
            this.ftab = tabid;
        }
        var stc = $('#stab').first().clone();
        stc.attr('id', 'cms_tid_t_'+tabid);
        stc.children().first().text(tabname);
        stc.data('tid', tabid);
        stc.click(function() {
            tabSwitch($(this));
        });
        stc.appendTo('#omenu');
        stc.show();
        var st = $('#stabdata').first();
        var std = st.clone();
        std.attr('id', 'cms_tid_d_'+tabid);
        st.after(std);
    },

    addGroup : function(tabid, groupid, groupname) {
        var stg1 = $($('#stab-group').html());
        var stg2 = $($('#stab-group-end').html());
        stg1.attr('id', 'cms_gid_'+groupid);
        $('.cl_fss', stg1).text(groupname);
        var st = $('#cms_tid_d_'+tabid);
        st.append(stg1);
        st.append(stg2);
    },

    selectTab : function(tabid) {
        this.selectedTab = tabid;
        tabSwitch($('#cms_tid_t_'+tabid));
    },

    tabsComplete : function() {},

    groupsComplete : function() {},

    displayMessage : function (number, mclass, mtext) {
        var c1 = $('#message').first();
        c0 = c1.clone();
        c0.append(mtext).attr({id: ''});
        if (mclass == 1) {
            c0.addClass('msg_fail');
        } else {
            c0.addClass('msg_ok');
        }
        c1.after(c0);
        c0.show();
    },

    // set parameter
    set : function (field, value) {
        switch(field) {
            case 'page-title':
                document.title = value;
                break;
            case 'cms-logo-alt':
                $('#t-cms-logo').attr('alt', value);
                break;
            case 'save-visible':
            case 'up-visible':
            case 'down-visible':
            case 'new-child-visible':
            case 'new-sibling-visible':
            case 'delete-visible':
            case 'preview-visible':
		var pval = (((value=='true') || (value==1)) ? true:false);
            if (pval) {
                $('.t-'+field).show();
            } else {
                $('.t-'+field).hide();
            }
            break;
            case 'new-picture-visible':
            var st0 = $('#cms_tid_d_'+'photo');
            var st1 = st0.children().first();
            var stg1 = $('#stab-photoadd').html();
            if ((st0.length || st1.length) && value) {
                if (st1.length) {
                    st1.before(stg1);
                } else {
                    st0.append(stg1);
                }
            }
            break;
            case 'name-editable':
            if (value != 'true') {                    
                $('#t-name').attr('readonly', true).css('background-color', '#eee');
            }
            break;
            case 'name':
            case 'site':
            case 'path':
            case 'lang':
            case 'slug':
            case 'deftx':
                $('#t-'+field).attr('value', value);
                break;
            case 'fckeditor_bodyid':
                fckeditor_bodyid = value;
                break;
            case 'fckeditor_bodyclass':
                fckeditor_bodyclass = value;
                break;
            case 'fckeditor_editorcss':
                fckeditor_editorcss = value;
                break;
			case 'patht':
				var xxlink = $('<A></A>');
				xxlink.attr('href', value).text(value);
				xxlink.attr('target', '_blank');
				$('#t-'+field).text('').append(xxlink);
				break;
			case 'pictures-left':
				this.pictures_left = 0;
				break;
			case 'auto_save_on_idle':
				setauto_save_on_idle(value);
				break;
            default:
                $('#t-'+field).text(value);
        }
    },

    hc : function() {
        this.hide = true;
        $('.template').hide();
        $('.act').hide();
    },

    addMenu : function(parentpath, mypath, mylevel, itemname, selected) {
        this.menu[this.menu.length] = {
            parent : parentpath,
            path : mypath,
            level : mylevel,
            name : itemname,
            selected : selected
        };
    },

    menuComplete : function() {
        var menuc = $('#menulewe_end').first();
        var k = this.menu.length;
        var ll = -1;
		var tmpStr;
        for(i=0; i<k; i++) {
            var item = this.menu[i];
			tmpStr = item.name.slice(0,4);
			tmpStr= tmpStr.split(' ').join('-');
            var menus = $("<div class='mm mml"+item.level+(i==(k-1)?'f':(item.level>this.menu[i+1].level?'f':''))+(item.selected?' mms':'')+" " +tmpStr+"'><i class='ico'></i><a href='"+item.path.replace(/\//g,'-')+"'> </a></div>");
            menus.children().last().text(item.name);
            menuc.before(menus);
        }
    },

    addSite : function(siteid, sitename, selected) {
        var p = this.sites.length;
        this.sites[p] = {
            id : siteid,
            name : sitename,
            selected : selected  
        };
    },

    sitesComplete : function() {
        var sajty = $('#sajty');
        var prev = '-';
        var curr = '-';
        for(k=0; k<this.sites.length; k++) {
            var sel = this.sites[k].selected;
            prev = curr;
            curr = (sel?'y':'n');
            /*sajty.append("<div class='sites-"+prev+curr+"'></div>");*/
            sajty.append("<div class='sites-"+curr+"'><span>"+this.sites[k].name+"</span></div>");
            if (!sel) {
                var ob = sajty.children().last();
                ob.css('cursor','pointer');
                ob.data('targetid',this.sites[k].id)
                ob.click(function() {
                    var tid = $(this).data('targetid');
                    window.location = ''+tid+'.0';
                });
            }
        }
        prev = curr;
        curr = '-';
        /*sajty.append("<div class='sites-"+prev+curr+"'></div>");*/
    },

    breadcrumb : function(names, paths) {
        var bc = $('#breadcrumb');
		var headh1 = $('#entry-name-h');
        for(i=0; i<names.length; i++) {
            if (i == (names.length-1)) {
                bc.append("<span class='currentItem'>"+names[i]+"</span>");
				headh1.append(names[i]);
            } else {
                bc.append("<a href='"+paths[i].replace(/\//g, '-')+"'>"+names[i]+"</a> » ");
				
            }
        }
			
			 
    },

    finish : function() {
        // hide typeselect if there is only one type
        $('select.typeselect').each(function() {
            var p = $(this);
            if (p.children('option').length==1)
                p.hide();
        });
        $("#whiteContainer").show();
        if (this.selectedTab == '' && this.ftab != '') {            
            tabSwitch($('#cms_tid_t_'+this.ftab));
        }
        if (this.langvisible) {
        	var obj = $('#t-lang-select');
        	obj.change(function() {
        		cmsmaster_goto_lang($('#t-lang-select').val());
        	});
			var obj = $('#t-lang-select2');
        	obj.change(function() {
        		cmsmaster_goto_lang($('#t-lang-select2').val());
        	});
        } else {
        	var obj = $('#t-lang-select-all');
        	obj.css('display', 'none');
        }
        // callback the master template function
        cmsmaster_late_initialization();
    },
    
    presend : function() {
        $('input[type=file]').filter(function() {
            return ($(this).val() == '');
        }).remove();
    }
};

var $t = new __tpl();
var fckeditor_bodyid = '';
var fckeditor_bodyclass = '';
var fckeditor_editorcss = '';

var ctid;

function tabSwitch(jqtabobj) {
    ctid = jqtabobj.data('tid');
    $('#t-deftx').val(ctid);
    $('.object').filter(function() {
        return $(this).attr('id') != 'cms_tid_d_'+ctid;
    }).hide();
    $('#cms_tid_d_'+ctid).show();
    $('.otab').filter(function() {
        return $(this).attr('id') != 'cms_tid_t_'+ctid;
    }).each(function() {
        $(this).removeClass('tab_a');
        $(this).addClass('tab_p');
    });
    $('#cms_tid_t_'+ctid).removeClass('tab_p');
    $('#cms_tid_t_'+ctid).addClass('tab_a');
    for(k=0;k<ocodeeditors.length;k++)
    	    ocodeeditors[k].refresh();
    $('.calendarNeedsRefresh').fullCalendar('render');
}
