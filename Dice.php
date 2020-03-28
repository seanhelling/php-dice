<?php
        /**
         * @author Sean Helling <sean@sfx.dev>
         * @link https://seanhelling.com
         * 
         * Some notes on nomenclature and usage:
         * "Rolls" are superset groups of rolls.
         * "Dice" are subset groups of rolls.
         * "Kind" is the number of sides on the die being rolled.
         * "Modifiers" are values added to "rolls."
         * 
         * This means that if you have 1 roll with 2 d20 dice and a +2 modifier, the modifier
         * will only be applied once. The upper bound will be 42, or (1*((2*20)+2)).
         * 
         * Similarly, if you have 2 rolls with 1 d20 die and a +2 modifier, you will have the
         * modifier applied once per each roll, for a total of two times. The upper bound will
         * then be 44, or (2*((1*20)+2)).
         * 
         * Expected input format is as follows:
         * "${rolls}x${dice}d${kind}${modifier}"
         * 
         * We are expecting a string argument to either the constructor or the method
         * executing the roll.
         * 
         * Rolls, dice, and kind will be treated as ints.
         * Modifier will be treated as a string on input and later cast to an int
         * following interpretation of the sign.
         * 
         * MIT License
         * 
         * Copyright (c) 2020 Sean Helling
         * 
         * Permission is hereby granted, free of charge, to any person obtaining a copy
         * of this software and associated documentation files (the "Software"), to deal
         * in the Software without restriction, including without limitation the rights
         * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
         * copies of the Software, and to permit persons to whom the Software is
         * furnished to do so, subject to the following conditions:
         * 
         * The above copyright notice and this permission notice shall be included in all
         * copies or substantial portions of the Software.
         * 
         * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
         * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
         * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
         * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
         * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
         * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
         * SOFTWARE.
         * 
         */

    class Dice {

        const DEFAULT_NUMBER_OF_ROLLS       = 1;
        const DEFAULT_NUMBER_OF_DICE        = 1;
        const DEFAULT_NUMBER_OF_SIDES       = 20;
        const DEFAULT_DICE_ROLL_MODIFIER    = '+0';

        protected $rollText     = self::DEFAULT_NUMBER_OF_ROLLS.'x'.
                                  self::DEFAULT_NUMBER_OF_DICE.
                                  'd'.self::DEFAULT_NUMBER_OF_SIDES.
                                  self::DEFAULT_DICE_ROLL_MODIFIER;
        protected $numRolls     = self::DEFAULT_NUMBER_OF_ROLLS;
        protected $numDice      = self::DEFAULT_NUMBER_OF_DICE;
        protected $kindDice     = self::DEFAULT_NUMBER_OF_SIDES;
        protected $modifier     = self::DEFAULT_DICE_ROLL_MODIFIER;
        protected $results      = [];

        public function __construct( $input = NULL ) {
            if(!is_null($input)) {
                $this->interpretRoll($input);
            }
        }

        protected function interpretRoll( $input ) {
            $diceRollArray = [];
            preg_match_all('/(?:(\d+)\s*X\s*)?(\d*)D(\d*)((?:[+\/*-]\d+)|(?:[+-][LH]))?/i', $input, $diceRollArray, PREG_PATTERN_ORDER);
            $this->rollText =      !empty($diceRollArray[0][0]) ? $diceRollArray[0][0] : NULL;
            $this->numRolls =      !empty($diceRollArray[1][0]) ?
                                        $diceRollArray[1][0] : self::DEFAULT_NUMBER_OF_ROLLS;
            $this->numDice  =      !empty($diceRollArray[2][0]) ?
                                        $diceRollArray[2][0] : self::DEFAULT_NUMBER_OF_DICE;
            $this->kindDice =      !empty($diceRollArray[3][0]) ?
                                        intval($diceRollArray[3][0]) : self::DEFAULT_NUMBER_OF_SIDES;
            $this->modifier =      !empty($diceRollArray[4][0]) ?
                                        $diceRollArray[4][0] : self::DEFAULT_DICE_ROLL_MODIFIER;
        }

        protected function parseModifier( $modifier = DEFAULT_DICE_ROLL_MODIFIER ) {
            $mod = substr($modifier, 0, 1);
            $num = substr($modifier, 1);
            switch ($mod) {
                case '-':
                    return -1 * abs($num);
                    break;
                case '+':
                default:
                    return abs($num);
                    break;
            }
        }

        public function rollDie($sides = self::DEFAULT_NUMBER_OF_SIDES) {
            return rand(1,$sides);
        }

        public function roll( $input = NULL, $details = FALSE) {
            if(!is_null($input)) {
                /* By design, if this argument is given both in the constructor and here,
                 this will overwrite the values given in the constructor. */
                $this->interpretRoll($input);
            }
            $this->results['final']=0;
            for ($i=1; $i <= $this->numRolls; $i++) { 
                for ($j=1; $j <= $this->numDice; $j++) { 
                    $thisRoll = $this->rollDie($this->kindDice);
                    $this->results['final'] += $thisRoll;
                    $this->results['details']['r'.$i]['d'.$j] = $thisRoll;
                }
                if($this->modifier != self::DEFAULT_DICE_ROLL_MODIFIER) {
                    $this->results['details']['r'.$i]['mod'] = $this->modifier;
                }
                $this->results['final'] += $this->parseModifier($this->modifier);
                
            }
            // Min/max for entire roll
            $this->results['totalLowerBound'] = $this->numRolls * (($this->numDice*1)+$this->parseModifier($this->modifier));
            $this->results['totalUpperBound'] = $this->numRolls * (($this->numDice*$this->kindDice)+$this->parseModifier($this->modifier));
            // Min/max for each die
            $this->results['dieLowerBound'] = 1;
            $this->results['dieUpperBound'] = intval($this->kindDice);
            return $details ? $this->details() : $this->result();
        }

        public function result() {
            return $this->results['final'] ?? NULL;
        }

        public function details() {
            return $this->results['details'] ?
                    array(
                        'result'        => $this->results['final'],
                        'params'        => $this->rollText,
                        'details'       => $this->results['details'],
                        'totalBounds'   =>
                            [
                                'totalLowerBound' => $this->results['totalLowerBound'] ?? NULL,
                                'totalUpperBound' => $this->results['totalUpperBound'] ?? NULL,
                            ],
                        'dieBounds'   =>
                            [
                                'dieLowerBound' => $this->results['dieLowerBound'] ?? NULL,
                                'dieUpperBound' => $this->results['dieUpperBound'] ?? NULL,
                            ],
                    ) : NULL;
        }

        public function totalBounds() {
            return array(
                'totalLowerBound' => $this->results['totalLowerBound'] ?? NULL,
                'totalUpperBound' => $this->results['totalUpperBound'] ?? NULL,
            );
        }

        public function dieBounds() {
            return array(
                'dieLowerBound' => $this->results['dieLowerBound'] ?? NULL,
                'dieUpperBound' => $this->results['dieUpperBound'] ?? NULL,
            );
        }

    }

?>