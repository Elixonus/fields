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
    public $graphers;

    function __construct($charges, $graphers)
    {
        $this->charges = $charges;
        $this->graphers = $graphers;
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
        $electricFieldNet = new Point(0, 0);

        foreach($this->charges as $charge)
        {
            $electricField = $charge->getElectricFieldVectorAtPoint($point);

            if($electricField === INF)
            {
                return $electricField;
            }

            else
            {
                $electricFieldNet->addTo($electricField);
            }
        }

        return $electricFieldNet;
    }

    function getElectricPotentialAtPoint($point)
    {
        $electricPotentialNet = 0;

        foreach($this->charges as $charge)
        {
            $electricPotential = $charge->getElectricPotentialAtPoint($point);

            if(abs($electricPotential) === INF)
            {
                return $electricPotential;
            }

            else
            {
                $electricPotentialNet += $electricPotential;
            }
        }

        return $electricPotentialNet;
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
    public $position1;
    public $position2;

    function __construct($charge, $position1, $position2)
    {
        $this->charge = $charge;
        $this->position1 = $position1;
        $this->position2 = $position2;
        return $this;
    }

    function getElectricFieldVectorAtPoint($point)
    {
        $lineVector = $this->position2->copy()->subtractTo($this->position1);
        $distanceBetweenEndpoints = $lineVector->getMagnitude();
        $relativePositionPoint1 = ($lineVector->x * ($this->position1->x - $point->x) + $lineVector->y * ($this->position1->y - $point->y)) / $distanceBetweenEndpoints;
        $relativePositionPoint2 = $relativePositionPoint1 + $distanceBetweenEndpoints;
        $squaredDistanceToPoint1 = $point->getSquaredDistanceTo($this->position1);
        $squaredDistanceToPoint2 = $point->getSquaredDistanceTo($this->position2);
        $distanceToPoint1 = sqrt($squaredDistanceToPoint1);
        $distanceToPoint2 = sqrt($squaredDistanceToPoint2);

        if($distanceToPoint1 == 0 || $distanceToPoint2 == 0)
        {
            return INF;
        }

        $distanceToProjection = sqrt(abs($squaredDistanceToPoint1 - pow($relativePositionPoint1, 2)));
        $chargeDensity = $this->charge / $distanceBetweenEndpoints;
        $electricFieldII = 8.9875517923E9 * $chargeDensity * (1 / $distanceToPoint2 - 1 / $distanceToPoint1);

        if($distanceToProjection == 0)
        {
            if($relativePositionPoint1 <= 0 && $relativePositionPoint2 >= 0)
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
            $electricFieldT = 8.9875517923E9 * $chargeDensity / $distanceToProjection * ($relativePositionPoint2 / $distanceToPoint2 - $relativePositionPoint1 / $distanceToPoint1);
        }

        if(($point->x - $this->position1->x) * $lineVector->y < ($point->y - $this->position1->y) * $lineVector->x)
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
        $lineVector = $this->position2->copy()->subtractTo($this->position1);
        $distanceBetweenEndpoints = $lineVector->getMagnitude();
        $relativePositionPoint1 = ($lineVector->x * ($this->position1->x - $point->x) + $lineVector->y * ($this->position1->y - $point->y)) / $distanceBetweenEndpoints;
        $relativePositionPoint2 = $relativePositionPoint1 + $distanceBetweenEndpoints;
        $squaredDistanceToPoint1 = $point->getSquaredDistanceTo($this->position1);
        $squaredDistanceToPoint2 = $point->getSquaredDistanceTo($this->position2);
        $distanceToPoint1 = sqrt($squaredDistanceToPoint1);
        $distanceToPoint2 = sqrt($squaredDistanceToPoint2);
        $distanceToProjection = sqrt(abs($squaredDistanceToPoint1 - pow($relativePositionPoint1, 2)));
        $chargeDensity = $this->charge / $distanceBetweenEndpoints;

        if($distanceToProjection == 0 || abs($relativePositionPoint1) == $distanceToPoint1 || abs($relativePositionPoint2) == $distanceToPoint2)
        {
            if($relativePositionPoint1 < 0 && $relativePositionPoint2 < 0)
            {
                return 8.9875517923E9 * $chargeDensity * log($relativePositionPoint1 / $relativePositionPoint2);
            }

            else if($relativePositionPoint1 <= 0 && $relativePositionPoint2 >= 0)
            {
                return $this->charge * INF;
            }

            else if($relativePositionPoint1 > 0 && $relativePositionPoint2 > 0)
            {
                return 8.9875517923E9 * $chargeDensity * log($relativePositionPoint2 / $relativePositionPoint1);
            }
        }

        if($relativePositionPoint1 < 0 && $relativePositionPoint2 < 0)
        {
            return 8.9875517923E9 * $chargeDensity * log(($distanceToPoint1 - $relativePositionPoint1) / ($distanceToPoint2 - $relativePositionPoint2));
        }

        else
        {
            return 8.9875517923E9 * $chargeDensity * log(($relativePositionPoint2 + $distanceToPoint2) / ($relativePositionPoint1 + $distanceToPoint1));
        }
    }
}

class LineSegmentGrapher
{
    public $position1;
    public $position2;
    public $fieldLineCount;

    function __construct($position1, $position2, $fieldLineCount)
    {
        $this->position1 = $position1;
        $this->position2 = $position2;
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
                array_push($this->rootFieldLinePositions, $this->position1->copy()->interpolateTo($this->position2, (($this->fieldLineCount === 1) ? 0.5 : $p / ($this->fieldLineCount - 1))));
            }
        }

        return $this->rootFieldLinePositions;
    }
}

class CircleGrapher
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

$data = json_decode(file_get_contents('php://input'));

if(json_last_error() === JSON_ERROR_NONE)
{
    if(property_exists($data, 'input') && property_exists($data, 'output'))
    {
        $dataInput = $data->input;
        $dataOutput = $data->output;

        if(property_exists($dataInput, 'charges') && property_exists($dataInput, 'graphers') && property_exists($dataOutput, 'fieldLineIterationLimit') && property_exists($dataOutput, 'fieldLineIterationStep') && property_exists($dataOutput, 'viewportMinimumX') && property_exists($dataOutput, 'viewportMinimumY') && property_exists($dataOutput, 'viewportMaximumX') && property_exists($dataOutput, 'viewportMaximumY'))
        {
            $dataCharges = $dataInput->charges;
            $dataGraphers = $dataInput->graphers;
            $dataFieldLineIterationLimit = $dataOutput->fieldLineIterationLimit;
            $dataFieldLineIterationStep = $dataOutput->fieldLineIterationStep;
            $dataViewportMinimumX = $dataOutput->viewportMinimumX;
            $dataViewportMinimumY = $dataOutput->viewportMinimumY;
            $dataViewportMaximumX = $dataOutput->viewportMaximumX;
            $dataViewportMaximumY = $dataOutput->viewportMaximumY;

            if(is_array($dataCharges) && is_array($dataGraphers) && is_int($dataFieldLineIterationLimit) && (is_int($dataFieldLineIterationStep) || is_float($dataFieldLineIterationStep)) && (is_int($dataViewportMinimumX) || is_float($dataViewportMinimumX)) && (is_int($dataViewportMinimumY) || is_float($dataViewportMinimumY)) && (is_int($dataViewportMaximumX) || is_float($dataViewportMaximumX)) && (is_int($dataViewportMaximumY) || is_float($dataViewportMaximumY)))
            {
                if(count($dataCharges) <= 100 && count($dataGraphers) <= 100 && $dataFieldLineIterationLimit >= 0 && $dataFieldLineIterationStep >= 1E-100 && $dataFieldLineIterationStep <= 1E100 && $dataViewportMinimumX >= -1E100 && $dataViewportMinimumY >= -1E100 && $dataViewportMaximumX <= 1E100 && $dataViewportMaximumY <= 1E100 && $dataViewportMaximumX - $dataViewportMinimumX >= 1E-100 && $dataViewportMaximumY - $dataViewportMinimumY >= 1E-100)
                {
                    $validCharges = true;

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

                                    else if($dataCharge->type === 'Line Segment')
                                    {
                                        if(property_exists($dataCharge, 'position1') && property_exists($dataCharge, 'position2'))
                                        {
                                            if(property_exists($dataCharge->position1, 'x') && property_exists($dataCharge->position1, 'y') && property_exists($dataCharge->position2, 'x') && property_exists($dataCharge->position2, 'y'))
                                            {
                                                if((is_int($dataCharge->position1->x) || is_float($dataCharge->position1->x)) && (is_int($dataCharge->position1->y) || is_float($dataCharge->position1->y)) && (is_int($dataCharge->position2->x) || is_float($dataCharge->position2->x)) && (is_int($dataCharge->position2->y) || is_float($dataCharge->position2->y)) && ($dataCharge->position1->x != $dataCharge->position2->x || $dataCharge->position1->y != $dataCharge->position2->y))
                                                {
                                                    if(abs($dataCharge->position1->x) <= 1E100 && abs($dataCharge->position1->y) <= 1E100 && abs($dataCharge->position2->x) <= 1E100 && abs($dataCharge->position2->y) <= 1E100 && (abs($dataCharge->position1->x - $dataCharge->position2->x) >= 1E-100 || abs($dataCharge->position1->y - $dataCharge->position2->y) >= 1E-100))
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

                        $validCharges = false;
                        break;
                    }

                    if($validCharges)
                    {
                        $validGraphers = true;
                        $fieldLineCount = 0;

                        foreach($dataGraphers as $dataGrapher)
                        {
                            if(property_exists($dataGrapher, 'type') && property_exists($dataGrapher, 'fieldLineCount'))
                            {
                                if(is_int($dataGrapher->fieldLineCount))
                                {
                                    if($dataGrapher->fieldLineCount >= 0 && $dataGrapher->fieldLineCount <= 1000)
                                    {
                                        $fieldLineCount += $dataGrapher->fieldLineCount;

                                        if($dataGrapher->type === 'Line Segment')
                                        {
                                            if(property_exists($dataGrapher, 'position1') && property_exists($dataGrapher, 'position2'))
                                            {
                                                if(property_exists($dataGrapher->position1, 'x') && property_exists($dataGrapher->position1, 'y') && property_exists($dataGrapher->position2, 'x') && property_exists($dataGrapher->position2, 'y'))
                                                {
                                                    if((is_int($dataGrapher->position1->x) || is_float($dataGrapher->position1->x)) && (is_int($dataGrapher->position1->y) || is_float($dataGrapher->position1->y)) && (is_int($dataGrapher->position2->x) || is_float($dataGrapher->position2->x)) && (is_int($dataGrapher->position2->y) || is_float($dataGrapher->position2->y)))
                                                    {
                                                        if(abs($dataGrapher->position1->x) <= 1E100 && abs($dataGrapher->position1->y) <= 1E100 && abs($dataGrapher->position2->x) <= 1E100 && abs($dataGrapher->position2->y) <= 1E100 && (abs($dataGrapher->position1->x - $dataGrapher->position2->x) >= 1E-100 || abs($dataGrapher->position1->y - $dataGrapher->position2->y) >= 1E-100))
                                                        {
                                                            continue;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        else if($dataGrapher->type === 'Circle')
                                        {
                                            if(property_exists($dataGrapher, 'position') && property_exists($dataGrapher, 'radius'))
                                            {
                                                if(property_exists($dataGrapher->position, 'x') && property_exists($dataGrapher->position, 'y') && (is_int($dataGrapher->radius) || is_float($dataGrapher->radius)))
                                                {
                                                    if((is_int($dataGrapher->position->x) || is_float($dataGrapher->position->x)) && (is_int($dataGrapher->position->y) || is_float($dataGrapher->position->y)))
                                                    {
                                                        if(abs($dataGrapher->position->x) <= 1E100 && abs($dataGrapher->position->y) <= 1E100 && $dataGrapher->radius > 1E-100 && $dataGrapher->radius <= 1E100)
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

                            $validGraphers = false;
                            break;
                        }

                        if($validGraphers && $fieldLineCount * $dataFieldLineIterationLimit * count($dataCharges) <= 1000000)
                        {
                            $charges = array();

                            foreach($dataCharges as $dataCharge)
                            {
                                if($dataCharge->type === 'Point')
                                {
                                    array_push($charges, new PointCharge($dataCharge->charge, new Point($dataCharge->position->x, $dataCharge->position->y)));
                                }

                                else if($dataCharge->type === 'Line Segment')
                                {
                                    array_push($charges, new LineSegmentCharge($dataCharge->charge, new Point($dataCharge->position1->x, $dataCharge->position1->y), new Point($dataCharge->position2->x, $dataCharge->position2->y)));
                                }
                            }

                            $graphers = array();

                            foreach($dataGraphers as $dataGrapher)
                            {
                                if($dataGrapher->type === 'Line Segment')
                                {
                                    $grapher = new LineSegmentGrapher(new Point($dataGrapher->position1->x, $dataGrapher->position1->y), new Point($dataGrapher->position2->x, $dataGrapher->position2->y), $dataGrapher->fieldLineCount);
                                }

                                else if($dataGrapher->type === 'Circle')
                                {
                                    $grapher = new CircleGrapher(new Point($dataGrapher->position->x, $dataGrapher->position->y), $dataGrapher->radius, $dataGrapher->fieldLineCount);
                                }

                                array_push($graphers, $grapher);
                            }

                            $collection = new Collection($charges, $graphers);
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
                            $electricFieldDraw->setFillColor('none');

                            foreach($graphers as $grapher)
                            {
                                for($l = 0; $l < $grapher->fieldLineCount; $l++)
                                {
                                    for($d = 1; $d >= -1; $d -= 2)
                                    {
                                        $fieldLinePosition = $grapher->getRootFieldLinePosition($l)->copy();
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
                                if($charge instanceof PointCharge)
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

                                else if($charge instanceof LineSegmentCharge)
                                {
                                    $screenCoordinates1 = virtualPositionToScreenCoordinates($charge->position1);
                                    $screenCoordinates2 = virtualPositionToScreenCoordinates($charge->position2);
                                    $elementsDraw->setFillColor('none');
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

                            foreach($graphers as $grapher)
                            {
                                $elementsDraw->setStrokeOpacity(0.3);
                                $elementsDraw->setStrokeColor('black');
                                $elementsDraw->setFillColor('none');

                                if($grapher instanceof LineSegmentGrapher)
                                {
                                    $screenCoordinates1 = virtualPositionToScreenCoordinates($grapher->position1);
                                    $screenCoordinates2 = virtualPositionToScreenCoordinates($grapher->position2);
                                    $elementsDraw->line($screenCoordinates1[0], $screenCoordinates1[1], $screenCoordinates2[0], $screenCoordinates2[1]);
                                }

                                if($grapher instanceof CircleGrapher)
                                {
                                    $screenCoordinates1 = virtualPositionToScreenCoordinates($grapher->position->copy()->subtractToCoordinates($grapher->radius, $grapher->radius));
                                    $screenCoordinates2 = virtualPositionToScreenCoordinates($grapher->position->copy()->addToCoordinates($grapher->radius, $grapher->radius));
                                    $elementsDraw->arc(min(max($screenCoordinates1[0], -100 * $width), 101 * $width), min(max($screenCoordinates1[1], -100 * $height), 101 * $height), min(max($screenCoordinates2[0], -100 * $width), 101 * $width), min(max($screenCoordinates2[1], -100 * $height), 101 * $height), 0, 360);
                                }

                                $elementsDraw->setStrokeColor('none');
                                $elementsDraw->setFillOpacity(1);
                                $elementsDraw->setFillColor('black');

                                for($p = 0; $p < $grapher->fieldLineCount; $p++)
                                {
                                    $screenPosition = virtualPositionToScreenCoordinates($grapher->getRootFieldLinePosition($p));
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

function interpolate($value1, $value2, $interpolation)
{
    return ($value1 + ($value2 - $value1) * $interpolation);
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
