<?php

class Point
{
    public $x;
    public $y;
    
    function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
        return $this;
    }
    
    function changeTo($p)
    {
        return $this->changeToCoordinates($p->x, $p->y);
    }
    
    function changeToCoordinates($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
        return $this;
    }
    
    function addTo($p)
    {
        return $this->addToCoordinates($p->x, $p->y);
    }
    
    function addToCoordinates($x, $y)
    {
        $this->x += $x;
        $this->y += $y;
        return $this;
    }
    
    function addToPolar($r, $a)
    {
        return $this->addToCoordinates($r * cos($a), $r * sin($a));
    }
    
    function subtractTo($p)
    {
        return $this->subtractToCoordinates($p->x, $p->y);
    }
    
    function subtractToCoordinates($x, $y)
    {
        $this->x -= $x;
        $this->y -= $y;
        return $this;
    }
    
    function multiplyBy($n)
    {
        $this->x *= $n;
        $this->y *= $n;
        return $this;
    }
    
    function divideBy($n)
    {
        $this->x /= $n;
        $this->y /= $n;
        return $this;
    }
    
    function normalize()
    {
        return $this->divideBy($this->getMagnitude());
    }
    
    function getMagnitude()
    {
        return hypot($this->x, $this->y);
    }
    
    function getDistanceTo($p)
    {
        return hypot($this->x - $p->x, $this->y - $p->y);
    }
    
    function getDirectionTo($p)
    {
        return atan2($p->y - $this->y, $p->x - $this->x);
    }
    
    function copy()
    {
        return new Point($this->x, $this->y);
    }
}

class Field
{
    public $charges;
    
    function __construct($charges)
    {
        $this->charges = $charges;
        return $this;
    }
    
    function addCharge($charge)
    {
        array_push($this->charges, $charges);
        return $this;
    }
    
    function removeChargeIndex($index)
    {
        array_splice($this->charges, $index, 1);
        return $this;
    }
    
    function getElectricFieldVectorAtPoint($point)
    {
        $vector = new Point(0, 0);
        
        for($c = 0; $c < count($this->charges); $c++)
        {
            $charge = $this->charges[$c];
            $distanceToCharge = $charge->position->getDistanceTo($point);
            $direction = $charge->position->getDirectionTo($point);
            
            if($distanceToCharge === 0.0)
            {
                $magnitude = INF;
            }
            
            else
            {
                $magnitude = 8.9875517923E9 * $charge->charge / pow($distanceToCharge, 2);    
            }
            
            $vector->addToPolar($magnitude, $direction);
        }
        
        return $vector;
    }
}

class Charge
{
    public $charge;
    public $position;
    
    function __construct($charge, $position)
    {
        $this->charge = $charge;
        $this->position = $position;
        return $this;
    }
}

$width = 1000;
$height = 1000;
$fieldLinesPerCharge = 30;
$maxIterationsPerFieldLine = 1000;
$stepPerIteration = 5;

//$charges = array(new Charge(100, new Point(700, 500)), new Charge(-100, new Point(400, 300)), new Charge(-100, new Point(500, 800)));
$charges = array(new Charge(-100, new Point(400, 600)), new Charge(200, new Point(800, 200)));
$field = new Field($charges);

$draw = new ImagickDraw();
$draw->setStrokeColor('#000000');
$draw->setFillOpacity(0);

for($c = 0; $c < count($charges); $c++)
{
    $charge = $charges[$c];
    
    if($charge->charge !== 0)
    {
        for($l = 0; $l < $fieldLinesPerCharge; $l++)
        {
            $position = $charge->position->copy();
            $direction = $l / $fieldLinesPerCharge * 2 * pi();
            $draw->pathStart();
            $draw->pathMoveToAbsolute($position->x, $position->y);
            $position->addToPolar($stepPerIteration, $direction);
            $draw->pathLineToAbsolute($position->x, $position->y);
            
            for($i = 0; $i < $maxIterationsPerFieldLine - 1; $i++)
            {
                $normalizedFieldAtPoint = $field->getElectricFieldVectorAtPoint($position)->normalize()->multiplyBy($stepPerIteration);
                
                if($charge->charge < 0)
                {
                    $normalizedFieldAtPoint->multiplyBy(-1);
                }
                
                $position->addTo($normalizedFieldAtPoint);
                $draw->pathLineToAbsolute($position->x, $position->y);
            }
            
            $draw->pathFinish();
        }
    }
}

$draw->setStrokeWidth(3);
$draw->setFillOpacity(1);

for($c = 0; $c < count($charges); $c++)
{
    $charge = $charges[$c];
    
    if($charge->charge < 0)
    {
        $draw->setStrokeColor('#0000ff');
        $draw->setFillColor('#6666ff');    
    }
    
    else if($charge->charge > 0)
    {
        $draw->setStrokeColor('#ff0000');
        $draw->setFillColor('#ff6666'); 
    }
    
    else
    {
        $draw->setStrokeColor('#888888');
        $draw->setFillColor('#aaaaaa'); 
    }
    
    $draw->circle($charges[$c]->position->x, $charges[$c]->position->y, $charges[$c]->position->x + 15, $charges[$c]->position->y);
}


$image = new Imagick();
$image->newImage($width, $height, 'white');
$image->setImageFormat('png');
$image->drawImage($draw);
header('Content-Type: image/png');
echo $image;

?>