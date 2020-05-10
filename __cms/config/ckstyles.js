// The set of styles for the Styles combo

CKEDITOR.stylesSet.add( 'default',
[
	{ name : 'Nagłówek 1', element : 'h1' },
	{ name : 'Nagłówek 2', element : 'h2' },

	{ name : 'Tekst wprowadzający', element : 'span',
	  attributes : { 'class' : 'lead' }
	},

	{ name : 'Akapit', element : 'p',
	  attributes : { 'class' : '' }
	},

	{ name : 'Obrazek lewo', element : 'img',
	  attributes : {
	    'class' : 'left',
	    'align' : 'left'
	  }
	},

	{ name : 'Obrazek prawo', element : 'img',
	  attributes : {
	    'class' : 'right',
	    'align' : 'right'
	  }
	}
]);
