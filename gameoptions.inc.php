<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Bouncers implementation : © Eric Kelly <boardgamearena@useric.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * Bouncers game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in emptygame.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = [

    100 => [
        'name' => totranslate('Show upcoming points cards'),
        'values' => array(
            0 => ['name' => totranslate('No'), 'description' => totranslate('Only the current points card is visible.')],
            1 => ['name' => totranslate('Yes'), 'description' => totranslate('All upcoming points cards are visible.')],
        ),
        'default' => 0,
    ],
    101 => [
        'name' => totranslate('Special Bouncer abilities'),
        'values' => [
            0 => ['name' => totranslate('No'), 'description' => totranslate('Each bouncer cancels a top-scoring card.')],
            2 => ['name' => totranslate('Yes'), 'description' => totranslate('The King cancels the highest card, the Jack cancels the lowest card, and the Queen cancels the previous card.')],
        ],
        'default' => 0,
    ]
];
