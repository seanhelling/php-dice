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
         */

    class Dice {

        const DEFAULT_NUMBER_OF_ROLLS       = 1;
        const DEFAULT_NUMBER_OF_DICE        = 1;
        const DEFAULT_NUMBER_OF_SIDES       = 20;
        const DEFAULT_DICE_ROLL_MODIFIER    = '+0';

        protected $rollText;
        protected $numRolls;
        protected $numDice;
        protected $kindDice;
        protected $modifier;
        protected $results = [];

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
                                        $diceRollArray[3][0] : self::DEFAULT_NUMBER_OF_SIDES; // true val was cast to an int before
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
                $this->results['details']['r'.$i]['mod'] = $this->modifier;
                $this->results['final'] += $this->parseModifier($this->modifier);
                
            }
            // Min/max for entire roll
            $this->results['totalLowerBound'] = $this->numRolls * (($this->numDice*1)+$this->parseModifier($this->modifier));
            $this->results['totalUpperBound'] = $this->numRolls * (($this->numDice*$this->kindDice)+$this->parseModifier($this->modifier));
            // Min/max for each die
            $this->results['dieLowerBound'] = 1;
            $this->results['dieUpperBound'] = $this->kindDice;
            return $details ? $this->details() : $this->result();
        }

        public function result() {
            return $this->results['final'] ?? NULL;
        }

        public function details() {
            return $this->results['details'] ?
                    array(
                        'result'    => $this->results['final'],
                        'details'   => $this->results['details']
                    ) : NULL;
        }

        public function totalBounds() {
            return array(
                'totalUpperBound' => $this->results['totalUpperBound'] ?? NULL,
                'totalLowerBound' => $this->results['totalLowerBound'] ?? NULL
            );
        }

        public function dieBounds() {
            return array(
                'dieUpperBound' => $this->results['dieUpperBound'] ?? NULL,
                'dieLowerBound' => $this->results['dieLowerBound'] ?? NULL
            );
        }

    }

?>