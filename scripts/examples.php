<?php

$directory = '../examples/';
$examples = array();
$namesOfExamples = array_slice(scandir($directory), 2);

for($n = 0; $n < count($namesOfExamples); $n++)
{
    $nameOfExample = $namesOfExamples[$n];
    $examples[substr($nameOfExample, 0, -5)] = json_decode(file_get_contents($directory.$nameOfExample));
}

echo json_encode($examples);

?>