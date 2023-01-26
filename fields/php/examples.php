<?php

$directory = '../examples/';
$examples = array();
$examplesName = array_slice(scandir($directory), 2);

for($n = 0; $n < count($examplesName); $n++)
{
    $exampleName = $examplesName[$n];
    $examples[substr($exampleName, 0, -5)] = json_decode(file_get_contents($directory.$exampleName));
}

echo json_encode($examples);

?>
