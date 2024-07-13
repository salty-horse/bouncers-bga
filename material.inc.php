<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Bouncers implementation : © Ori Avtalion <ori@avtalion.name>
 * Based on NinetyNine implementation: © Eric Kelly <boardgamearena@useric.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * Bouncers game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


$this->suits = [
    0 => ['name' => clienttranslate('club'),
          'pluralname' => clienttranslate('Clubs'),
          'symbol' => clienttranslate('♣')],
    1 => ['name' => clienttranslate('diamond'),
          'pluralname' => clienttranslate('Diamonds'),
          'symbol' => clienttranslate('♦')],
    2 => ['name' => clienttranslate('spade'),
          'pluralname' => clienttranslate('Spades'),
          'symbol' => clienttranslate('♠')],
];

$this->rank_labels = [
    2 => '2',
    3 => '3',
    4 => '4',
    5 => '5',
    6 => '6',
    7 => '7',
    8 => '8',
    9 => '9',
    10 => '10',
    11 => 'J',
    12 => 'Q',
    13 => 'K',
    14 => 'A'
];

$this->point_labels = [
    2 => '2',
    3 => '3',
    4 => '4',
    5 => '5',
    6 => '6',
    7 => '7',
    8 => '8',
    9 => '9',
    10 => '10',
    11 => 'J',
    12 => 'Q',
    13 => 'K',
    14 => '11'
];
