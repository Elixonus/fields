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
    
    function getSquaredDistanceTo($p)
    {
        return (pow($this->x - $p->x, 2) + pow($this->y - $p->y, 2));
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

class Matrix
{
    public $data;
    
    function __construct($data)
    {
        $this->data = $data;
        return $this;
    }
    
    static function fromFill($number, $rows, $columns)
    {
        $matrixData = array();
        
        for($m = 0; $m < $rows; $m++)
        {
            array_push($matrixData, array());
            
            for($n = 0; $n < $columns; $n++)
            {
                array_push($matrixData[$m], $number);
            }
        }
        
        return new Matrix($matrixData);
    }
    
    function changeTo($matrix)
    {
        $this->data = $matrix->data;
        return $this;
    }
    
    function addTo($matrix)
    {
        for($m = 0; $m < count($this->data); $m++)
        {
            for($n = 0; $n < count($this->data[$m]); $n++)
            {
                $this->data[$m][$n] += $matrix->data[$m][$n];
            }
        }
        
        return $this;
    }
    
    function subtractTo($matrix)
    {
        for($m = 0; $m < count($this->data); $m++)
        {
            for($n = 0; $n < count($this->data[$m]); $n++)
            {
                $this->data[$m][$n] -= $matrix->data[$m][$n];
            }
        }
        
        return $this;
    }
    
    function multiplyBy($number)
    {
        for($m = 0; $m < count($this->data); $m++)
        {
            for($n = 0; $n < count($this->data[$m]); $n++)
            {
                $this->data[$m][$n] *= $number;
            }
        }
        
        return $this;
    }
    
    function divideBy($number)
    {
        for($m = 0; $m < count($this->data); $m++)
        {
            for($n = 0; $n < count($this->data[$m]); $n++)
            {
                $this->data[$m][$n] /= $number;
            }
        }
        
        return $this;
    }
    
    function getMatrixMultiplyBy($matrix)
    {
        $newMatrixData = array();
        
        for($m = 0; $m < count($this->data); $m++)
        {
            array_push($newMatrixData, array());
            
            for($n = 0; $n < count($matrix->data[0]); $n++)
            {
                $sum = 0;
                
                for($k = 0; $k < count($this->data[0]); $k++)
                {
                    $sum += $this->data[$m][$k] * $matrix->data[$k][$n];
                }
                
                array_push($newMatrixData[$m], $sum);
            }
        }
        
        return new Matrix($newMatrixData);
    }
    
    function getDotProductWith($matrix)
    {
        $sum = 0;
        
        for($k = 0; $k < count($this->data); $k++)
        {
            $sum += $this->data[$k] * $matrix->data[$k];
        }
        
        return $sum;
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
            return new Point(0, 0);
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
        $lineSegmentVector = $this->endpoint2->copy()->subtractTo($this->endpoint1);
        $distanceBetweenEndpoints = $lineSegmentVector->getMagnitude();
        $relativePositionEndpoint1 = ($lineSegmentVector->x * ($this->endpoint1->x - $point->x) + $lineSegmentVector->y * ($this->endpoint1->y - $point->y)) / $distanceBetweenEndpoints;
        $relativePositionEndpoint2 = $relativePositionEndpoint1 + $distanceBetweenEndpoints;
        $squaredDistanceToEndpoint1 = $point->getSquaredDistanceTo($this->endpoint1);
        $squaredDistanceToEndpoint2 = $point->getSquaredDistanceTo($this->endpoint2);
        $distanceToEndpoint1 = sqrt($squaredDistanceToEndpoint1);
        $distanceToEndpoint2 = sqrt($squaredDistanceToEndpoint2);
        
        if($distanceToEndpoint1 == 0 || $distanceToEndpoint2 == 0)
        {
            return new Point(0, 0);
        }
        
        $distanceToProjection = sqrt($squaredDistanceToEndpoint1 - pow($relativePositionEndpoint1, 2));
        $chargeDensity = $this->charge / $distanceBetweenEndpoints;
        
        $electricFieldII = $this->charge * $chargeDensity * (1 / $distanceToEndpoint2 - 1 / $distanceToEndpoint1);
        
        if($distanceToProjection == 0)
        {
            $electricFieldT = 0;
        }
        
        else
        {
            $electricFieldT = $this->charge * $chargeDensity / $distanceToProjection * ($relativePositionEndpoint2 / $distanceToEndpoint2 - $relativePositionEndpoint1 / $distanceToEndpoint1);
        }
        
        if(($point->x - $this->endpoint1->x) * $lineSegmentVector->y < ($point->y - $this->endpoint1->y) * $lineSegmentVector->x)
        {
            $isPointAboveLineSegment = true;
        }
        
        else
        {
            $isPointAboveLineSegment = false;
        }
        
        return (new Point($lineSegmentVector->x * $electricFieldII + (1 - 2 * $isPointAboveLineSegment) * $lineSegmentVector->y * $electricFieldT, $lineSegmentVector->y * $electricFieldII + (2 * $isPointAboveLineSegment - 1) * $lineSegmentVector->x * $electricFieldT))->divideBy($distanceBetweenEndpoints);
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
$maxIterationsPerFieldLine = 1000;
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
$charges = array(new PointCharge($elementaryCharge / 10, new Point(0.3, 0.7)));
$charges = array();
$lineSegmentCharge = new LineSegmentCharge(1 * $elementaryCharge, new Point(0.5, 0.5), new Point(1, 0.5));
array_push($charges, $lineSegmentCharge);
$flashlights = array(new Flashlight(new Point(0.3, 0.5), new Point(0.3, 0.8), 30));
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
        for($d = -1; $d >= -1; $d -= 2)
        {
            $fieldLinePosition = $flashlight->endpoint1->copy()->interpolateToPoint($flashlight->endpoint2, (($flashlight->numberOfFieldLines === 1) ? 0.5 : $l1 / ($flashlight->numberOfFieldLines - 1)));
            $screenCoordinates = virtualPositionToScreenCoordinates($fieldLinePosition);
            $simulationDraw->pathStart();
            $simulationDraw->pathMoveToAbsolute($screenCoordinates[0], $screenCoordinates[1]);
            
            for($l = 0; $l < $maxIterationsPerFieldLine; $l++)
            {
                $normalizedFieldAtPoint = $collection->getElectricFieldVectorAtPoint($fieldLinePosition)->normalize();
                
                if($normalizedFieldAtPoint->x == 0 && $normalizedFieldAtPoint->y == 0)
                {
                    break;
                }
                
                if($l > 0)
                {
                    if($previousNormalizedFieldAtPoint->x * $normalizedFieldAtPoint->x + $previousNormalizedFieldAtPoint->y * $normalizedFieldAtPoint->y < 0)
                    {
                        break;
                    }
                }
                
                $previousNormalizedFieldAtPoint = $normalizedFieldAtPoint->copy();
                $fieldLinePosition->addTo($normalizedFieldAtPoint->multiplyBy($stepPerIteration)->multiplyBy($d));
                $screenCoordinates = virtualPositionToScreenCoordinates($fieldLinePosition);
                $simulationDraw->pathLineToAbsolute($screenCoordinates[0], $screenCoordinates[1]);
            }
            
            $simulationDraw->pathFinish();
        }
    }
}

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

$screenPosition1 = virtualPositionToScreenCoordinates($flashlights[0]->endpoint1);
$screenPosition2 = virtualPositionToScreenCoordinates($flashlights[0]->endpoint2);
$simulationDraw->line($screenPosition1[0], $screenPosition1[1], $screenPosition2[0], $screenPosition2[1]);

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