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
        array_push($this->charges, $charge);
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
        $totalElectricField = new Point(0, 0);
        
        for($c = 0; $c < count($this->charges); $c++)
        {
            $charge = $this->charges[$c];
            $electricField = $charge->getElectricFieldVectorAtPoint($point);
            
            if($electricField === INF)
            {
                return $electricField;
            }
            
            else
            {
                $totalElectricField->addTo($electricField);
            }
        }
        
        return $totalElectricField;
    }
    
    function getElectricPotentialAtPoint($point)
    {
        $totalElectricPotential = 0;
        
        for($c = 0; $c < count($this->charges); $c++)
        {
            $charge = $this->charges[$c];
            $electricPotential = $charge->getElectricPotentialAtPoint($point);
            
            if(abs($electricPotential) === INF)
            {
                return $electricPotential;
            }
            
            else
            {
                $totalElectricPotential += $electricPotential;
            }
        }
        
        return $totalElectricPotential;
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
            return INF;
        }
        
        else
        {
            $direction = $this->position->getDirectionTo($point);
            $magnitude = 8.9875517923E9 * $this->charge / pow($distanceToPoint, 2);
            return Point::fromPolar($magnitude, $direction);
        }
    }
    
    function getElectricPotentialAtPoint($point)
    {
        $distanceToPoint = $this->position->getDistanceTo($point);
        
        if($distanceToPoint == 0)
        {
            return $this->charge * INF;
        }
        
        else
        {
            return 8.9875517923E9 * $this->charge / $distanceToPoint;
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
            return INF;
        }
        
        $distanceToProjection = sqrt(abs($squaredDistanceToEndpoint1 - pow($relativePositionEndpoint1, 2)));
        $chargeDensity = $this->charge / $distanceBetweenEndpoints;
        
        $electricFieldII = 8.9875517923E9 * $chargeDensity * (1 / $distanceToEndpoint2 - 1 / $distanceToEndpoint1);
        
        if($distanceToProjection == 0)
        {
            $electricFieldT = 0;
        }
        
        else
        {
            $electricFieldT = 8.9875517923E9 * $chargeDensity / $distanceToProjection * ($relativePositionEndpoint2 / $distanceToEndpoint2 - $relativePositionEndpoint1 / $distanceToEndpoint1);
        }
        
        if(($point->x - $this->endpoint1->x) * $lineSegmentVector->y < ($point->y - $this->endpoint1->y) * $lineSegmentVector->x)
        {
            return (new Point($lineSegmentVector->x * $electricFieldII - $lineSegmentVector->y * $electricFieldT, $lineSegmentVector->y * $electricFieldII + $lineSegmentVector->x * $electricFieldT))->divideBy($distanceBetweenEndpoints);
        }
        
        else
        {
            return (new Point($lineSegmentVector->x * $electricFieldII + $lineSegmentVector->y * $electricFieldT, $lineSegmentVector->y * $electricFieldII - $lineSegmentVector->x * $electricFieldT))->divideBy($distanceBetweenEndpoints);
        }
    }
    
    function getElectricPotentialAtPoint($point)
    {
        $lineSegmentVector = $this->endpoint2->copy()->subtractTo($this->endpoint1);
        $distanceBetweenEndpoints = $lineSegmentVector->getMagnitude();
        $relativePositionEndpoint1 = ($lineSegmentVector->x * ($this->endpoint1->x - $point->x) + $lineSegmentVector->y * ($this->endpoint1->y - $point->y)) / $distanceBetweenEndpoints;
        $relativePositionEndpoint2 = $relativePositionEndpoint1 + $distanceBetweenEndpoints;
        $squaredDistanceToEndpoint1 = $point->getSquaredDistanceTo($this->endpoint1);
        $squaredDistanceToEndpoint2 = $point->getSquaredDistanceTo($this->endpoint2);
        $distanceToProjection = sqrt(abs($squaredDistanceToEndpoint1 - pow($relativePositionEndpoint1, 2)));
        $chargeDensity = $this->charge / $distanceBetweenEndpoints;
        
        if($distanceToProjection == 0)
        {
            if($relativePositionEndpoint1 < 0 && $relativePositionEndpoint2 < 0)
            {
                return 8.9875517923E9 * $chargeDensity * log($relativePositionEndpoint1 / $relativePositionEndpoint2);
            }
            
            if($relativePositionEndpoint1 <= 0 && $relativePositionEndpoint2 >= 0)
            {
                return $this->charge * INF;
            }
            
            if($relativePositionEndpoint1 > 0 && $relativePositionEndpoint2 > 0)
            {
                return 8.9875517923E9 * $chargeDensity * log($relativePositionEndpoint2 / $relativePositionEndpoint1);
            }
        }
        
        $distanceToEndpoint1 = sqrt($squaredDistanceToEndpoint1);
        $distanceToEndpoint2 = sqrt($squaredDistanceToEndpoint2);
        
        if($relativePositionEndpoint1 < 0 && $relativePositionEndpoint2 < 0)
        {
            return -8.9875517923E9 * $chargeDensity * log(abs(($distanceToEndpoint2 - $relativePositionEndpoint2) / ($distanceToEndpoint1 - $relativePositionEndpoint1)));
        }
        
        else
        {
            return 8.9875517923E9 * $chargeDensity * log(abs(($relativePositionEndpoint2 + $distanceToEndpoint2) / ($relativePositionEndpoint1 + $distanceToEndpoint1)));
        }
    }
}

class LineSegmentFlashlight
{
    public $endpoint1;
    public $endpoint2;
    public $numberOfFieldLines;
    
    function __construct($endpoint1, $endpoint2, $numberOfFieldLines)
    {
        $this->endpoint1 = $endpoint1;
        $this->endpoint2 = $endpoint2;
        $this->numberOfFieldLines = $numberOfFieldLines;
        return $this;
    }
}

class CircleFlashlight
{
    public $position;
    public $radius;
    public $numberOfFieldLines;
    
    function __construct($position, $radius, $numberOfFieldLines)
    {
        $this->position = $position;
        $this->radius = $radius;
        $this->numberOfFieldLines = $numberOfFieldLines;
        return $this;
    }
}

class CircularArcFlashlight
{
    public $position;
    public $radius;
    public $startingAngle;
    public $endingAngle;
    public $numberOfFieldLines;
    
    function __construct($position, $radius, $startingAngle, $endingAngle, $numberOfFieldLines)
    {
        $this->position = $position;
        $this->radius = $radius;
        $this->startingAngle = $startingAngle;
        $this->endingAngle = $endingAngle;
        $this->numberOfFieldLines = $numberOfFieldLines;
        return $this;
    }
}

$elementaryCharge = 1.6021E-19;
$maxIterationsPerFieldLine = 2000;
$stepPerIteration = 0.1;

$width = 1000;
$height = 1000;
$minimumX = -80;
$minimumY = -80;
$maximumX = 80;
$maximumY = 80;
$multiplierX = $width / ($maximumX - $minimumX);
$multiplierY = $height / ($maximumY - $minimumY);

$charges = array(new PointCharge(-$elementaryCharge, new Point(-10, -30)), new LineSegmentCharge(-$elementaryCharge, new Point(-20, -40), new Point(-20, 40)), new LineSegmentCharge($elementaryCharge, new Point(20, -40), new Point(20, 40)));
$flashlights = array(new CircleFlashlight(new Point(0, 0), 70, 30), new LineSegmentFlashlight(new Point(0, -60), new Point(0, 60), 20));
$collection = new Collection($charges, $flashlights);

$image = new Imagick();
$image->newImage($width, $height, 'white');

$electricPotentials = array();

for($y = $height; $y > 0; $y--)
{
    for($x = 0; $x < $width; $x++)
    {
        $electricPotential = $collection->getElectricPotentialAtPoint(screenCoordinatesToVirtualPosition($x, $y));
        array_push($electricPotentials, $electricPotential);
        
        
        if(screenCoordinatesToVirtualPosition($x, $y)->x == -20)
        {
            //echo $electricPotential.', '.screenCoordinatesToVirtualPosition($x, $y)->y.'<br>';
        }
        
        
        
        if(abs($electricPotential) === INF)
        {
            continue;
        }
        
        if($x === 0 && $y === 0)
        {
            $minimumElectricPotential = $electricPotential;
            $maximumElectricPotential = $electricPotential;
        }
        
        else if($electricPotential < $minimumElectricPotential)
        {
            $minimumElectricPotential = $electricPotential;
        }
        
        else if($electricPotential > $maximumElectricPotential)
        {
            $maximumElectricPotential = $electricPotential;
        }
    }
}

$minimumElectricPotential = -1E-10;
$maximumElectricPotential = 1E-10;
$colorValues = array();

$pixel = new ImagickPixel();

for($p = 0; $p < count($electricPotentials); $p++)
{
    $electricPotential = $electricPotentials[$p];
    
    if($electricPotential === -INF)
    {
        $colorValue = 0;
    }
    
    else if($electricPotential === INF)
    {
        $colorValue = 255;
    }
    
    else
    {
        $colorValue = 255 * max(min(($electricPotential - $minimumElectricPotential) / ($maximumElectricPotential - $minimumElectricPotential), 1), 0);
    }
    
    $pixel->setHSL(-($colorValue / 255) / 2 + 1, 1, 0.5);
    $get = $pixel->getColor();
    array_push($colorValues, $get['r'], $get['g'], $get['b']);
}

$image->importImagePixels(0, 0, $width, $height, 'RGB', Imagick::PIXEL_CHAR, $colorValues);

$electricFieldDraw = new ImagickDraw();
$electricFieldDraw->affine(array('sx' => 1, 'sy' => -1, 'rx' => 0, 'ry' => 0, 'tx' => 0, 'ty' => $height));
$electricFieldDraw->setStrokeColor('black');
$electricFieldDraw->setFillOpacity(0);

for($f = 0; $f < count($collection->flashlights); $f++)
{
    $flashlight = $collection->flashlights[$f];
    
    for($l1 = 0; $l1 < $flashlight->numberOfFieldLines; $l1++)
    {
        for($d = 1; $d >= -1; $d -= 2)
        {
            if(get_class($flashlight) === 'LineSegmentFlashlight')
            {
                $fieldLinePosition = $flashlight->endpoint1->copy()->interpolateToPoint($flashlight->endpoint2, (($flashlight->numberOfFieldLines === 1) ? 0.5 : $l1 / ($flashlight->numberOfFieldLines - 1)));
            }
            
            else if(get_class($flashlight) === 'CircleFlashlight')
            {
                $fieldLinePosition = $flashlight->position->copy()->addToPolar($flashlight->radius, interpolate(0, 2 * pi(), $l1 / $flashlight->numberOfFieldLines));
            }
            
            else if(get_class($flashlight) === 'CircularArcFlashlight')
            {
                $fieldLinePosition = $flashlight->position->copy()->addToPolar($flashlight->radius, interpolate($flashlight->startingAngle, $flashlight->endingAngle, ($flashlight->numberOfFieldLines === 1) ? 0.5 : $l1 / ($flashlight->numberOfFieldLines - 1)));
            }
            
            $screenCoordinates = virtualPositionToScreenCoordinates($fieldLinePosition);
            $electricFieldDraw->pathStart();
            $electricFieldDraw->pathMoveToAbsolute($screenCoordinates[0], $screenCoordinates[1]);
            
            for($l = 0; $l < $maxIterationsPerFieldLine; $l++)
            {
                $normalizedFieldAtPoint = $collection->getElectricFieldVectorAtPoint($fieldLinePosition)->normalize();
                
                if($normalizedFieldAtPoint === INF)
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
                $electricFieldDraw->pathLineToAbsolute($screenCoordinates[0], $screenCoordinates[1]);
            }
            
            $electricFieldDraw->pathFinish();
        }
    }
}

$image->drawImage($electricFieldDraw);
$electricFieldDraw->clear();
$elementsDraw = new ImagickDraw();
$elementsDraw->affine(array('sx' => 1, 'sy' => -1, 'rx' => 0, 'ry' => 0, 'tx' => 0, 'ty' => $height));
$elementsDraw->setStrokeWidth(3);

for($c = 0; $c < count($charges); $c++)
{
    $charge = $charges[$c];
    
    if(get_class($charge) === 'PointCharge')
    {
        $elementsDraw->setFillOpacity(1);
        
        if($charge->charge < 0)
        {
            $elementsDraw->setStrokeColor('#0000ff');
            $elementsDraw->setFillColor('#6666ff');
        }
        
        else if($charge->charge > 0)
        {
            $elementsDraw->setStrokeColor('#ff0000');
            $elementsDraw->setFillColor('#ff6666');
        }
        
        else
        {
            $elementsDraw->setStrokeColor('#888888');
            $elementsDraw->setFillColor('#aaaaaa');
        }
        
        $screenCoordinates = virtualPositionToScreenCoordinates($charge->position);
        $elementsDraw->circle($screenCoordinates[0], $screenCoordinates[1], $screenCoordinates[0] + 15, $screenCoordinates[1]);
    }
    
    else if(get_class($charge) === 'LineSegmentCharge')
    {
        $elementsDraw->setFillOpacity(0);
        
        if($charge->charge < 0)
        {
            $elementsDraw->setStrokeColor('#0000ff');
        }
        
        else if($charge->charge > 0)
        {
            $elementsDraw->setStrokeColor('#ff0000');
        }
        
        else
        {
            $elementsDraw->setStrokeColor('#888888');
        }
        
        $screenCoordinates1 = virtualPositionToScreenCoordinates($charge->endpoint1);
        $screenCoordinates2 = virtualPositionToScreenCoordinates($charge->endpoint2);
        $elementsDraw->line($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
    }
}

$elementsDraw->setStrokeLineCap(Imagick::LINECAP_SQUARE);
$elementsDraw->setFillOpacity(0);
$elementsDraw->setFillColor('black');

for($f = 0; $f < count($flashlights); $f++)
{
    $flashlight = $flashlights[$f];
    
    if(get_class($flashlight) === 'LineSegmentFlashlight')
    {
        $screenCoordinates1 = virtualPositionToScreenCoordinates($flashlight->endpoint1);
        $screenCoordinates2 = virtualPositionToScreenCoordinates($flashlight->endpoint2);
        $elementsDraw->setStrokeColor('black');
        $elementsDraw->setStrokeWidth(10);
        $elementsDraw->line($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
        $elementsDraw->setStrokeColor('yellow');
        $elementsDraw->setStrokeWidth(4);
        $elementsDraw->line($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
    }
    
    if(get_class($flashlight) === 'CircleFlashlight')
    {
        $screenCoordinates1 = virtualPositionToScreenCoordinates($flashlight->position);
        $screenCoordinates2 = virtualPositionToScreenCoordinates($flashlight->position->copy()->addToCoordinates($flashlight->radius, 0));
        $elementsDraw->setStrokeColor('black');
        $elementsDraw->setStrokeWidth(10);
        $elementsDraw->circle($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
        $elementsDraw->setStrokeColor('yellow');
        $elementsDraw->setStrokeWidth(4);
        $elementsDraw->circle($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
    }
    
    if(get_class($flashlight) === 'CircularArcFlashlight')
    {
        $screenCoordinates1 = virtualPositionToScreenCoordinates($flashlight->position->copy()->subtractToCoordinates($flashlight->radius, $flashlight->radius));
        $screenCoordinates2 = virtualPositionToScreenCoordinates($flashlight->position->copy()->addToCoordinates($flashlight->radius, $flashlight->radius));
        $elementsDraw->setStrokeColor('black');
        $elementsDraw->setStrokeWidth(10);
        $elementsDraw->arc($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1], 180 / pi() * $flashlight->startingAngle, 180 / pi() * $flashlight->endingAngle);
        $elementsDraw->setStrokeColor('yellow');
        $elementsDraw->setStrokeWidth(4);
        $elementsDraw->arc($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1], 180 / pi() * $flashlight->startingAngle, 180 / pi() * $flashlight->endingAngle);
    }
}

$image->drawImage($elementsDraw);
$elementsDraw->clear();
$image->setImageFormat('png');
header('Content-Type: image/png');
echo $image;
//$lineCharge = $charges[0];
//echo $lineCharge->getElectricPotentialAtPoint(new Point(-20, 62.4));
//echo $maximumElectricPotential;

function virtualPositionToScreenCoordinates($position)
{
    global $minimumX, $multiplierX, $minimumY, $multiplierY;
    return array(($position->x - $minimumX) * $multiplierX, ($position->y - $minimumY) * $multiplierY);
}

function screenCoordinatesToVirtualPosition($x, $y)
{
    global $minimumX, $multiplierX, $minimumY, $multiplierY;
    return new Point($x / $multiplierX + $minimumX, $y / $multiplierY + $minimumY);
}

function interpolate($startingValue, $endingValue, $t)
{
    return ($startingValue + ($endingValue - $startingValue) * $t);
}

?>