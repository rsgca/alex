<?php 
header('Content-Type: text/plain; charset=utf-8'); 
require ('system/alex_document.class.php'); 
$burkes = new alex_document('burkes.txt'); 
$individuals = $burkes->get_people();
$families = $burkes->get_families();
?>
0 HEAD
1 SOUR alex
2 NAME alex
2 VERS 1
1 DEST DISKETTE
1 DATE <?php echo strtoupper(date('j M Y'))?><?php echo "\n"; ?>
2 TIME <?php echo date('H:i:s')."\n"; ?>
1 GEDC
2 VERS 5.5.1
2 FORM Lineage-Linked
1 CHAR UTF-8
1 FILE The Greenwood Family
1 COPR Copyright (c) 2012 Richard.
1 LANG English
1 PLAC
2 FORM City, County, State/Province, Country 
1 SUBM @SUBM@
<?php foreach ($individuals as $key => $person) : ?>
0 <?php echo $person->get_id(); ?> INDI
1 NAME <?php echo $person->get_name('given'); ?> /<?php echo $person->get_name('last'); ?>/ 
2 GIVN <?php echo $person->get_name('given'); ?><?php echo "\n"; ?>
2 SURN <?php echo $person->get_name('last'); ?><?php echo "\n"; ?>
<?php if ($person->get_npfx()) : ?>
2 NPFX <?php echo $person->get_npfx(); ?><?php echo "\n"; ?>
<?php endif;?>
2 NOTE <?php echo $person->get_note(); ?><?php echo "\n"; ?>
1 SEX <?php echo $person->get_sex(); ?><?php echo "\n"; ?>
<?php if ($person->get_birt()) : ?>
1 BIRT 
2 DATE <?php echo $person->get_birt(); ?><?php echo "\n"; ?>
<?php endif;?>
<?php if ($person->get_bapm()) : ?>
1 BAPM 
2 DATE <?php echo $person->get_bapm(); ?><?php echo "\n"; ?>
<?php endif;?>
<?php if ($person->get_deat()) : ?>
1 DEAT 
2 DATE <?php echo $person->get_deat(); ?><?php echo "\n"; ?>
<?php endif;?>
<?php if ($person->get_buri()) : ?>
1 BURI 
2 DATE <?php echo $person->get_buri(); ?><?php echo "\n"; ?>
<?php endif;?>
<?php if ($person->get_fams()) : ?>
<?php foreach ($person->get_fams() as $key => $value): ?>
1 FAMS <?php echo $value; ?><?php echo "\n"; ?>
<?php endforeach; ?>
<?php endif; ?>
<?php if ($person->get_famc()) : ?>
1 FAMC <?php echo $person->get_famc(); ?><?php echo "\n"; ?>
<?php endif;?>
<?php endforeach; ?>
<?php foreach ($families as $family) : ?>
0 <?php echo $family['id']; ?> FAM
<?php if (isset($family['husb'])) : ?>
1 HUSB <?php echo $family['husb']; ?><?php echo "\n"; ?>
<?php endif; ?>
<?php if (isset($family['wife'])) : ?>
1 WIFE <?php echo $family['wife']; ?><?php echo "\n"; ?>
<?php endif; ?>
1 MARR
<?php if ($family['date']) : ?>
2 DATE <?php echo $family['date']; ?><?php echo "\n"; ?>
<?php else : ?>
2 TYPE Y
<?php endif; ?>
<?php if(isset($family['children'])) : ?>
<?php foreach ( $family['children'] as $value) : ?>
1 CHIL <?php echo $value; ?><?php echo "\n"; ?>
<?php endforeach; ?>
<?php endif; ?>
<?php endforeach; ?>
0 @SUBM@ SUBM
1 NAME Alex
1 ADDR Not Provided
2 CONT Not Provided
2 ADR1 Not Provided
0 TRLR