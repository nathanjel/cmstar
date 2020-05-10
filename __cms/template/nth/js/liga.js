/* A polyfill for browsers that don't support ligatures. */
/* The script tag referring to this file must be placed before the ending body tag. */

/* To provide support for elements dynamically added, this script adds
   method 'icomoonLiga' to the window object. You can pass element references to this method.
*/
(function () {
	'use strict';
	function supportsProperty(p) {
		var prefixes = ['Webkit', 'Moz', 'O', 'ms'],
			i,
			div = document.createElement('div'),
			ret = p in div.style;
		if (!ret) {
			p = p.charAt(0).toUpperCase() + p.substr(1);
			for (i = 0; i < prefixes.length; i += 1) {
				ret = prefixes[i] + p in div.style;
				if (ret) {
					break;
				}
			}
		}
		return ret;
	}
	var icons;
	if (!supportsProperty('fontFeatureSettings')) {
		icons = {
			'pen': '&#xe908;',
			'write3': '&#xe908;',
			'books': '&#xe920;',
			'library': '&#xe920;',
			'profile': '&#xe923;',
			'file2': '&#xe923;',
			'file-text2': '&#xe926;',
			'file4': '&#xe926;',
			'calculator': '&#xe940;',
			'compute': '&#xe940;',
			'display': '&#xe956;',
			'screen': '&#xe956;',
			'mobile': '&#xe958;',
			'cell-phone': '&#xe958;',
			'tv': '&#xe95b;',
			'television': '&#xe95b;',
			'bubble2': '&#xe96e;',
			'comment2': '&#xe96e;',
			'users': '&#xe972;',
			'group': '&#xe972;',
			'info': '&#xea0c;',
			'information': '&#xea0c;',
			'table2': '&#xea71;',
			'wysiwyg19': '&#xea71;',
			'folder-open': '&#xe930;',
			'directory2': '&#xe930;',
			'0': 0
		};
		delete icons['0'];
		window.icomoonLiga = function (els) {
			var classes,
				el,
				i,
				innerHTML,
				key;
			els = els || document.getElementsByTagName('*');
			if (!els.length) {
				els = [els];
			}
			for (i = 0; ; i += 1) {
				el = els[i];
				if (!el) {
					break;
				}
				classes = el.className;
				if (/icon-/.test(classes)) {
					innerHTML = el.innerHTML;
					if (innerHTML && innerHTML.length > 1) {
						for (key in icons) {
							if (icons.hasOwnProperty(key)) {
								innerHTML = innerHTML.replace(new RegExp(key, 'g'), icons[key]);
							}
						}
						el.innerHTML = innerHTML;
					}
				}
			}
		};
		window.icomoonLiga();
	}
}());