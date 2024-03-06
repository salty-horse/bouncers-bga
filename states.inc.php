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
 * states.inc.php
 *
 * bouncers game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


if (!defined('STATE_END_GAME')) {

define('STATE_NEW_HAND', 2);
define('STATE_PLAYER_TURN', 3);
define('STATE_NEXT_PLAYER', 4);
define('STATE_END_HAND', 5);
define('STATE_END_GAME', 99);
}

$machinestates = [

    // The initial state. Please do not modify.
    1 => [
        'name' => 'gameSetup',
        'description' => clienttranslate('Game setup'),
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => ['' => STATE_NEW_HAND]
    ],
    
    STATE_NEW_HAND => [
        'name' => 'newHand',
        'description' => '',
        'type' => 'game',
        'action' => 'stNewHand',
        'updateGameProgression' => true,
        'transitions' => ['' => STATE_PLAYER_TURN]
    ],
    
    STATE_PLAYER_TURN => [
        'name' => 'playerTurn',
        'description' => clienttranslate('${actplayer} must play a card'),
        'descriptionmyturn' => clienttranslate('${you} must play a card'),
        'args' => 'argPlayableCards',
        'type' => 'activeplayer',
        'possibleactions' => ['playCard'],
        'transitions' => ['playCard' => STATE_NEXT_PLAYER]
    ],

    STATE_NEXT_PLAYER => [
        'name' => 'nextPlayer',
        'description' => '',
        'type' => 'game',
        'action' => 'stNextPlayer',
        'transitions' => [
            'nextPlayer' => STATE_PLAYER_TURN,
            'nextTrick' => STATE_PLAYER_TURN,
            'endHand' => STATE_END_HAND
        ]
    ],
    
    // End of the hand (scoring, etc...)
    STATE_END_HAND => [
        'name' => 'endHand',
        'description' => '',
        'type' => 'game',
        'action' => 'stEndHand',
        'updateGameProgression' => true,
        'transitions' => [
            'gameEnd' => STATE_END_GAME,
            'newHand' => STATE_NEW_HAND
        ]
    ],
    
    // Final state.
    // Please do not modify.
    STATE_END_GAME => [
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'action' => 'stGameEnd',
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    ]
];
