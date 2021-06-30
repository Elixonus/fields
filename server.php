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
    
    static function fromPolar($r, $a)
    {
        return new Point($r * cos($a), $r * sin($a));
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

class Collection
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
    
    function removeCharge($charge)
    {
        return $this->removeChargeIndex(array_search($charge, $this->charges));
    }
    
    function removeChargeIndex($index)
    {
        array_splice($this->charges, $index, 1);
        return $this;
    }
    
    function getElectricFieldVectorAtPoint($point)
    {
        $sumVector = new Point(0, 0);
        
        for($c = 0; $c < count($this->charges); $c++)
        {
            $charge = $this->charges[$c];
            $vector = $charge->getElectricFieldVectorAtPoint($point);
            
            if(abs($vector->x) === INF || abs($vector->y) === INF)
            {
                $sumVector = $vector;
                break;
            }
            
            else
            {
                $sumVector->addTo($vector);
            }
        }
        
        return $sumVector;
    }
}

class PointCharge
{
    public $charge;
    public $position;
    
    function __construct($charge, $position)
    {
        $this->charge = $charge;
        $this->position = $position;
        return $this;
    }
    
    function getElectricFieldVectorAtPoint($point)
    {
        $distanceToPoint = $this->position->getDistanceTo($point);
        
        if($distanceToPoint == 0)
        {
            return new Point(INF, 0);
        }
        
        else
        {
            $direction = $this->position->getDirectionTo($point);
            $magnitude = 8.9875517923E9 * $this->charge / pow($distanceToPoint, 2);
            return Point::fromPolar($magnitude, $direction);
        }
    }
}

class ShapeCharge
{
    public $charge;
    public $position;
    public $vertices;
    public $isClosed;
    
    function __construct($charge, $position, $vertices, $isClosed = true)
    {
        $this->charge = $charge;
        $this->position = $position;
        $this->vertices = $vertices;
        $this->isClosed = $isClosed;
        return $this;
    }
    
    function getElectricFieldVectorAtPoint($point)
    {
        $perimeter = 0;
        
        for($v = 0; $v < count($this->vertices) + $this->isClosed - 1; $v++)
        {
            $perimeter += $this->vertices[$v]->getDistanceTo($this->vertices[($v + 1) % count($this->vertices)]);
        }
        
        return $perimeter;
    }
}

$elementaryCharge = 1.6021E19;

$fieldLinesPerCharge = 50;
$maxIterationsPerFieldLine = 2000;
$stepPerIteration = 0.01;



$graphWidth = 1500;
$graphHeight = 1500;
$simulationWidth = 1000;
$simulationHeight = 1000;
$minimumX = 0;
$minimumY = 0;
$maximumX = 1;
$maximumY = 1;
$multiplierX = $simulationWidth / ($maximumX - $minimumX);
$multiplierY = $simulationHeight / ($maximumY - $minimumY);

//$charges = array(new Charge(100, new Point(700, 500)), new Charge(-100, new Point(400, 300)), new Charge(-100, new Point(500, 800)));
//$charges = array(new Charge($elementaryCharge, new Point(0.4, 0.6)), new Charge($elementaryCharge, new Point(0.6, 0.5)), new Charge(-$elementaryCharge, new Point(0.2, 0.2)));
$charges = array(new PointCharge($elementaryCharge, new Point(0.2, 0.5)), new PointCharge(-$elementaryCharge, new Point(0.8, 0.5)));
$collection = new Collection($charges);

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
            $screenPosition = virtualPositionToScreenCoordinates($position);
            $draw->pathStart();
            $draw->pathMoveToAbsolute($screenPosition[0], $screenPosition[1]);
            $position->addToPolar($stepPerIteration, $direction);
            $screenPosition = virtualPositionToScreenCoordinates($position);
            $draw->pathLineToAbsolute($screenPosition[0], $screenPosition[1]);
            
            for($i = 0; $i < $maxIterationsPerFieldLine - 1; $i++)
            {
                $normalizedFieldAtPoint = $collection->getElectricFieldVectorAtPoint($position)->normalize();
                
                if($charge->charge < 0)
                {
                    $normalizedFieldAtPoint->multiplyBy(-1);
                }
                
                $position->addTo($normalizedFieldAtPoint->multiplyBy($stepPerIteration));
                $screenPosition = virtualPositionToScreenCoordinates($position);
                $breakTwice = false;
                
                for($cc = 0; $cc < count($charges); $cc++)
                {
                    if($c === $cc)
                    {
                        continue;
                    }
                    
                    if($position->getDistanceTo($charges[$cc]->position) < $stepPerIteration)
                    {
                        $breakTwice = true;
                        break;
                    }
                }
                
                $draw->pathLineToAbsolute($screenPosition[0], $screenPosition[1]);
                
                if($breakTwice)
                {
                    break;
                }
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
    
    $screenPosition = virtualPositionToScreenCoordinates($charge->position);
    $draw->circle($screenPosition[0], $screenPosition[1], $screenPosition[0] + 15, $screenPosition[1]);
}


$simulationImage = new Imagick();
$simulationImage->newImage($simulationWidth, $simulationHeight, 'white');
$simulationImage->setImageFormat('png');
$simulationImage->drawImage($draw);
//header('Content-Type: image/png');
//echo $simulationImage;

$shapeCharge = new ShapeCharge(2, new Point(0, 0), array(new Point(0, 0), new Point(10, 0), new Point(10, 10), new Point(0, 10)), true);
array_push($shapeCharge->vertices, new Point(-1, 5));

echo $shapeCharge->getElectricFieldVectorAtPoint(new Point(0, 0));

function virtualPositionToScreenCoordinates($position)
{
    global $minimumX, $multiplierX, $simulationHeight, $minimumY, $multiplierY;
    return array(($position->x - $minimumX) * $multiplierX, $simulationHeight - ($position->y - $minimumY) * $multiplierY);
}

?>