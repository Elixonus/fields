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

if(!empty(file_get_contents('php://input')))
{
    $data = json_decode(file_get_contents('php://input'));
    
    if(json_last_error() === JSON_ERROR_NONE)
    {
        if(property_exists($data, 'input') && property_exists($data, 'output'))
        {
            $dataInput = $data->input;
            $dataOutput = $data->output;
            
            if(property_exists($dataInput, 'charges') && property_exists($dataInput, 'flashlights') && property_exists($dataOutput, 'maximumIterationsPerFieldLine') && property_exists($dataOutput, 'stepPerIteration') && property_exists($dataOutput, 'minimumX') && property_exists($dataOutput, 'minimumY') && property_exists($dataOutput, 'maximumX') && property_exists($dataOutput, 'maximumY'));
            {
                $dataCharges = $dataInput->charges;
                $dataFlashlights = $dataInput->flashlights;
                $dataMaximumIterationsPerFieldLine = $dataOutput->maximumIterationsPerFieldLine;
                $dataStepPerIteration = $dataOutput->stepPerIteration;
                $dataMinimumX = $dataOutput->minimumX;
                $dataMinimumY = $dataOutput->minimumY;
                $dataMaximumX = $dataOutput->maximumX;
                $dataMaximumY = $dataOutput->maximumY;
                
                if(is_array($dataCharges) && is_array($dataFlashlights) && is_int($dataMaximumIterationsPerFieldLine) && (is_int($dataStepPerIteration) || is_float($dataStepPerIteration)) && (is_int($dataMinimumX) || is_float($dataMinimumX)) && (is_int($dataMinimumY) || is_float($dataMinimumY)) && (is_int($dataMaximumX) || is_float($dataMaximumX)) && (is_int($dataMaximumY) || is_float($dataMaximumY)))
                {
                    $dataChargesValid = true;
                    
                    for($c = 0; $c < count($dataCharges); $c++)
                    {
                        $dataCharge = $dataCharges[$c];
                        
                        if(property_exists($dataCharge, 'type') && property_exists($dataCharge, 'charge'))
                        {
                            $dataChargeValid = false;
                            
                            if($dataCharge->type === 'Point')
                            {
                                if(property_exists($dataCharge, 'position'))
                                {
                                    if(property_exists($dataCharge->position, 'x') && property_exists($dataCharge->position, 'y'))
                                    {
                                        if((is_int($dataCharge->position->x) || is_float($dataCharge->position->x)) && (is_int($dataCharge->position->y) || is_float($dataCharge->position->y)))
                                        {
                                            $dataChargeValid = true;
                                        }
                                    }
                                }
                            }
                            
                            else if($dataCharge->type === 'Line Segment')
                            {
                                if(property_exists($dataCharge, 'endpoint1') && property_exists($dataCharge, 'endpoint2'))
                                {
                                    if(property_exists($dataCharge->endpoint1, 'x') && property_exists($dataCharge->endpoint1, 'y') && property_exists($dataCharge->endpoint2, 'x') && property_exists($dataCharge->endpoint2, 'y'))
                                    {
                                        if((is_int($dataCharge->endpoint1->x) || is_float($dataCharge->endpoint1->x)) && (is_int($dataCharge->endpoint1->y) || is_float($dataCharge->endpoint1->y)) && (is_int($dataCharge->endpoint2->x) || is_float($dataCharge->endpoint2->x)) && (is_int($dataCharge->endpoint2->y) || is_float($dataCharge->endpoint2->y)) && ($dataCharge->endpoint1->x != $dataCharge->endpoint2->x || $dataCharge->endpoint1->y != $dataCharge->endpoint2->y))
                                        {
                                            $dataChargeValid = true;
                                        }
                                    }
                                }
                            }
                            
                            if($dataChargeValid)
                            {
                                $dataChargeValue = $dataCharge->charge;
                                
                                if((is_int($dataChargeValue) || is_float($dataChargeValue)))
                                {
                                    continue;
                                }
                            }
                        }
                        
                        $dataChargesValid = false;
                        break;
                    }
                    
                    if($dataChargesValid && $dataMaximumIterationsPerFieldLine >= 0 && $dataStepPerIteration > 0 && $dataMinimumX < $dataMaximumX && $dataMinimumY < $dataMaximumY)
                    {
                        $dataFlashlightsValid = true;
                        
                        for($f = 0; $f < count($dataFlashlights); $f++)
                        {
                            $dataFlashlight = $dataFlashlights[$f];
                            
                            if(property_exists($dataFlashlight, 'type') && property_exists($dataFlashlight, 'numberOfFieldLines'))
                            {
                                $dataFlashlightValid = false;
                                
                                if($dataFlashlight->type === 'Line Segment')
                                {
                                    if(property_exists($dataFlashlight, 'endpoint1') && property_exists($dataFlashlight, 'endpoint2'))
                                    {
                                        if(property_exists($dataFlashlight->endpoint1, 'x') && property_exists($dataFlashlight->endpoint1, 'y') && property_exists($dataFlashlight->endpoint2, 'x') && property_exists($dataFlashlight->endpoint2, 'y'))
                                        {
                                            if((is_int($dataFlashlight->endpoint1->x) || is_float($dataFlashlight->endpoint1->x)) && (is_int($dataFlashlight->endpoint1->y) || is_float($dataFlashlight->endpoint1->y)) && (is_int($dataFlashlight->endpoint2->x) || is_float($dataFlashlight->endpoint2->x)) && (is_int($dataFlashlight->endpoint2->y) || is_float($dataFlashlight->endpoint2->y)) && ($dataFlashlight->endpoint1->x != $dataFlashlight->endpoint2->x || $dataFlashlight->endpoint1->y != $dataFlashlight->endpoint2->y))
                                            {
                                                $dataFlashlightValid = true;
                                            }
                                        }
                                    }
                                }
                                
                                else if($dataFlashlight->type === 'Circle')
                                {
                                    if(property_exists($dataFlashlight, 'position') && property_exists($dataFlashlight, 'radius'))
                                    {
                                        if(property_exists($dataFlashlight->position, 'x') && property_exists($dataFlashlight->position, 'y') && (is_int($dataFlashlight->radius) || is_float($dataFlashlight->radius)))
                                        {
                                            if((is_int($dataFlashlight->position->x) || is_float($dataFlashlight->position->x)) && (is_int($dataFlashlight->position->y) || is_float($dataFlashlight->position->y)) && $dataFlashlight->radius > 0)
                                            {
                                                $dataFlashlightValid = true;
                                            }
                                        }
                                    }
                                }
                                
                                else if($dataFlashlight->type === 'Circular Arc')
                                {
                                    if(property_exists($dataFlashlight, 'position') && property_exists($dataFlashlight, 'radius') && property_exists($dataFlashlight, 'startingAngle') && property_exists($dataFlashlight, 'endingAngle'))
                                    {
                                        if(property_exists($dataFlashlight->position, 'x') && property_exists($dataFlashlight->position, 'y') && (is_int($dataFlashlight->radius) || is_float($dataFlashlight->radius)) && (is_int($dataFlashlight->startingAngle) || is_float($dataFlashlight->startingAngle)) && (is_int($dataFlashlight->endingAngle) || is_float($dataFlashlight->endingAngle)))
                                        {
                                            if((is_int($dataFlashlight->position->x) || is_float($dataFlashlight->position->x)) && (is_int($dataFlashlight->position->y) || is_float($dataFlashlight->position->y)) && $dataFlashlight->radius > 0 && $dataFlashlight->startingAngle >= 0 && $dataFlashlight->endingAngle <= 360 && $dataFlashlight->startingAngle < $dataFlashlight->endingAngle)
                                            {
                                                $dataFlashlightValid = true;
                                            }
                                        }
                                    }
                                }
                                
                                if($dataFlashlightValid)
                                {
                                    if(is_int($dataFlashlight->numberOfFieldLines))
                                    {
                                        if($dataFlashlight->numberOfFieldLines >= 0)
                                        {
                                            continue;
                                        }
                                    }
                                }
                            }
                            
                            $dataFlashlightsValid = false;
                            break;
                        }
                        
                        if($dataFlashlightsValid)
                        {
                            $charges = array();
                            
                            for($c = 0; $c < count($dataCharges); $c++)
                            {
                                $dataCharge = $dataCharges[$c];
                                
                                if($dataCharge->type === 'Point')
                                {
                                    $charge = new PointCharge($dataCharge->charge, new Point($dataCharge->position->x, $dataCharge->position->y));
                                }
                                
                                else if($dataCharge->type === 'Line Segment')
                                {
                                    $charge = new LineSegmentCharge($dataCharge->charge, new Point($dataCharge->endpoint1->x, $dataCharge->endpoint1->y), new Point($dataCharge->endpoint2->x, $dataCharge->endpoint2->y));
                                }
                                
                                array_push($charges, $charge);
                            }
                            
                            $flashlights = array();
                            
                            for($f = 0; $f < count($dataFlashlights); $f++)
                            {
                                $dataFlashlight = $dataFlashlights[$f];
                                
                                if($dataFlashlight->type === 'Line Segment')
                                {
                                    $flashlight = new LineSegmentFlashlight(new Point($dataFlashlight->endpoint1->x, $dataFlashlight->endpoint1->y), new Point($dataFlashlight->endpoint2->x, $dataFlashlight->endpoint2->y), $dataFlashlight->numberOfFieldLines);
                                }
                                
                                else if($dataFlashlight->type === 'Circle')
                                {
                                    $flashlight = new CircleFlashlight(new Point($dataFlashlight->position->x, $dataFlashlight->position->y), $dataFlashlight->radius, $dataFlashlight->numberOfFieldLines);
                                }
                                
                                else if($dataFlashlight->type === 'Circular Arc')
                                {
                                    $flashlight = new CircularArcFlashlight(new Point($dataFlashlight->position->x, $dataFlashlight->position->y), $dataFlashlight->radius, pi() / 180 * $dataFlashlight->startingAngle, pi() / 180 * $dataFlashlight->endingAngle, $dataFlashlight->numberOfFieldLines);
                                }
                                
                                array_push($flashlights, $flashlight);
                            }
                            
                            $collection = new Collection($charges, $flashlights);
                            
                            $maximumIterationsPerFieldLine = $dataMaximumIterationsPerFieldLine;
                            $stepPerIteration = $dataStepPerIteration;
                            $width = 1000;
                            $height = 1000;
                            $minimumX = $dataMinimumX;
                            $minimumY = $dataMinimumY;
                            $maximumX = $dataMaximumX;
                            $maximumY = $dataMaximumY;
                            $multiplierX = $width / ($maximumX - $minimumX);
                            $multiplierY = $height / ($maximumY - $minimumY);
                            
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
                                        
                                        for($l = 0; $l < $maximumIterationsPerFieldLine; $l++)
                                        {
                                            $fieldAtPoint = $collection->getElectricFieldVectorAtPoint($fieldLinePosition);
                                            
                                            if(($fieldAtPoint->x == 0 && $fieldAtPoint->y == 0) || $fieldAtPoint === INF)
                                            {
                                                break;
                                            }
                                            
                                            $normalizedFieldAtPoint = $fieldAtPoint->normalize();
                                            
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
                            
                            $image = new Imagick();
                            $image->newImage($width, $height, 'white');
                            $image->setImageFormat('png');
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
                                    $screenCoordinates2 = virtualPositionToScreenCoordinates($flashlight->position->copy()->addToCoordinates($flashlight->radius, $flashlight->radius));
                                    $elementsDraw->setStrokeColor('black');
                                    $elementsDraw->setStrokeWidth(10);
                                    $elementsDraw->ellipse($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0] - $screenCoordinates1[0], $screenCoordinates2[1] - $screenCoordinates1[1], 0, 360);
                                    $elementsDraw->setStrokeColor('yellow');
                                    $elementsDraw->setStrokeWidth(4);
                                    $elementsDraw->ellipse($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0] - $screenCoordinates1[0], $screenCoordinates2[1] - $screenCoordinates1[1], 0, 360);
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
                            header('Content-Type: image/png');
                            echo $image;
                        }
                    }
                }
            }
        }
    }
}

function interpolate($startingValue, $endingValue, $value)
{
    return ($startingValue + ($endingValue - $startingValue) * $value);
}

function virtualPositionToScreenCoordinates($position)
{
    global $minimumX, $multiplierX, $minimumY, $multiplierY;
    return array(($position->x - $minimumX) * $multiplierX, ($position->y - $minimumY) * $multiplierY);
}

?>