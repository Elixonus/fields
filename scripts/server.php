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
        return pow($this->x - $p->x, 2) + pow($this->y - $p->y, 2);
    }
    
    function getDirectionTo($p)
    {
        return atan2($p->y - $this->y, $p->x - $this->x);
    }
    
    function getDotProductWith($p)
    {
        return $this->x * $p->x + $this->y * $p->y;
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

class FiniteLineCharge
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
        $lineVector = $this->endpoint2->copy()->subtractTo($this->endpoint1);
        $distanceBetweenEndpoints = $lineVector->getMagnitude();
        $relativePositionEndpoint1 = ($lineVector->x * ($this->endpoint1->x - $point->x) + $lineVector->y * ($this->endpoint1->y - $point->y)) / $distanceBetweenEndpoints;
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
            if($relativePositionEndpoint1 <= 0 && $relativePositionEndpoint2 >= 0)
            {
                return INF;
            }
            
            else
            {
                $electricFieldT = 0;
            }
        }
        
        else
        {
            $electricFieldT = 8.9875517923E9 * $chargeDensity / $distanceToProjection * ($relativePositionEndpoint2 / $distanceToEndpoint2 - $relativePositionEndpoint1 / $distanceToEndpoint1);
        }
        
        if(($point->x - $this->endpoint1->x) * $lineVector->y < ($point->y - $this->endpoint1->y) * $lineVector->x)
        {
            return (new Point($lineVector->x * $electricFieldII - $lineVector->y * $electricFieldT, $lineVector->y * $electricFieldII + $lineVector->x * $electricFieldT))->divideBy($distanceBetweenEndpoints);
        }
        
        else
        {
            return (new Point($lineVector->x * $electricFieldII + $lineVector->y * $electricFieldT, $lineVector->y * $electricFieldII - $lineVector->x * $electricFieldT))->divideBy($distanceBetweenEndpoints);
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
    
    function resetRootFieldLinePositions()
    {
        unset($this->rootFieldLinePositions);
    }
    
    function getRootFieldLinePosition($fieldLineNumber)
    {
        if(isset($this->rootFieldLinePositions))
        {
            return $this->rootFieldLinePositions[$fieldLineNumber];
        }
        
        else
        {
            return $this->getRootFieldLinePositions()[$fieldLineNumber];
        }
    }
    
    function getRootFieldLinePositions()
    {
        if(!isset($this->rootFieldLinePositions))
        {
            $this->rootFieldLinePositions = array();
            
            for($p = 0; $p < $this->numberOfFieldLines; $p++)
            {
                array_push($this->rootFieldLinePositions, $this->endpoint1->copy()->interpolateToPoint($this->endpoint2, (($this->numberOfFieldLines === 1) ? 0.5 : $p / ($this->numberOfFieldLines - 1))));
            }
        }
        
        return $this->rootFieldLinePositions;
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
    
    function resetRootFieldLinePositions()
    {
        unset($this->rootFieldLinePositions);
    }
    
    function getRootFieldLinePosition($fieldLineNumber)
    {
        if(isset($this->rootFieldLinePositions))
        {
            return $this->rootFieldLinePositions[$fieldLineNumber];
        }
        
        else
        {
            return $this->getRootFieldLinePositions()[$fieldLineNumber];
        }
    }
    
    function getRootFieldLinePositions()
    {
        if(!isset($this->rootFieldLinePositions))
        {
            $this->rootFieldLinePositions = array();
            
            for($p = 0; $p < $this->numberOfFieldLines; $p++)
            {
                array_push($this->rootFieldLinePositions, $this->position->copy()->addToPolar($this->radius, interpolate(0, 2 * pi(), $p / $this->numberOfFieldLines)));
            }
        }
        
        return $this->rootFieldLinePositions;
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
    
    function resetRootFieldLinePositions()
    {
        unset($this->rootFieldLinePositions);
    }
    
    function getRootFieldLinePosition($fieldLineNumber)
    {
        if(isset($this->rootFieldLinePositions))
        {
            return $this->rootFieldLinePositions[$fieldLineNumber];
        }
        
        else
        {
            return $this->getRootFieldLinePositions()[$fieldLineNumber];
        }
    }
    
    function getRootFieldLinePositions()
    {
        if(!isset($this->rootFieldLinePositions))
        {
            $this->rootFieldLinePositions = array();
            
            for($p = 0; $p < $this->numberOfFieldLines; $p++)
            {
                array_push($this->rootFieldLinePositions, $this->position->copy()->addToPolar($this->radius, interpolate($this->startingAngle, $this->endingAngle, ($this->numberOfFieldLines === 1) ? 0.5 : $p / ($this->numberOfFieldLines - 1))));
            }
        }
        
        return $this->rootFieldLinePositions;
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
                    if(count($dataCharges) <= 100 && count($dataFlashlights) <= 100 && abs($dataStepPerIteration) <= 1E100)
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
                                                if(abs($dataCharge->position->x) <= 1E100 && abs($dataCharge->position->y) <= 1E100)
                                                {
                                                    $dataChargeValid = true;
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                else if($dataCharge->type === 'Finite Line')
                                {
                                    if(property_exists($dataCharge, 'endpoint1') && property_exists($dataCharge, 'endpoint2'))
                                    {
                                        if(property_exists($dataCharge->endpoint1, 'x') && property_exists($dataCharge->endpoint1, 'y') && property_exists($dataCharge->endpoint2, 'x') && property_exists($dataCharge->endpoint2, 'y'))
                                        {
                                            if((is_int($dataCharge->endpoint1->x) || is_float($dataCharge->endpoint1->x)) && (is_int($dataCharge->endpoint1->y) || is_float($dataCharge->endpoint1->y)) && (is_int($dataCharge->endpoint2->x) || is_float($dataCharge->endpoint2->x)) && (is_int($dataCharge->endpoint2->y) || is_float($dataCharge->endpoint2->y)) && ($dataCharge->endpoint1->x != $dataCharge->endpoint2->x || $dataCharge->endpoint1->y != $dataCharge->endpoint2->y))
                                            {
                                                if(abs($dataCharge->endpoint1->x) <= 1E100 && abs($dataCharge->endpoint1->y) <= 1E100 && abs($dataCharge->endpoint2->x) <= 1E100 && abs($dataCharge->endpoint2->y) <= 1E100)
                                                {
                                                    $dataChargeValid = true;
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                if($dataChargeValid)
                                {
                                    $dataChargeValue = $dataCharge->charge;
                                    
                                    if((is_int($dataChargeValue) || is_float($dataChargeValue)))
                                    {
                                        if(abs($dataChargeValue) <= 1E100)
                                        {
                                            continue;
                                        }
                                    }
                                }
                            }
                            
                            $dataChargesValid = false;
                            break;
                        }
                        
                        if($dataChargesValid && $dataMaximumIterationsPerFieldLine >= 0 && $dataStepPerIteration > 0 && $dataMinimumX < $dataMaximumX && $dataMinimumY < $dataMaximumY)
                        {
                            $dataFlashlightsValid = true;
                            $totalNumberOfFieldLines = 0;
                            
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
                                                    if(abs($dataFlashlight->endpoint1->x) <= 1E100 && abs($dataFlashlight->endpoint1->y) <= 1E100 && abs($dataFlashlight->endpoint2->x) <= 1E100 && abs($dataFlashlight->endpoint2->y) <= 1E100)
                                                    {
                                                        $dataFlashlightValid = true;
                                                    }
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
                                                    if(abs($dataFlashlight->position->x) <= 1E100 && abs($dataFlashlight->position->y) <= 1E100 && abs($dataFlashlight->radius) <= 1E100)
                                                    {
                                                        $dataFlashlightValid = true;
                                                    }
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
                                                    if(abs($dataFlashlight->position->x) <= 1E100 && abs($dataFlashlight->position->y) <= 1E100 && abs($dataFlashlight->radius) <= 1E100)
                                                    {
                                                        $dataFlashlightValid = true;
                                                    }
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
                                                $totalNumberOfFieldLines += $dataFlashlight->numberOfFieldLines;
                                                continue;
                                            }
                                        }
                                    }
                                }
                                
                                $dataFlashlightsValid = false;
                                break;
                            }
                            
                            if($dataFlashlightsValid && $totalNumberOfFieldLines * $dataMaximumIterationsPerFieldLine * count($dataCharges) <= 1000000)
                            {
                                $charges = array();
                                
                                for($c = 0; $c < count($dataCharges); $c++)
                                {
                                    $dataCharge = $dataCharges[$c];
                                    
                                    if($dataCharge->type === 'Point')
                                    {
                                        $charge = new PointCharge($dataCharge->charge, new Point($dataCharge->position->x, $dataCharge->position->y));
                                    }
                                    
                                    else if($dataCharge->type === 'Finite Line')
                                    {
                                        $charge = new FiniteLineCharge($dataCharge->charge, new Point($dataCharge->endpoint1->x, $dataCharge->endpoint1->y), new Point($dataCharge->endpoint2->x, $dataCharge->endpoint2->y));
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
                                
                                $image = new Imagick();
                                $image->newImage($width, $height, 'white');
                                $image->setImageFormat('png');
                                $backgroundDraw = new ImagickDraw();
                                $backgroundDraw->setFillColor('#fcfaf5');
                                
                                for($x = 0; $x < $width; $x += 40)
                                {
                                    for($y = 40 * ($x / 40 % 2); $y < $height; $y += 80)
                                    {
                                        $backgroundDraw->rectangle($x, $y, $x + 40, $y + 40);
                                    }
                                }
                                
                                $image->drawImage($backgroundDraw);
                                $backgroundDraw->clear();
                                $electricFieldDraw = new ImagickDraw();
                                $electricFieldDraw->affine(array('sx' => 1, 'sy' => -1, 'rx' => 0, 'ry' => 0, 'tx' => 0, 'ty' => $height));
                                $electricFieldDraw->setStrokeColor('black');
                                $electricFieldDraw->setFillOpacity(0);
                                
                                for($f = 0; $f < count($flashlights); $f++)
                                {
                                    $flashlight = $flashlights[$f];
                                    
                                    for($l1 = 0; $l1 < $flashlight->numberOfFieldLines; $l1++)
                                    {
                                        for($d = 1; $d >= -1; $d -= 2)
                                        {
                                            $fieldLinePosition = $flashlight->getRootFieldLinePosition($l1)->copy();
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
                                                
                                                if($l > 0)
                                                {
                                                    if($previousFieldAtPoint->getDotProductWith($fieldAtPoint) < 0)
                                                    {
                                                        break;
                                                    }
                                                }
                                                
                                                $previousFieldAtPoint = $fieldAtPoint->copy();
                                                $normalizedFieldAtPoint = $fieldAtPoint->normalize();
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
                                $elementsDraw->setStrokeLineCap(Imagick::LINECAP_SQUARE);
                                
                                for($c = 0; $c < count($charges); $c++)
                                {
                                    $charge = $charges[$c];
                                    
                                    if(get_class($charge) === 'PointCharge')
                                    {
                                        $screenCoordinates = virtualPositionToScreenCoordinates($charge->position);
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
                                        
                                        $elementsDraw->setStrokeWidth(3);
                                        $elementsDraw->circle($screenCoordinates[0], $screenCoordinates[1], $screenCoordinates[0] + 15, $screenCoordinates[1]);
                                    }
                                    
                                    else if(get_class($charge) === 'FiniteLineCharge')
                                    {
                                        $screenCoordinates1 = virtualPositionToScreenCoordinates($charge->endpoint1);
                                        $screenCoordinates2 = virtualPositionToScreenCoordinates($charge->endpoint2);
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
                                        
                                        $elementsDraw->setStrokeWidth(8);
                                        $elementsDraw->line($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
                                        
                                        if($charge->charge < 0)
                                        {
                                            $elementsDraw->setStrokeColor('#6666ff');
                                        }
                                        
                                        else if($charge->charge > 0)
                                        {
                                            $elementsDraw->setStrokeColor('#ff6666');
                                        }
                                        
                                        else
                                        {
                                            $elementsDraw->setStrokeColor('#aaaaaa');
                                        }
                                        
                                        $elementsDraw->setStrokeWidth(3);
                                        $elementsDraw->line($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
                                    }
                                }
                                
                                $elementsDraw->setStrokeColor('black');
                                $elementsDraw->setStrokeWidth(2);
                                $elementsDraw->setStrokeDashArray([5, 10]);
                                $elementsDraw->setStrokeLineCap(Imagick::LINECAP_BUTT);
                                $elementsDraw->setFillColor('black');
                                
                                for($f = 0; $f < count($flashlights); $f++)
                                {
                                    $flashlight = $flashlights[$f];
                                    $elementsDraw->setStrokeOpacity(0.3);
                                    $elementsDraw->setStrokeColor('black');
                                    $elementsDraw->setFillOpacity(0);
                                    $elementsDraw->setFillColor('black');
                                    
                                    if(get_class($flashlight) === 'LineSegmentFlashlight')
                                    {
                                        $screenCoordinates1 = virtualPositionToScreenCoordinates($flashlight->endpoint1);
                                        $screenCoordinates2 = virtualPositionToScreenCoordinates($flashlight->endpoint2);
                                        $elementsDraw->line($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
                                    }
                                    
                                    if(get_class($flashlight) === 'CircleFlashlight')
                                    {
                                        $screenCoordinates1 = virtualPositionToScreenCoordinates($flashlight->position->copy()->subtractToCoordinates($flashlight->radius, $flashlight->radius));
                                        $screenCoordinates2 = virtualPositionToScreenCoordinates($flashlight->position->copy()->addToCoordinates($flashlight->radius, $flashlight->radius));
                                        $elementsDraw->arc(max($screenCoordinates1[0], -100 * $width), max($screenCoordinates1[1], -100 * $height), min($screenCoordinates2[0], 101 * $width), min($screenCoordinates2[1], 101 * $height), 0, 360);
                                    }
                                    
                                    if(get_class($flashlight) === 'CircularArcFlashlight')
                                    {
                                        $screenCoordinates1 = virtualPositionToScreenCoordinates($flashlight->position->copy()->subtractToCoordinates($flashlight->radius, $flashlight->radius));
                                        $screenCoordinates2 = virtualPositionToScreenCoordinates($flashlight->position->copy()->addToCoordinates($flashlight->radius, $flashlight->radius));
                                        $elementsDraw->arc(max($screenCoordinates1[0], -100 * $width), max($screenCoordinates1[1], -100 * $height), min($screenCoordinates2[0], 101 * $width), min($screenCoordinates2[1], 101 * $height), 180 / pi() * $flashlight->startingAngle, 180 / pi() * $flashlight->endingAngle);
                                    }
                                    
                                    $elementsDraw->setStrokeOpacity(0);
                                    $elementsDraw->setStrokeColor('black');
                                    $elementsDraw->setFillOpacity(1);
                                    $elementsDraw->setFillColor('black');
                                    
                                    for($p = 0; $p < $flashlight->numberOfFieldLines; $p++)
                                    {
                                        $screenPosition = virtualPositionToScreenCoordinates($flashlight->getRootFieldLinePosition($p));
                                        $elementsDraw->circle($screenPosition[0], $screenPosition[1], $screenPosition[0] + 4, $screenPosition[1]);
                                    }
                                }
                                
                                $image->drawImage($elementsDraw);
                                $elementsDraw->clear();
                                echo base64_encode($image->getImageBlob());
                                
                                //var_dump($collection->getElectricFieldVectorAtPoint(new Point(0.301, 0.6)));
                                //echo '================';
                                //var_dump($collection->getElectricFieldVectorAtPoint(new Point(0.301, 0.4)));
                                
                                /*$electricFieldStrengths = array();
                                
                                for($y = $height; $y > 0; $y--)
                                {
                                    for($x = 0; $x < $width; $x++)
                                    {
                                        $electricField = $collection->getElectricFieldVectorAtPoint(screenCoordinatesToVirtualPosition($x + 0.5, $y + 0.5));
                                        
                                        if($x == 400 && $y == 500)
                                        {
                                            //echo $electricField->getMagnitude().'====';
                                        }
                                        
                                        if($x == 300 && $y == 500)
                                        {
                                            //echo $electricField->getMagnitude().'====';
                                        }
                                        
                                        if($electricField === INF)
                                        {
                                            $electricFieldStrength = $electricField;
                                        }
                                        
                                        else
                                        {
                                            $electricFieldStrength = $electricField->getMagnitude();
                                        }
                                        
                                        array_push($electricFieldStrengths, $electricFieldStrength);
                                    }
                                }
                                
                                $pixels = array();
                                
                                for($p = 0; $p < $width * $height; $p++)
                                {
                                    $pixel = 255 / (1 + exp(-3E6 * $electricFieldStrengths[$p]));
                                    array_push($pixels, $pixel, $pixel, $pixel);
                                }*/
                                
                                //$image->importImagePixels(0, 0, $width, $height, 'RGB', Imagick::PIXEL_CHAR, $pixels);
                                //echo base64_encode($image->getImageBlob());
                                
                                /*
                                $pixels = array();
                                
                                for($p = 0; $p < $width * $height; $p++)
                                {
                                    $pixel = 255 * $orderedElectricFieldStrengths[$electricFieldStrengths[$p]] / ($width * $height);
                                    array_push($pixels, $pixel, $pixel, $pixel);
                                }
                                
                                $image->importImagePixels(0, 0, $width, $height, 'RGB', Imagick::PIXEL_CHAR, $pixels);
                                echo base64_encode($image->getImageBlob());
                                */
    /*                            $cdf = 0;
                                
                                for($e = 0; $e < count($orderedElectricFieldStrengths); $e++)
                                {
                                    $numberOfRepeats = 0;
                                    
                                    while($orderedElectricFieldStrengths[$e] == $orderedElectricFieldStrengths[$e + $numberOfRepeats + 1])
                                    {
                                        $numberOfRepeats++;
                                        
                                        if($e + $numberOfRepeats + 1 === count($orderedElectricFieldStrengths))
                                        {
                                            break;
                                        }
                                    }
                                    
                                    $cdf += $numberOfRepeats + 1;
                                    $cdfs[$e] = $cdf;
                                    $e += $numberOfRepeats;
                                }
                                
                                $CDFS = array();
                                $previousCDF = 0;
                                $orderedElectricFieldStrengths = array(14, 29, 29, 79, 105, 135);
                                
                                for($e = 1; $e < count($orderedElectricFieldStrengths); $e++)
                                {
                                    if($orderedElectricFieldStrengths[$e] != $orderedElectricFieldStrengths[$e - 1])
                                    {
                                        $numberOfRepeats = 1;
                                        $previousCDF += $numberOfRepeats;
                                        $CDFS[$e - 1] = $previousCDF;
                                    }
                                    
                                    else
                                    {
                                        $numberOfRepeats++;
                                    }
                                }
                                
                                $CDFS[count($orderedElectricFieldStrengths) - 1] = $previousCDF;*/
                            }
                        }
                    }
                }
            }
        }
    }
}









/*


getEqualizedNumbers(array(5, 5, 9, 9, 9, 1));
//var_dump(getUniqueSortedCDFS(array(1, 1, 5, 5, 9, 9, 9)));

function getEqualizedNumbers($numbers)
{
    $sortedNumbers = $numbers;
    asort($sortedNumbers);
    
    // $numbers = [5, 5, 9, 9, 9, 1]
    // $sortedNumbers = [1, 5, 5, 9, 9, 9]
    // $sortedUniqueCDFS = [1, 3, 6]
    // $minimumCDF = 1;
    
    var_dump($sortedNumbers);
    $sortedUniqueCDFS = getUniqueSortedCDFS($sortedNumbers);
    $minimumCDF = $sortedUniqueCDFS[0];
    
    // $equalizedNumbers = [0.4, 0.4, 1, 1, 1, 0]
    $sortedEqualizedNumbers = array();
    
    for($n = 0; $n < count($sortedUniqueCDFS); $n++)
    {
        $equalizedNumber = ($sortedUniqueCDFS[$n] - $minimumCDF) / (count($numbers) - $minimumCDF);
        
        for($m = 0; $m < $sortedUniqueCDFS[$n] - ($n === 0 ? 0 : $sortedUniqueCDFS[$n - 1]); $m++)
        {
            array_push($sortedEqualizedNumbers, $equalizedNumber);
        }
    }
    
    for($n = 0; $n < count($numbers); $n++)
    {
        $equalizedNumbers[array_keys($sortedNumbers)[$n]] = $sortedEqualizedNumbers[$n];
    }
    
    ksort($equalizedNumbers);
    return $equalizedNumbers;
}

function getUniqueSortedCDFS($numbers)
{
    $CDFS = array();
    $CDF = 0;
    
    for($n = 0; $n < count($numbers); $n++)
    {
        $numberOfRepeats = 0;
        
        while($numbers[$n] == $numbers[$n + $numberOfRepeats + 1])
        {
            $numberOfRepeats++;
            
            if($n + $numberOfRepeats + 1 === count($numbers))
            {
                break;
            }
        }
        
        $CDF += $numberOfRepeats + 1;
        $CDFS[$n] = $CDF;
        $n += $numberOfRepeats;
    }
    
    return $CDFS;
}*/

function interpolate($startingValue, $endingValue, $value)
{
    return ($startingValue + ($endingValue - $startingValue) * $value);
}

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

?>