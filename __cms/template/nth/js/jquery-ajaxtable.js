(function( $ ){

    var methods = {
        init : function( options ) {

            return this.each(function(){

                var $this = $(this),
                data = $this.data('ajt');

                // If the plugin hasn't been initialized yet
                if ( ! data ) {
                    var rowlen = options.columns.length;
                    // initialize table
                    if (options.searchfields) {
                        for(var p = 0; p < options.searchfields.length; p++) {
                            var sfn = options.searchfields[p];
                            var sfd = '';
                            for(i=0; i<rowlen; i++) {
                                if (options.columns[i].fn == sfn)
                                    sfd = options.columns[i].name;
                            }
                            var inp = $('<input/>');
                            inp.attr({
                                name: 's_'+sfn,
                            });
                            inp.data('data',$this);
                            inp.keyup(function(){
                                $.typewatch(function(){
                                    $this.ajaxtable('search');
                                }, 350);
                            });                        
                            $this.append(inp);
                            inp.before('<span>'+sfd+': </span>');
                        }
                    }
                    var sinp = $('<input/>');
                    sinp.attr({
                        name: 'sort_',
                        type: 'hidden'
                    });
                    $this.append(sinp);
                    var div = $('<div/>');
                    div.css({
                        width: '100%',
                        height: '450px',
                        overflow:'auto'
                    })
                    $this.append(div);
                    var obj = $('<table cellspacing="0" />');
                    div.append(obj);

                    $this.addClass('ajaxtable');
                    obj.addClass('stdtable');
                    var tr = $('<tr/>');
                    tr.addClass('stdhead')
                    for(i=0; i<rowlen; i++) {
                        var th = $('<th/>');
                        th.text(options.columns[i].name);
                        if (options.columns[i].sf) {
                            th.data('target',{
                                target: $this,
                                inp: sinp,
                                sfn: options.columns[i].fn
                            });
                            th.addClass('sortable');
                            th.css('cursor','pointer');
                            th.click(function() {
                                var tho = $(this);
                                tho.siblings().removeClass('sort-up').removeClass('sort-down');
                                var thd = tho.data('target');
                                if (tho.hasClass('sort-up') || tho.hasClass('sort-down')) {
                                    tho.toggleClass('sort-up');
                                    tho.toggleClass('sort-down');
                                } else {
                                    tho.toggleClass('sort-up');
                                }
                                thd.inp.attr({
                                    name: 'sort_' + thd.sfn,
                                    value: tho.hasClass('sort-up')?'up':'down'
                                });
                                thd.target.ajaxtable('search');
                            });
                        }
                        tr.append(th);
                    }
                    var udcol = false;
                    var accol = false;
                    if (options.button_up || options.button_down) {
                        udcol = true;
                        var th = $('<th/>');
                        th.text('Porządek');
                        tr.append(th);
                    }
                    if (options.button_edit || options.button_delete || options.button_preview || options.button_save || options.button_duplicate || (options.extra.length > 0)) {
                        accol = true;
                        var th = $('<th/>');
                        th.text('Akcje');
                        tr.append(th);
                    }
                    obj.append(tr);
                    rowlen += 1;
                    var datarowlen = rowlen;
                    if (udcol) rowlen += 1;
                    if (accol) rowlen += 1;

                    $this.data('ajt', {
                        target : $this,
                        params : options,
                        table : obj,
                        sinp : sinp,
                        headrow : tr,
                        udcol : udcol,
                        accol : accol,
                        rowlen : rowlen,
                        datarl : datarowlen,
                    });

                    if (options.rows) {
                        $this.ajaxtable('filltable', options.rows);
                    } else {
                        $this.find('.sortable').first().click();
                    }                

                }
            });
        },
        destroy : function( ) {

            return this.each(function(){

                var $this = $(this),
                data = $this.data('ajt');

                // Namespacing FTW
                $(window).unbind('.ajaxtable');
                data.table.remove();
                $this.removeData('ajt');

            })

        },
        search : function() {
            return this.each(function(){

                var $this = $(this),
                data = $this.data('ajt');

                if ($this.data('xhr') != null)
                    $this.data('xhr').abort();
		$('body').css('cursor','wait');
                $this.data('xhr',$.ajax({
                    url: data.params.path + '/dbe-list',
                    dataType: 'json' ,
                    data: $('input',$this).serializeArray(),
                    context: this,
                    success: function(data) {
                        $(this).data('xhr',null);
                        $(this).ajaxtable('filltable', data);
			$('body').css('cursor','default');
                    },
                    error: function(a,b,c) {
                        $(this).data('xhr',null);
			$('body').css('cursor','default');
                        alert('Połączenie z bazą nieudane. Prosimy spróbować ponownie za chwilę.');
                    }
                })
                );
            })
        },
        genclickfun : function(ev) {
                                        if(ev.target.matches('a')) {
                                            return true;
                                        }
                                        ev.stopPropagation();
                                        var p = $(this).data('aid');
                                        cmsmaster_call(p.id, p.action + (ev.ctrlKey?'_nw':''), false);
                                    },
        filltable : function ( rows ) {
            return this.each(function(){

                var $this = $(this),
                data = $this.data('ajt');                

                data.table.children().children().first().nextAll().remove();

                for(i=0; i<rows.length; i++) {
                    tr = $('<tr/>');
                    var row = rows[i];
                    var rid = 0;
                    var udcolx = data.udcol;
                    var accolx = data.accol;
                    if (row)
                    for(j=0; j<data.rowlen; j++) {                    
                        var td = $('<td/>');
                        if (j == 0) {
                            rid = row[j];
                            continue;
                        }
                        if (j >= data.datarl) {
                            if (udcolx) {
                                // updown
                                udcolx = false; tr.append(td);
                                continue;
                            }
                            if (accolx) {
								td.addClass('actions');
                                // actions
                                if (data.params.button_edit) {
                                    var so = $('<input/>').attr({
                                        type : 'button',
                                        value : 'Edytuj',
                                        'class' : 'input_button edit'
                                    });
                                    so.data('aid',{
                                        id: rid,
                                        action: data.params.actionprefix+'_edit'
                                    });
                                    so.one("click", methods['genclickfun']);
                                    td.append(so);
                                }
								if (data.params.button_preview) {
                                    var so = $('<input/>').attr({
                                        type : 'button',
                                        value : 'Podgląd',
                                        'class' : 'input_button preview'
                                    });
                                    so.data('aid',{
                                        id: rid,
                                        action: data.params.actionprefix+'_preview'
                                    });
                                    so.one("click", function() {
                                        var p = $(this).data('aid');
                                        cmsmaster_call(p.id,p.action,true);
                                    });
                                    td.append(so);
                                }
								if (data.params.button_duplicate) {
                                    var so = $('<input/>').attr({
                                        type : 'button',
                                        value : 'Kopiuj',
                                        'class' : 'input_button copy'
                                    });
                                    so.data('aid',{
                                        id: rid,
                                        action: data.params.actionprefix+'_dup'
                                    });
                                    so.one("click", methods['genclickfun']);
                                    td.append(so);
                                }
                                if (data.params.button_delete) {
                                    var so = $('<input/>').attr({
                                        type : 'button',
                                        value : 'Usuń',
                                        'class' : 'input_button del'
                                    });
                                    so.data('aid',{
                                        id: rid,
                                        action: data.params.actionprefix+'_delete'
                                    });
                                    so.one("click", methods['genclickfun']);
                                    td.append(so);
                                }
                                accolx = false; tr.append(td);
                                continue;
                            }
                        }
/*
	text x
	checkbox x
	user x
	img(numer) x
	file x
	select(pole:typ) x
	relate(adres:kod:kierunek:ilosc:pole:typ) x
*/
						if (!Array.isArray(row[j])) {
							row[j] = [row[j]];
						}
						for(k=0;k<row[j].length;k++) {
                          switch(data.params.columns[j-1].type) {
                            case 'checkbox':
                                var cb = $('<input type="checkbox"/>');
                                cb.attr({
                                    disabled: true,
                                    checked: row[j][k]==1
                                })
                                td.append(cb);
                                break;
                            case 'date':
                            	if (row[j][k]!=null) {
                            		var d = new Date(row[j][k]*1000);
                            		td.text(d.toLocaleDateString());
                            	}
                            	break;
							case 'img':
									if(row[j][k]!=null) {
										var cb = $('<img alt="">');
										cb.attr('src', '../__i/0/50/_images/' + row[j][k]);
										td.append(cb);
									}
								break;
                            case 'link':
                                    if(row[j][k]!='') {
                                        var cb = $('<a>');
                                        cb.attr({
                                            href:row[j][k].href,
                                            target:'_blank'
                                        });
                                        if (typeof row[j][k].desc === 'undefined') {
                                            cb.text(row[j][k].href);
                                        } else {
                                            cb.text(row[j][k].desc);
                                        }
                                        td.append(cb);
                                    }
                                break;
							case 'file':
									if(row[j][k].downloadkey!='') {
										var cb = $('<a>');
										cb.attr({
											href:'../__f/' + row[j][k].downloadkey
										});
										cb.text(row[j][k].filename + ' ('+row[j][k].filesize+'kB)');
										td.append(cb);
									}
								break;
                            default:	// text, user
								if(row[j][k]!=null) {
									var cb = $('<span>');
	                                cb.text(row[j][k]);
									td.append(cb);
								}
                          }
						}
                        tr.append(td);
                    }
                    tr.data('aid',{
                                            id: rid,
                                            action: data.params.actionprefix+'_edit'
                                        });
                    tr.one("click", methods['genclickfun']);
                    if (row[data.rowlen-1] == 1) {
                        tr.addClass('inactive');
                    }
                    data.table.append(tr);
                }
            })  
        }

    };

    $.fn.ajaxtable = function( method ) {

        if ( methods[method] ) {
            return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.ajaxtable' );
        }    

    };

    $.typewatch = (function(){
        var timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        }  
    })();

})( jQuery );

/*
params:

// button_edit
// button_delete
// button_up
// button_down
// button_preview
// button_save
// button_duplicate
// legend 
// actionprefix
// boxed (shows tabli within standard field frame, instead of free form)
// columns (array of objects - .name .type, where type can be (text,image,date,dates,checkbox), 
// don't include action cols here, omit first column (the ID one)
// rows (2d array - 1st lev - row, 2nd lev - cell value, first cell contains the ID (mandatory)
// if empty an ajax call will be made if path is set
// path - path for ajax call, ?search and /dbe-list will be added automagically
// if not set, all is ok but ajax call will not be possible
// also search field will not be shown then
// extra - extra buttons (array of objects - .name, .function)                   

// pressing button calls cmsmaster_call(id, function) javascript function


*/
