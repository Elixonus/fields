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
$charges = array(new Charge(1, new Point(500, 500)));
$field = new Field($charges);

$draw = new ImagickDraw();
$draw->setStrokeWidth(3);

for($n = 0; $n < count($charges); $n++)
{
    $charge = $charges[$n];
    
    if($charge->charge !== 0)
    {
        for($k = 0; $k < 10; $k++)
        {
            
        }
    }
}

for($n = 0; $n < count($charges); $n++)
{
    $charge = $charges[$n];
    
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
    
    $draw->circle($charges[$n]->position->x, $charges[$n]->position->y, $charges[$n]->position->x + 20, $charges[$n]->position->y);
}


$image = new Imagick();
$image->newImage($width, $height, new ImagickPixel('white'));
$image->setImageFormat('png');
$image->drawImage($draw);

$pixels = array();

for($x = 0; $x < $width; $x++)
{
    for($y = 0; $y < $height; $y++)
    {
        $value = $field->getElectricFieldVectorAtPoint(new Point($x, $y))->getMagnitude() / 300;
        array_push($pixels, $value, $value, $value);
    }
}

$image->importImagePixels(0, 0, $width, $height, 'RGB', Imagick::PIXEL_CHAR, $pixels);
header('Content-Type: image/png');
echo $image;

?>