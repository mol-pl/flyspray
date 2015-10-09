<!DOCTYPE html>
<html lang="pl">
	<head>
		<meta charset="utf-8">
		<title>Gantt</title>
		<script                 src="{$baseurl}javascript/varia.js"></script>

		<link rel="stylesheet" href="{$baseurl}javascript/jsgantt/jsgantt.css" media="screen" />
		<script                 src="{$baseurl}javascript/jsgantt/jsgantt.js"></script>
		<script                 src="{$baseurl}javascript/jsgantt/date-functions.js"></script>
		<script                 src="{$baseurl}javascript/jsgantt/jsgantt_i18n_pl.js"></script>

		<script                 src="{$baseurl}javascript/jsgantt/jsgantt_loader.js"></script>

		<!-- Flyspray datepicker -->
		<!--
		<script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar_stripped.js"></script>
		<script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar-setup_stripped.js"> </script>
		<script type="text/javascript" src="{$baseurl}javascript/jscalendar/lang/calendar-{substr(L('locale'), 0, 2)}.js"></script>
		<link rel="stylesheet" type="text/css" media="screen" href="{$baseurl}javascript/jscalendar/calendar-system.css" />
		-->
		
		<!-- Form extra -->
		<script src="{$baseurl}javascript/jquery-ui/js/jquery.js"></script>
		<script src="{$baseurl}javascript/jquery-ui/js/jquery-ui.js"></script>
		<script src="{$baseurl}javascript/jquery-ui/js/i18n/jquery.ui.datepicker-pl.js"></script>
		<link rel="stylesheet" href="{$baseurl}javascript/jquery-ui/css/ui-lightness/jquery-ui.css" media="screen" />

		<script src="{$baseurl}javascript/jsgantt/form_helper.js"></script>
		<link rel="stylesheet" href="{$baseurl}javascript/jsgantt/form_helper.css" media="screen" />

		<!-- Gantt editor (experimental) -->
		<script                 src="{$baseurl}javascript/jsgantt/jsgantt.editor.js"></script>
	</head>
	<body>
		<h2>Parametry <a href="javascript:void(0)" onclick="$('form').first().toggle()">+/-</a></h2>
		<form method="GET" action="" id="param_form" style="display:none;">
			<input type="hidden" name="do" value="gantt_export" />
			<!-- data without specific transformations -->
			<? foreach($_GET as $key=>$val) { ?>
				<!-- not changed in other way -->
				<? if (!in_array( $key, array('forced_dev', 'order_type', 'do', 'gantt_base_date', 'due', 'freedays', 'submit', 'ids') )) { ?>
					<div class="input_group">
						<? if (!is_array( $val )) { ?>
							<label>{$key}</label> <input type="text" name="{$key}" value="{$val}" />
						<? } else { ?>
							<label>{$key}</label>
							<ul>
								<? foreach($val as $key2=>$val2) { ?>
									<li><label>{$key2}</label> <input type="text" name="{$key}[{$key2}]" value="{$val2}" /></li>
								<? } ?>
							</ul>
						<? } ?>
					</div>
				<? } ?>
			<? } ?>

			<!-- data with specific transformations or behaviours or ones that should always be exposed -->
			<div class="input_group">
				<label for="forced_dev">Wymuszony programista</label>
				<p class="info">Przypisuje osobę do wszystkich rozpoczętych zadań. Dodatkowo ignorowane są zadania oznaczone jako <em>niepotwierdzone</em>.</p>
				<input type="text" name="forced_dev" value="{Req::val('forced_dev', '')}" />
			</div>
			<div class="input_group">
				<label for="gantt_base_date">Data startowa</label>
				<input type="text" name="gantt_base_date" class="datepicker" value="{Req::val('gantt_base_date', date('Y-m-d'))}" />
			</div>
			<div class="input_group">
				<label for="gantt_base_date">Tryb sortowania</label>
				<select name="order_type">
					{!tpl_options(array('' => 'priorytet, ważkość', 'severity-first'=>'ważkość, priorytet'), Req::val('order_type'))}
				</select>
			</div>
			<div class="input_group">
				<label for="dueversion">Zadania w harmonogramie</label>
				<ul class="group">
					<li>
						<label for="dueversion">Z określonej wersji</label>
						<select id="dueversion" name="due[]" multiple="multiple">
							<option value="0">{L('undecided')}</option>
							{!tpl_options($proj->listVersions(false, '1,2,3'), Req::val('due'))}
						</select>
					</li>
					<li>
						<label for="ids">Identyfikatory zadań (CSV)</label>
						<input type="text" name="ids" value="{Req::val('ids')}" />
					</li>
				</ul>
			</div>
			<div class="input_group">
				<label for="freedays" class="group">Dni wolne</label>
				<p class="info">pojedyncze dni po przecinku, zakresy z dwoma dwukropkami</p>
				<ul class="group">
					<?
						$freedays = Req::val('freedays');
						$freedays_wannabes = array_merge(array(0), $task_groups);	// 4all + groups
						//$freedays_wannabes = array_fill_keys($freedays_wannabes, '');	// flip keys with values (and set values to '')
						foreach ($freedays_wannabes as $who)
						{
							if (!isset($freedays[$who]))
							{
								$freedays[$who] = '';
							}
						}
					?>
					<? foreach($freedays as $key=>$val) { ?>
						<? $groupname = ($key=='0' ? 'Wszyscy' : $key); ?>
						<li>
							<label>{$groupname} <a href="javascript:dateaddOpen('dateadd', 'freedays_{$key}')">dodaj</a></label>
							<input type="text" id="freedays_{$key}" name="freedays[{$key}]" value="{$val}" />
						</li>
					<? } ?>
				</ul>
			</div>
			<div class="input_group"><input type="submit" value="Generuj ponownie" name="submit" /></div>
		</form>
		<!-- mini form/dialog for dateaddOpen -->
		<div id="dateadd" data-for="freedays_0" style="display:none">
			<p><label for="dateadd-start">Start</label> <input type="text" id="dateadd-start" class="datepicker" value="" /></p>
			<p><label for="dateadd-end">Koniec</label>  <input type="text" id="dateadd-end" class="datepicker" value="" /></p>
			<p class="buttons">
				<a href="javascript:void(0)" onclick="appendDates(this.parentNode.parentNode)">Dopisz</a>
				<a href="javascript:void(0)" onclick="this.parentNode.parentNode.style.display='none'">Zamknij</a>
			</p>
		</div>

		<!-- gantt -->
		<h2>Diagram</h2>
		<div id="GanttChartDIV">
			<a href="{!$gantt_xml_url}">Gantt data</a>
		</div>

		<p><b>Uwaga!</b> Zadaniom bez wpisanego czasu wykonania dopisano (?) i przypisano czas wykonania na 1 dzień.</p>

		<input type="button" name="update" onclick="oJSGantEdit.chartUpdate()" value="Aktualizuj z pola" title="Aktualizuj diagram z pola edycyjnego do diagramu" />

		<!-- code -->
		<h2>Kod do wklejenia na wiki</h2>
		<textarea style="width:100%; height:300px;" id="txtinput"><?php $this->display('gantt.text.tpl'); ?></textarea>

		<!-- links -->
		<h2>Strony z harmonogramami</h2>
		<p>
		<a href="http://prl.mol.com.pl/wiki/index.php/Harmonogramy_prac_nad_Libr%C4%85">Libra</a>
		&bull;
		<a href="http://prl.mol.com.pl/wiki/index.php/Harmonogramy_prac_nad_MOL-em">MOL</a>
		&bull;
		<a href="http://prl.mol.com.pl/wiki/index.php/Molik:Harmonogramy">Molik</a>
		</p>
	</body>
</html>