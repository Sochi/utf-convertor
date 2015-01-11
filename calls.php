<?php

define('DIVIDER', ' ');

require sprintf('%s/functions.php', __DIR__);

// Array-example
$examples = array(
    'Hello world!',
    'Žluťoučký kůň',
);

?>
<pre>
<?php foreach ($examples as $string): ?>
Input:       <?=$string; ?> 
Unicode:     <?=text2bin($string, 16, DIVIDER); ?> 
Hexadecimal: <?=text2bin($string, 8, DIVIDER); ?> 
Binary:      <?=$binary = text2bin($string, 2, DIVIDER); ?> 
Reversed:    <?=bin2text($binary); ?> 
-- 
<?php endforeach; ?>
</pre>
