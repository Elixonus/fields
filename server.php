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
    
    function interpolateToPoint($p, $t)
    {
        $this->x = interpolate($this->x, $p->x, $t);
        $this->y = interpolate($this->y, $p->y, $t);
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

class Collection
{
    public $charges;
    public $flashlights;
    
    function __construct($charges, $flashlights)
    {
        $this->charges = $charges;
        $this->flashlights = $flashlights;
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

class LineSegmentCharge
{
    public $charge;
    public $endpoint1;
    public $endpoint2;
    
    function __construct($charge, $endpoint1, $endpoint2)
    {
        $this->charge = $charge;
        $this->endpoint1 = $endpoint1;
        $this->endpoint2 = $endpoint2;
        return $this;
    }
    
    function getElectricFieldVectorAtPoint($point)
    {
        $dx = $this->endpoint2->x - $this->endpoint1->x;
        $dy = $this->endpoint2->y - $this->endpoint1->y;
        $dp1p2 = sqrt(pow($dx, 2) + pow($dy, 2));
        $a = ($dx * ($this->endpoint1->x - $point->x) + $dy * ($this->endpoint1->y - $point->y)) / $dp1p2;
        $b = $a + $dp1p2;
        $dasq = pow($point->x - $this->endpoint1->x, 2) + pow($point->y - $this->endpoint1->y, 2);
        $dbsq = pow($point->x - $this->endpoint2->x, 2) + pow($point->y - $this->endpoint2->y, 2);
        $z = sqrt($dasq - pow($a, 2));
        $da = sqrt($dasq);
        $db = sqrt($dbsq);
        $eII = $this->charge * 8.9875517923E9 * (1 / $db - 1 / $da);
        $eT = $this->charge * 8.9875517923E9 / $z * ($b / $db - $a / $da);
        
        
        /*$theta = atan2($this->endpoint2->y - $this->endpoint1->y, $this->endpoint2->x - $this->endpoint1->x);
        $matrix = array(array(cos($theta), -sin($theta)), array(sin($theta), cos($theta)));
        $ex = $matrix[0][0] * $eII + $matrix[0][1] * $eT;
        $ey = $matrix[1][0] * $eII + $matrix[1][1] * $eT;*/
        
        
        
        
        $matrixAbove = [[$dx / $dp1p2, -$dy / $dp1p2], [$dy / $dp1p2, $dx / $dp1p2]];
        $matrixBelow = [[$dx / $dp1p2, $dy / $dp1p2], [-$dy / $dp1p2, $dx / $dp1p2]];
        $matrixBelow = $matrixAbove;
        
        if(($point->x - $this->endpoint1->x) * $dy <= ($point->y - $this->endpoint1->y) * $dx)
        {
            $matrix = $matrixAbove;
        }
        
        else
        {
            $matrix = $matrixBelow;
        }
        
        $matrix = $matrixBelow;
        
        $ex = $matrix[0][0] * $eII + $matrix[0][1] * $eT;
        $ey = $matrix[1][0] * $eII + $matrix[1][1] * $eT;
        return new Point($ex, $ey);
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
        $sides = array();
        $numberOfSides = count($this->vertices) + $this->isClosed - 1;
        $perimeter = 0;
        
        for($s = 0; $s < $numberOfSides; $s++)
        {
            $side = $this->vertices[$s]->getDistanceTo($this->vertices[($s + 1) % count($this->vertices)]);
            array_push($sides, $side);
            $perimeter += $side;
        }
        
        for($s = 0; $s < $numberOfSides; $s++)
        {
            $side = $sides[$s];
            $endpoint1 = $this->vertices[$s];
            $endpoint2 = $this->vertices[($s + 1) % count($this->vertices)];
            $difference1 = $point->copy()->subtractTo($endpoint1);
            $difference2 = $endpoint2->copy()->subtractTo($endpoint1);
            $dotProduct = $difference1->x * $difference2->x + $difference1->y * $difference2->y;
            $magicNumber = $dotProduct / pow($side, 2);
            $closestPoint = new Point($endpoint1->x + $magicNumber * $difference2->x, $endpoint1->y + $magicNumber * $difference2->y);
        }
        
        return;
    }
}

class Flashlight
{
    public $endpoint1;
    public $endpoint2;
    public $numberOfFieldLines;
    
    function __construct($endpoint1, $endpoint2, $numberOfFieldLines)
    {
        $this->endpoint1 = $endpoint1;
        $this->endpoint2 = $endpoint2;
        $this->numberOfFieldLines = $numberOfFieldLines;
    }
}

$elementaryCharge = 1.6021E19;

$fieldLinesPerCharge = 50;
$maxIterationsPerFieldLine = 2000;
$stepPerIteration = 0.001;



$width = 1000;
$height = 1000;
$simulationWidth = 1000;
$simulationHeight = 1000;
$minimumX = 0;
$minimumY = 0;
$maximumX = 1;
$maximumY = 1;
$multiplierX = $simulationWidth / ($maximumX - $minimumX);
$multiplierY = $simulationHeight / ($maximumY - $minimumY);

$charges = array(new PointCharge($elementaryCharge, new Point(0.6, 0.7)), new PointCharge($elementaryCharge, new Point(0.3, 0.1)));
$charges = array();
$lineSegmentCharge = new LineSegmentCharge(10 * $elementaryCharge, new Point(0.4, 0.5), new Point(0.6, 0.5));
array_push($charges, $lineSegmentCharge);
$flashlights = array(new Flashlight(new Point(0, 0.47), new Point(1, 0.47), 100));
$collection = new Collection($charges, $flashlights);

$simulationDraw = new ImagickDraw();
$simulationDraw->translate(($width - $simulationWidth) / 2, ($height - $simulationHeight) / 2);
$simulationDraw->pushClipPath('square');
$simulationDraw->rectangle(0, 0, $simulationWidth, $simulationHeight);
$simulationDraw->popClipPath();
$simulationDraw->setClipPath('square');
$simulationDraw->setStrokeColor('black');
$simulationDraw->setFillOpacity(0);

for($f = 0; $f < count($collection->flashlights); $f++)
{
    $flashlight = $collection->flashlights[$f];
    
    for($l1 = 0; $l1 < $flashlight->numberOfFieldLines; $l1++)
    {
        for($d = 0; $d < 2; $d++)
        {
            $fieldLinePosition = $flashlight->endpoint1->copy()->interpolateToPoint($flashlight->endpoint2, (($flashlight->numberOfFieldLines === 1) ? 0.5 : $l1 / ($flashlight->numberOfFieldLines - 1)));
            $screenCoordinates = virtualPositionToScreenCoordinates($fieldLinePosition);
            $simulationDraw->pathStart();
            $simulationDraw->pathMoveToAbsolute($screenCoordinates[0], $screenCoordinates[1]);
            $doBreak = false;
            
            for($l = 0; $l < $maxIterationsPerFieldLine; $l++)
            {
                $normalizedFieldAtPoint = $collection->getElectricFieldVectorAtPoint($fieldLinePosition)->normalize();
                
                if($d === 0)
                {
                    $fieldLinePosition->addTo($normalizedFieldAtPoint->multiplyBy($stepPerIteration));
                }
                
                else
                {
                    $fieldLinePosition->addTo($normalizedFieldAtPoint->multiplyBy(-$stepPerIteration));
                }
                
                $screenCoordinates = virtualPositionToScreenCoordinates($fieldLinePosition);
                
                for($c = 0; $c < count($charges); $c++)
                {
                    $charge = $charges[$c];
                    
                    if($charge->charge != 0)
                    {
                        if(get_class($charge) === 'PointCharge')
                        {
                            if($fieldLinePosition->getDistanceTo($charges[$c]->position) < $stepPerIteration)
                            {
                                $doBreak = true;
                                break;
                            }
                        }
                    }
                }
                
                $simulationDraw->pathLineToAbsolute($screenCoordinates[0], $screenCoordinates[1]);
                
                if($doBreak)
                {
                    break;
                }
            }
            
            $simulationDraw->pathFinish();
        }
    }
}

/*for($c = 0; $c < count($charges); $c++)
{
    $charge = $charges[$c];
    
    if(get_class($charge) !== 'PointCharge')
    {
        continue;
    }
    
    if($charge->charge !== 0)
    {
        for($l = 0; $l < $fieldLinesPerCharge; $l++)
        {
            $position = $charge->position->copy();
            $direction = $l / $fieldLinesPerCharge * 2 * pi();
            $screenPosition = virtualPositionToScreenCoordinates($position);
            $simulationDraw->pathStart();
            $simulationDraw->pathMoveToAbsolute($screenPosition[0], $screenPosition[1]);
            $position->addToPolar($stepPerIteration, $direction);
            $screenPosition = virtualPositionToScreenCoordinates($position);
            $simulationDraw->pathLineToAbsolute($screenPosition[0], $screenPosition[1]);
            
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
                    
                    if(get_class($charges[$cc]) === 'PointCharge')
                    {// make sure charge is opposite
                        if($position->getDistanceTo($charges[$cc]->position) < $stepPerIteration)
                        {
                            $breakTwice = true;
                            break;
                        }
                    }
                }
                
                $simulationDraw->pathLineToAbsolute($screenPosition[0], $screenPosition[1]);
                
                if($breakTwice)
                {
                    break;
                }
            }
            
            $simulationDraw->pathFinish();
        }
    }
}*/



$simulationDraw->setStrokeWidth(3);
$simulationDraw->setFillOpacity(1);

for($c = 0; $c < count($charges); $c++)
{
    $charge = $charges[$c];
    
    if($charge->charge < 0)
    {
        $simulationDraw->setStrokeColor('#0000ff');
        $simulationDraw->setFillColor('#6666ff');    
    }
    
    else if($charge->charge > 0)
    {
        $simulationDraw->setStrokeColor('#ff0000');
        $simulationDraw->setFillColor('#ff6666'); 
    }
    
    else
    {
        $simulationDraw->setStrokeColor('#888888');
        $simulationDraw->setFillColor('#aaaaaa'); 
    }
    
    if(get_class($charge) === 'PointCharge')
    {
        $screenPosition = virtualPositionToScreenCoordinates($charge->position);
        $simulationDraw->circle($screenPosition[0], $screenPosition[1], $screenPosition[0] + 15, $screenPosition[1]);
    }
    
    else if(get_class($charge) === 'LineSegmentCharge')
    {
        $screenPosition1 = virtualPositionToScreenCoordinates($charge->endpoint1);
        $screenPosition2 = virtualPositionToScreenCoordinates($charge->endpoint2);
        $simulationDraw->line($screenPosition1[0], $screenPosition1[1], $screenPosition2[0], $screenPosition2[1]);
    }
}

$graphDraw = new ImagickDraw();
$graphDraw->translate(($width - $simulationWidth) / 2, ($height - $simulationHeight) / 2);
$graphDraw->setFillColor('#ffffff');
$graphDraw->setFillOpacity(1);
$graphDraw->rectangle(0, 0, $simulationWidth, $simulationHeight);

$image = new Imagick();
$image->newImage($width, $height, 'white');
$image->setImageFormat('png');
$image->drawImage($graphDraw);
$image->drawImage($simulationDraw);
header('Content-Type: image/png');
echo $image;

//$collection->getElectricFieldVectorAtPoint(new Point(0.45, 0.6))->normalize();

/*$shapeCharge = new ShapeCharge(2, new Point(0, 0), array(new Point(0, 0), new Point(10, 0), new Point(10, 10), new Point(0, 10)), true);
array_push($shapeCharge->vertices, new Point(-1, 5));

echo $shapeCharge->getElectricFieldVectorAtPoint(new Point(0, 0));*/

function virtualPositionToScreenCoordinates($position)
{
    global $minimumX, $multiplierX, $simulationHeight, $minimumY, $multiplierY;
    return array(($position->x - $minimumX) * $multiplierX, $simulationHeight - ($position->y - $minimumY) * $multiplierY);
}

function interpolate($startingValue, $endingValue, $t)
{
    return ($startingValue + ($endingValue - $startingValue) * $t);
}

?>