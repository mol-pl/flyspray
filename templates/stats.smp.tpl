<!-- tabela statystyk -->
<script type="text/javascript" src="{$baseurl}javascript/sortable.js"></script>
<div style="float:left">
	<?php if (empty($stats)){ ?>	
		<p>Brak danych do wyświetlenia. Spróbuj odświeżyć dane.</p>
	<?php } else { ?>
		<?php tabelka_print($stats); ?>
	<?php } ?>
	<?php if ($user->perms('open_new_tasks')){ ?>	
		<p style="width:400px">Możesz <a href="index.php?do=prog_stats_refresh">odświeżyć</a> statystyki, ale pamiętaj, że jest to spore obciążenie dla serwera. Nie należy wykonywać tego co chwilę.</p>
	<?php } ?>
</div>
<section>
<div class="warning_box" style="float:left; width:500px">
	<section>
	<p><strong>Uwaga!</strong> Statystyki są liczone tylko dla wersji wydanych (wersja oznaczona jako "Przeszła", albo "Obecna").
	Dodatkowe ukryte są wersje beta (nie widoczne także na liście wyboru wersji przy dodawaniu zgłoszeń).</p>
	<p>Dodatkowo nie są liczone błędy poprawione w trakcie produkcji (zamknięte w tej samej wersji).</p>
	<p><strong>Uwaga!</strong> Ważkość błędów nie jest rozróżniana, ani filtrowana.</p>
	</section>
</div>
</section>
<br clear="all" />