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
    
    function rotate($r)
    {
        $cos = cos($r);
        $sin = sin($r);
        $this->x = $cos * $this->x - $sin * $this->y;
        $this->y = $sin * $this->x + $cos * $this->y;
        return $this;
    }
    
    function rotateAround($p, $r)
    {
        $cos = cos($r);
        $sin = sin($r);
        $dx = $this->x - $p->x;
        $dy = $this->y - $p->y;
        $this->x = $cos * $dx - $sin * $dy + $p->x;
        $this->y = $sin * $dx + $cos * $dy + $p->y;
        return $this;
    }
    
    function normalize()
    {
        return $this->divideBy($this->getMagnitude());
    }
    
    function interpolateTo($p, $t)
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
    
    function addChargeIndex($charge, $index)
    {
        array_splice($this->charges, $index, 0, $charge);
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
        
        foreach($this->charges as $charge)
        {
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
        
        foreach($this->charges as $charge)
        {
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
    
    function getElectricPotentialAtPoint($point)
    {
        $lineVector = $this->endpoint2->copy()->subtractTo($this->endpoint1);
        $distanceBetweenEndpoints = $lineVector->getMagnitude();
        $relativePositionEndpoint1 = ($lineVector->x * ($this->endpoint1->x - $point->x) + $lineVector->y * ($this->endpoint1->y - $point->y)) / $distanceBetweenEndpoints;
        $relativePositionEndpoint2 = $relativePositionEndpoint1 + $distanceBetweenEndpoints;
        $squaredDistanceToEndpoint1 = $point->getSquaredDistanceTo($this->endpoint1);
        $squaredDistanceToEndpoint2 = $point->getSquaredDistanceTo($this->endpoint2);
        $distanceToEndpoint1 = sqrt($squaredDistanceToEndpoint1);
        $distanceToEndpoint2 = sqrt($squaredDistanceToEndpoint2);
        $distanceToProjection = sqrt(abs($squaredDistanceToEndpoint1 - pow($relativePositionEndpoint1, 2)));
        $chargeDensity = $this->charge / $distanceBetweenEndpoints;
        
        if($distanceToProjection == 0 || abs($relativePositionEndpoint1) == $distanceToEndpoint1 || abs($relativePositionEndpoint2) == $distanceToEndpoint2)
        {
            if($relativePositionEndpoint1 < 0 && $relativePositionEndpoint2 < 0)
            {
                return 8.9875517923E9 * $chargeDensity * log($relativePositionEndpoint1 / $relativePositionEndpoint2);
            }
            
            else if($relativePositionEndpoint1 <= 0 && $relativePositionEndpoint2 >= 0)
            {
                return $this->charge * INF;
            }
            
            else if($relativePositionEndpoint1 > 0 && $relativePositionEndpoint2 > 0)
            {
                return 8.9875517923E9 * $chargeDensity * log($relativePositionEndpoint2 / $relativePositionEndpoint1);
            }
        }
        
        if($relativePositionEndpoint1 < 0 && $relativePositionEndpoint2 < 0)
        {
            return 8.9875517923E9 * $chargeDensity * log(($distanceToEndpoint1 - $relativePositionEndpoint1) / ($distanceToEndpoint2 - $relativePositionEndpoint2));
        }
        
        else
        {
            return 8.9875517923E9 * $chargeDensity * log(($relativePositionEndpoint2 + $distanceToEndpoint2) / ($relativePositionEndpoint1 + $distanceToEndpoint1));
        }
    }
}

class LineSegmentFlashlight
{
    public $endpoint1;
    public $endpoint2;
    public $fieldLineCount;
    
    function __construct($endpoint1, $endpoint2, $fieldLineCount)
    {
        $this->endpoint1 = $endpoint1;
        $this->endpoint2 = $endpoint2;
        $this->fieldLineCount = $fieldLineCount;
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
            
            for($p = 0; $p < $this->fieldLineCount; $p++)
            {
                array_push($this->rootFieldLinePositions, $this->endpoint1->copy()->interpolateTo($this->endpoint2, (($this->fieldLineCount === 1) ? 0.5 : $p / ($this->fieldLineCount - 1))));
            }
        }
        
        return $this->rootFieldLinePositions;
    }
}

class CircleFlashlight
{
    public $position;
    public $radius;
    public $fieldLineCount;
    
    function __construct($position, $radius, $fieldLineCount)
    {
        $this->position = $position;
        $this->radius = $radius;
        $this->fieldLineCount = $fieldLineCount;
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
            
            for($p = 0; $p < $this->fieldLineCount; $p++)
            {
                array_push($this->rootFieldLinePositions, $this->position->copy()->addToPolar($this->radius, interpolate(0, 2 * pi(), $p / $this->fieldLineCount)));
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
    public $fieldLineCount;
    
    function __construct($position, $radius, $startingAngle, $endingAngle, $fieldLineCount)
    {
        $this->position = $position;
        $this->radius = $radius;
        $this->startingAngle = $startingAngle;
        $this->endingAngle = $endingAngle;
        $this->fieldLineCount = $fieldLineCount;
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
            
            for($p = 0; $p < $this->fieldLineCount; $p++)
            {
                array_push($this->rootFieldLinePositions, $this->position->copy()->addToPolar($this->radius, interpolate($this->startingAngle, $this->endingAngle, ($this->fieldLineCount === 1) ? 0.5 : $p / ($this->fieldLineCount - 1))));
            }
        }
        
        return $this->rootFieldLinePositions;
    }
}

$data = json_decode(file_get_contents('php://input'));

if(json_last_error() === JSON_ERROR_NONE)
{
    if(property_exists($data, 'input') && property_exists($data, 'output'))
    {
        $dataInput = $data->input;
        $dataOutput = $data->output;

        if(property_exists($dataInput, 'charges') && property_exists($dataInput, 'flashlights') && property_exists($dataOutput, 'fieldLineIterationLimit') && property_exists($dataOutput, 'fieldLineIterationStep') && property_exists($dataOutput, 'viewportMinimumX') && property_exists($dataOutput, 'viewportMinimumY') && property_exists($dataOutput, 'viewportMaximumX') && property_exists($dataOutput, 'viewportMaximumY'));
        {
            $dataCharges = $dataInput->charges;
            $dataFlashlights = $dataInput->flashlights;
            $dataFieldLineIterationLimit = $dataOutput->fieldLineIterationLimit;
            $dataFieldLineIterationStep = $dataOutput->fieldLineIterationStep;
            $dataViewportMinimumX = $dataOutput->viewportMinimumX;
            $dataViewportMinimumY = $dataOutput->viewportMinimumY;
            $dataViewportMaximumX = $dataOutput->viewportMaximumX;
            $dataViewportMaximumY = $dataOutput->viewportMaximumY;

            if(is_array($dataCharges) && is_array($dataFlashlights) && is_int($dataFieldLineIterationLimit) && (is_int($dataFieldLineIterationStep) || is_float($dataFieldLineIterationStep)) && (is_int($dataViewportMinimumX) || is_float($dataViewportMinimumX)) && (is_int($dataViewportMinimumY) || is_float($dataViewportMinimumY)) && (is_int($dataViewportMaximumX) || is_float($dataViewportMaximumX)) && (is_int($dataViewportMaximumY) || is_float($dataViewportMaximumY)))
            {
                if(count($dataCharges) <= 100 && count($dataFlashlights) <= 100 && $dataFieldLineIterationLimit >= 0 && $dataFieldLineIterationStep >= 1E-100 && $dataFieldLineIterationStep <= 1E100 && $dataViewportMinimumX >= -1E100 && $dataViewportMinimumY >= -1E100 && $dataViewportMaximumX <= 1E100 && $dataViewportMaximumY <= 1E100 && $dataViewportMaximumX - $dataViewportMinimumX >= 1E-100 && $dataViewportMaximumY - $dataViewportMinimumY >= 1E-100)
                {
                    $chargesValid = true;

                    foreach($dataCharges as $dataCharge)
                    {
                        if(property_exists($dataCharge, 'type') && property_exists($dataCharge, 'charge'))
                        {
                            if((is_int($dataCharge->charge) || is_float($dataCharge->charge)))
                            {
                                if(abs($dataCharge->charge) <= 1E100)
                                {
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
                                                        continue;
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
                                                    if(abs($dataCharge->endpoint1->x) <= 1E100 && abs($dataCharge->endpoint1->y) <= 1E100 && abs($dataCharge->endpoint2->x) <= 1E100 && abs($dataCharge->endpoint2->y) <= 1E100 && (abs($dataCharge->endpoint1->x - $dataCharge->endpoint2->x) >= 1E-100 || abs($dataCharge->endpoint1->y - $dataCharge->endpoint2->y) >= 1E-100))
                                                    {
                                                        continue;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    else if($dataCharge->type === 'Regular Polygon')
                                    {
                                        if(property_exists($dataCharge, 'position') && property_exists($dataCharge, 'rotation') && property_exists($dataCharge, 'sides') && property_exists($dataCharge, 'radius'))
                                        {
                                            if(property_exists($dataCharge->position, 'x') && property_exists($dataCharge->position, 'y'))
                                            {
                                                if((is_int($dataCharge->position->x) || is_float($dataCharge->position->x)) && (is_int($dataCharge->position->y) || is_float($dataCharge->position->y)) && (is_int($dataCharge->rotation) || is_float($dataCharge->rotation)) && is_int($dataCharge->sides) && (is_int($dataCharge->radius) || is_float($dataCharge->radius)))
                                                {
                                                    if(abs($dataCharge->position->x) <= 1E100 && abs($dataCharge->position->y) <= 1E100 && abs($dataCharge->rotation) <= 1E100 && $dataCharge->sides >= 3 && $dataCharge->radius > 1E-100 && $dataCharge->radius <= 1E100)
                                                    {
                                                        continue;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $chargesValid = false;
                        break;
                    }

                    if($chargesValid)
                    {
                        $flashlightsValid = true;
                        $totalfieldLineCount = 0;

                        foreach($dataFlashlights as $dataFlashlight)
                        {
                            if(property_exists($dataFlashlight, 'type') && property_exists($dataFlashlight, 'fieldLineCount'))
                            {
                                if(is_int($dataFlashlight->fieldLineCount))
                                {
                                    if($dataFlashlight->fieldLineCount >= 0 && $dataFlashlight->fieldLineCount <= 1000)
                                    {
                                        $totalfieldLineCount += $dataFlashlight->fieldLineCount;

                                        if($totalfieldLineCount <= 1000)
                                        {
                                            if($dataFlashlight->type === 'Line Segment')
                                            {
                                                if(property_exists($dataFlashlight, 'endpoint1') && property_exists($dataFlashlight, 'endpoint2'))
                                                {
                                                    if(property_exists($dataFlashlight->endpoint1, 'x') && property_exists($dataFlashlight->endpoint1, 'y') && property_exists($dataFlashlight->endpoint2, 'x') && property_exists($dataFlashlight->endpoint2, 'y'))
                                                    {
                                                        if((is_int($dataFlashlight->endpoint1->x) || is_float($dataFlashlight->endpoint1->x)) && (is_int($dataFlashlight->endpoint1->y) || is_float($dataFlashlight->endpoint1->y)) && (is_int($dataFlashlight->endpoint2->x) || is_float($dataFlashlight->endpoint2->x)) && (is_int($dataFlashlight->endpoint2->y) || is_float($dataFlashlight->endpoint2->y)))
                                                        {
                                                            if(abs($dataFlashlight->endpoint1->x) <= 1E100 && abs($dataFlashlight->endpoint1->y) <= 1E100 && abs($dataFlashlight->endpoint2->x) <= 1E100 && abs($dataFlashlight->endpoint2->y) <= 1E100 && (abs($dataFlashlight->endpoint1->x - $dataFlashlight->endpoint2->x) >= 1E-100 || abs($dataFlashlight->endpoint1->y - $dataFlashlight->endpoint2->y) >= 1E-100))
                                                            {
                                                                continue;
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
                                                        if((is_int($dataFlashlight->position->x) || is_float($dataFlashlight->position->x)) && (is_int($dataFlashlight->position->y) || is_float($dataFlashlight->position->y)))
                                                        {
                                                            if(abs($dataFlashlight->position->x) <= 1E100 && abs($dataFlashlight->position->y) <= 1E100 && $dataFlashlight->radius > 1E-100 && $dataFlashlight->radius <= 1E100)
                                                            {
                                                                continue;
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
                                                        if((is_int($dataFlashlight->position->x) || is_float($dataFlashlight->position->x)) && (is_int($dataFlashlight->position->y) || is_float($dataFlashlight->position->y)))
                                                        {
                                                            if(abs($dataFlashlight->position->x) <= 1E100 && abs($dataFlashlight->position->y) <= 1E100 && $dataFlashlight->radius > 1E-100 && $dataFlashlight->radius <= 1E100 && $dataFlashlight->startingAngle >= 0 && $dataFlashlight->endingAngle <= 360 && $dataFlashlight->endingAngle - $dataFlashlight->startingAngle >= 1E-100)
                                                            {
                                                                continue;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $flashlightsValid = false;
                            break;
                        }

                        if($flashlightsValid && $totalfieldLineCount * $dataFieldLineIterationLimit * count($dataCharges) <= 1000000)
                        {
                            $charges = array();

                            foreach($dataCharges as $dataCharge)
                            {
                                if($dataCharge->type === 'Point')
                                {
                                    array_push($charges, new PointCharge($dataCharge->charge, new Point($dataCharge->position->x, $dataCharge->position->y)));
                                }

                                else if($dataCharge->type === 'Finite Line')
                                {
                                    array_push($charges, new FiniteLineCharge($dataCharge->charge, new Point($dataCharge->endpoint1->x, $dataCharge->endpoint1->y), new Point($dataCharge->endpoint2->x, $dataCharge->endpoint2->y)));
                                }

                                else if($dataCharge->type === 'Regular Polygon')
                                {
                                    $center = new Point($dataCharge->position->x, $dataCharge->position->y);
                                    $points = array();

                                    for($p = 0; $p < $dataCharge->sides; $p++)
                                    {
                                        array_push($points, $center->copy()->addToPolar($dataCharge->radius, pi() * (2 * $p / $dataCharge->sides + $dataCharge->rotation / 180 + 0.5)));
                                    }

                                    for($s = 0; $s < $dataCharge->sides; $s++)
                                    {
                                        array_push($charges, new FiniteLineCharge($dataCharge->charge / $dataCharge->sides, $points[$s], $points[($s + 1) % $dataCharge->sides]));
                                    }
                                }
                            }

                            $flashlights = array();

                            foreach($dataFlashlights as $dataFlashlight)
                            {
                                if($dataFlashlight->type === 'Line Segment')
                                {
                                    $flashlight = new LineSegmentFlashlight(new Point($dataFlashlight->endpoint1->x, $dataFlashlight->endpoint1->y), new Point($dataFlashlight->endpoint2->x, $dataFlashlight->endpoint2->y), $dataFlashlight->fieldLineCount);
                                }

                                else if($dataFlashlight->type === 'Circle')
                                {
                                    $flashlight = new CircleFlashlight(new Point($dataFlashlight->position->x, $dataFlashlight->position->y), $dataFlashlight->radius, $dataFlashlight->fieldLineCount);
                                }

                                else if($dataFlashlight->type === 'Circular Arc')
                                {
                                    $flashlight = new CircularArcFlashlight(new Point($dataFlashlight->position->x, $dataFlashlight->position->y), $dataFlashlight->radius, pi() / 180 * $dataFlashlight->startingAngle, pi() / 180 * $dataFlashlight->endingAngle, $dataFlashlight->fieldLineCount);
                                }

                                array_push($flashlights, $flashlight);
                            }

                            $collection = new Collection($charges, $flashlights);
                            $fieldLineIterationLimit = $dataFieldLineIterationLimit;
                            $fieldLineIterationStep = $dataFieldLineIterationStep;
                            $width = 1000;
                            $height = 1000;
                            $viewportMinimumX = $dataViewportMinimumX;
                            $viewportMinimumY = $dataViewportMinimumY;
                            $viewportMaximumX = $dataViewportMaximumX;
                            $viewportMaximumY = $dataViewportMaximumY;
                            $multiplierX = $width / ($viewportMaximumX - $viewportMinimumX);
                            $multiplierY = $height / ($viewportMaximumY - $viewportMinimumY);

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

                            foreach($flashlights as $flashlight)
                            {
                                for($l = 0; $l < $flashlight->fieldLineCount; $l++)
                                {
                                    for($d = 1; $d >= -1; $d -= 2)
                                    {
                                        $fieldLinePosition = $flashlight->getRootFieldLinePosition($l)->copy();
                                        $screenCoordinates = virtualPositionToScreenCoordinates($fieldLinePosition);
                                        $electricFieldDraw->pathStart();
                                        $electricFieldDraw->pathMoveToAbsolute($screenCoordinates[0], $screenCoordinates[1]);

                                        for($i = 0; $i < $fieldLineIterationLimit; $i++)
                                        {
                                            $fieldAtPoint = $collection->getElectricFieldVectorAtPoint($fieldLinePosition);

                                            if($fieldAtPoint === INF)
                                            {
                                                break;
                                            }

                                            else if($fieldAtPoint->x == 0 && $fieldAtPoint->y == 0)
                                            {
                                                break;
                                            }

                                            else if($i > 0 && $previousFieldAtPoint->getDotProductWith($fieldAtPoint) < 0)
                                            {
                                                break;
                                            }

                                            $previousFieldAtPoint = $fieldAtPoint->copy();
                                            $normalizedFieldAtPoint = $fieldAtPoint->normalize();
                                            $fieldLinePosition->addTo($normalizedFieldAtPoint->multiplyBy($fieldLineIterationStep)->multiplyBy($d));
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

                            foreach($charges as $charge)
                            {
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

                            foreach($flashlights as $flashlight)
                            {
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
                                    $elementsDraw->arc(min(max($screenCoordinates1[0], -100 * $width), 101 * $width), min(max($screenCoordinates1[1], -100 * $height), 101 * $height), min(max($screenCoordinates2[0], -100 * $width), 101 * $width), min(max($screenCoordinates2[1], -100 * $height), 101 * $height), 0, 360);
                                }

                                if(get_class($flashlight) === 'CircularArcFlashlight')
                                {
                                    $screenCoordinates1 = virtualPositionToScreenCoordinates($flashlight->position->copy()->subtractToCoordinates($flashlight->radius, $flashlight->radius));
                                    $screenCoordinates2 = virtualPositionToScreenCoordinates($flashlight->position->copy()->addToCoordinates($flashlight->radius, $flashlight->radius));
                                    $elementsDraw->arc(min(max($screenCoordinates1[0], -100 * $width), 101 * $width), min(max($screenCoordinates1[1], -100 * $height), 101 * $height), min(max($screenCoordinates2[0], -100 * $width), 101 * $width), min(max($screenCoordinates2[1], -100 * $height), 101 * $height), 180 / pi() * $flashlight->startingAngle, 180 / pi() * $flashlight->endingAngle);
                                }

                                $elementsDraw->setStrokeOpacity(0);
                                $elementsDraw->setStrokeColor('black');
                                $elementsDraw->setFillOpacity(1);
                                $elementsDraw->setFillColor('black');

                                for($p = 0; $p < $flashlight->fieldLineCount; $p++)
                                {
                                    $screenPosition = virtualPositionToScreenCoordinates($flashlight->getRootFieldLinePosition($p));
                                    $elementsDraw->circle($screenPosition[0], $screenPosition[1], $screenPosition[0] + 4, $screenPosition[1]);
                                }
                            }

                            $image->drawImage($elementsDraw);
                            $elementsDraw->clear();
                            echo base64_encode($image->getImageBlob());
                            $image->clear();
                        }
                    }
                }
            }
        }
    }
}

function interpolate($startingValue, $endingValue, $interpolation)
{
    return ($startingValue + ($endingValue - $startingValue) * $interpolation);
}

function virtualPositionToScreenCoordinates($position)
{
    global $viewportMinimumX, $multiplierX, $viewportMinimumY, $multiplierY;
    return array(($position->x - $viewportMinimumX) * $multiplierX, ($position->y - $viewportMinimumY) * $multiplierY);
}

function screenCoordinatesToVirtualPosition($x, $y)
{
    global $viewportMinimumX, $multiplierX, $viewportMinimumY, $multiplierY;
    return new Point($x / $multiplierX + $viewportMinimumX, $y / $multiplierY + $viewportMinimumY);
}

?>
