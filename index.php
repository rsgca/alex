<?php
/**
 * Alex - Convert Burkes Peerage to GEDCOM
 *
 * Parses a Burkes Peerage format and converts it to GEDCOM
 *
 * @package Alex
 * @subpackage index
 * @author Richard Greenwood
 */
require ('system/alex_document.class.php');
?>

<!DOCTYPE html>
<html>
<head>
	<title>Alex - Convert Burkes Peerage to GEDCOM</title>
	<meta charset="utf-8">
	<style>
		html, body { padding: 0; margin: 0; font-family: Helvetica, sans-serif; }
		body { margin: 0; padding: 0; font-size: 12px;}
		h1 { font-size: 100%; margin: 10px; float:left; }
		.results { float: right; margin: 10px; }
		.odd, .odd td { background: #DDD; }
		.odd td { margin: 1%; }
		pre {  }
		table { border-collapse:collapse; }
		td { padding: 1%; vertical-align: top; border: 1px solid #BBB;}
		th { background: #BBB; }
		.line { padding: 2px; display: inline; line-height: 20px; }
		.line:hover, tr:hover td { background: #C3DFC3;}
		.import { 
			background: none repeat scroll 0 0 #EEEEEE;
			bottom: 60%;
			margin: 1%;
			overflow: scroll;
			padding: 1%;
			position: absolute;
			top: 27px;
			width: 96%; 
			}
		.indi_title { color: red; }
		.meta_title { color: #999; }
		.comparison { 
			bottom: 1%;
			margin: 1%;
			overflow: scroll;
			position: absolute;
			top: 38%;
		}
	</style>
</head>

<body>

<h1>Alex (Convert Burkes Peerage to GEDCOM)</h1>
<div class="results"><a href="/alex/result.php">Download GEDCOM</a></div>
<?php
	$burkes = new alex_document('burkes.txt');
	$conformed = $burkes->doc;
	$review = $burkes->get_people(TRUE);
?>

<div class="import">
<?php foreach ($conformed as $key => $value) :?>
	<?php $class = ( $key % 2 ) ? 'odd' : 'even'; ?>
	<pre class="line <?php echo $class; ?>" title="Line: <?php echo $key; ?>"><?php echo $key; ?> <?php echo $value ?></pre><br />
<?php endforeach; ?>
</div>

<?php if (isset($review)) : ?>
<div class="comparison">
	<table>
		<colgroup span="1" width="3%" ></colgroup>		
		<colgroup span="2" width="22%" ></colgroup>
		<colgroup span="3" width="18%" ></colgroup>
		<thead>
			<th></th>			
			<th>Raw Line</th>
			<th>Relation</th>
			<th>Spouse</th>
			<th>Inlaw Father</th>
			<th>Inlaw Mother</th>
		</thead>
		<tbody>
		<?php foreach ($review as $key => $line) : ?>		
			<?php $class = ( $key % 2 ) ? 'odd' : 'even'; ?>
			<tr class="<?php echo $class; ?>">
				<td><?php echo $key; ?></td>				
				<td><?php echo $line['l']; ?></td>
				<?php if (is_array($line)) : ?>
					<?php foreach ($line as $type => $person) : ?>
						<?php if ($type !== 'l') : ?>
							<td>			
								<span class="indi_title"><?php echo $person->get_id(); ?></span> <br />
								<span class="meta_title">1 NAME </span><?php echo $person->get_name('given'); ?> /</span><?php echo $person->get_name('last'); ?>/<br />
								<span class="meta_title">2 GIVN </span><?php echo $person->get_name('given'); ?><br />
								<span class="meta_title">2 SURN </span><?php echo $person->get_name('last'); ?><br />
								<span class="meta_title">2 NPFX </span><?php echo $person->get_npfx(); ?><br />
								<span class="meta_title">2 NOTE </span><?php echo $person->get_note(); ?><br />
								<span class="meta_title">1 SEX  </span><?php echo $person->get_sex(); ?><br />
								<span class="meta_title">1 BIRT </span><br />
								<span class="meta_title">2 DATE </span><?php echo $person->get_birt(); ?><br />
								<span class="meta_title">1 BAPM </span><br />
								<span class="meta_title">2 DATE </span><?php echo $person->get_bapm(); ?><br />
								<span class="meta_title">1 DEAT </span><br />
								<span class="meta_title">2 DATE </span><?php echo $person->get_deat(); ?><br />
								<span class="meta_title">1 BURI </span><br />
								<span class="meta_title">2 DATE </span><?php echo $person->get_buri(); ?><br />
								<?php if ($person->get_fams()) : ?>
									<?php foreach ($person->get_fams() as $value): ?>
										<span class="meta_title">1 FAMS </span><?php echo $value; ?><br />
									<?php endforeach; ?>
								<?php endif; ?>
								<span class="meta_title">1 FAMC </span><?php echo $person->get_famc(); ?><br />
							</td>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif; ?>
</body>

</html>