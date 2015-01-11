<?php

require sprintf('%s/functions.php', __DIR__);



$inputText = 'example input';

$divider = ' '; //(isset($_POST['divider'])) ? htmlspecialchars($_POST['divider'], ENT_QUOTES) : ' ';
$mean = 2; //(isset($_POST['mean']) && ($_POST['mean'] == 2 || $_POST['mean'] == 8 || $_POST['mean'] == 16)) ? $_POST['mean']*1 : '2';
$bin = text2bin($inputText, $mean, $divider); //text2bin(trim($_POST['area']),$mean,$divider);


echo $bin.'<br />';


$str = bin2text($bin);

echo $str.'<br />';

$str = bin2text($inputText);

echo $str.'<br />';

exit;

/*


// Array-example
$examples = array();

// Generate examples 
for ($i = 0; $i < 16; ++$i) {
    $randomNumber = mt_rand();
    $examples[$randomNumber] = encode(CHARACTERS_CASE_SENSITIVE, $randomNumber);
}
*/

?>
<pre>
<?php foreach ($exampleSets as $characters): ?>
<?php if (hasDuplicates($characters)): ?>
Unsuitable character set: <?=$characters; ?> (containing duplicate characters)
<?php else: ?>
Suitable character set: <?=$characters; ?> 
<?php endif; ?>
<?php endforeach; ?>
-----
<?php foreach ($examples as $number => $encoded): ?>
Randomly-generated: <?=$number; ?> 
Shortened output: <?=$encoded; ?> 
Reverse check: <?=decode(CHARACTERS_CASE_SENSITIVE, $encoded); ?> 
--
<?php endforeach; ?>
</pre>

