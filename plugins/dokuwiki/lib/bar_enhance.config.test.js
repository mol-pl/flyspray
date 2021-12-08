/*
	Test of Settings.
	
	Use `bar_test` param to run this.
	index.php?do=newtask&project=5&bar_test=1
*/

// buttons/tabs
nuxbar.tpls.buttons = ''
	+'<div style="text-align:left">'
		+'<a href="javascript:nuxbar.insertURL(\'%txtarea_id%\')">Ogólne</a>'
		+' &bull; '
		+'<a href="javascript:nuxbar.insertSVNURL(\'%txtarea_id%\')">SVN</a>'
	+'</div>'
;

// Docuwiki link prefixes list with names and types
nuxbar.arrPrefixes =
[
	{
		display:'Bugz',
		prefix:'fs',
		type:'general',
		url:'https://pp.mol.com.pl/bugz/',
		alt_urls:[
			'http://prl.mol.com.pl/bugz/',
			'https://prl.mol.com.pl/bugz/',
		]
	},
	{
		display:'ISO',
		prefix:'iso',
		type:'general',
		url:'https://iso.pp.mol.com.pl/'

	}
];

// templates containing text/dokuwiki code to be inserted
nuxbar.arrInsertTpls =
[
	{
		strLabel	:'Cursor before',
		strHint		:'Test placing curosor before text.',
		strText		:'$|$'	// cursor here
			+'\\nMore details below:'
			+'\\n  - A'
			+'\\n  - B'
			+'\\n'
	},
	{
		strLabel	:'Multiline plain',
		strHint		:'ES5 multiline string.',
		strText		:''
			+'\\nMultiline plain:'
			+'\\n  - A'
			+'\\n  - B'
			+'\\n'
	},
	//
	// ES6 template strings
	//
	// Note that this line escaping is due to how `strText` is inserted into HTML.
	{
		strHtml     :'<label>Tpl strings:</label>'	// Label = new section
	},
	{
		strLabel	:'Multiline template string',
		strHint		:'ES6 multiline string.',
		strText		:`
\\nMultiline template string:
\\n  - A
\\n  - B
\\n
`
	},
	{
		strLabel	:'Parsed template string',
		strHint		:'ES6 multiline string with escaped lines added automatically.',
		strText		:`
Multiline template string:
  - A
  - B
`.replace(/\n/g, '\\n')
	},
	{
		strLabel	:'Parsed template string tabs (recommended)',
		strHint		:'ES6 multiline string with escaped lines added automatically and tabs removed.',
		strText		:`
			Multiline template string:
			  - A
			  - B
		`.replace(/\n\t*/g, '\\n')
	},


	//
	// Quotes etc
	//
	{
		strHtml     :'<label>Escaping:</label>'	// Label = new section
	},
	{
		strLabel	:'Test "q"',
		strHint		:'Test "quotes"',
		strText		:'\\nTest "quotes"'
	},

	{
		strLabel	:`Test 'apo'<b>abc</b>`,
		strHint		:`Test 'apostrophe'`,
		strText		:`\\nTest 'apostrophe'.`
	},

];
