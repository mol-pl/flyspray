<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl">
<head>
    <title>{$this->_title}</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<meta name="author" content="Maciej Jaros" />
	<meta name="copyright" content="Maciej Jaros" />

	<link rel="stylesheet" type="text/css" media="screen" href="{$baseurl}/javascript/frmcopy.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="{$baseurl}/themes/Bluey/theme.css" />

	<script type="text/javascript" src="{$baseurl}/javascript/varia.js"></script>
	<script type="text/javascript" src="{$baseurl}/javascript/json2.js"></script>

	<script type="text/javascript" src="{$baseurl}/javascript/framemsg.js"></script>
	<script type="text/javascript" src="{$baseurl}/javascript/frmcopy.js"></script>
	<script type="text/javascript">
	//<![CDATA[
	oPostman.strDestFrameBaseUrl = '{!$postman['strDestFrameBaseUrl']}';
	//]]>
	</script>
</head>
<body>

<div id="copy_actions">
	<input type="button" value="ukryj źródło" onclick="frmcp.showHideFrame('source_frame', <?php echo "{el:this, shown:'ukryj źródło', hidden:'pokaż źródło'}";?>)" />
	<input type="button" value="&rarr; kopiuj dane &rarr;" onclick="frmcp.run()" />
	<input type="button" value="ukryj cel" onclick="frmcp.showHideFrame('dest_frame', <?php echo "{el:this, shown:'ukryj cel', hidden:'pokaż cel'}";?>)" />

	<input type="button" style="float:right" value="zamknij ramki" onclick="frmcp.close()" />
</div>

<div id="copy_info">
<p>Pamiętaj o wybraniu odpowiedniego projektu w wersji docelowej! Zwróć uwagę, że załączniki i niektóre inne pola nie zostaną skopiowane. Musisz to zrobić ręcznie.</p>
</div>
	
<div id="copy_frames">
	<iframe src="{$source_url}" id="source_frame">blah!</iframe>
	<iframe src="{$dest_url}" id="dest_frame">blah!</iframe>
</div>

</body>
</html>