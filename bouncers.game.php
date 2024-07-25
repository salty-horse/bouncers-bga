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
  * bouncers.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Bouncers extends Table {
    function __construct() {

        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::initGameStateLabels([
            'ledSuit' => 10,
            'firstPlayer' => 11,
            'currentPlayer' => 12,
            'handNum' => 13,
            'showUpcomingPoints' => 100,
            'specialBouncerAbilities' => 101,
        ]);

        $this->cards = self::getNew('module.common.deck');
        $this->cards->init('card');
    }

    protected function getGameName() {
        return 'bouncers';
    }

    /*
        setupNewGame:

        This method is called 1 time when a new game is launched.
        In this method, you must setup the game according to game rules, in order
        the game is ready to be played.

    */
    protected function setupNewGame($players, $options = []) {
        $this->initializePlayers($players);

        /************ Start the game initialization *****/
        // Init global values with their initial values

        self::setGameStateInitialValue('ledSuit', -1);
        self::setGameStateInitialValue('handNum', 0);

        // Activate first player (which is in general a good idea :))
        $this->activeNextPlayer();

        $player_id = self::getActivePlayerId();
        self::setGameStateInitialValue('firstPlayer', $player_id);

        // Create cards
        $this->createCards();

        self::initStat('player', 'collected_0_cards_in_a_round', 0);
        self::initStat('player', 'collected_0_points_in_a_round', 0);
        self::initStat('player', 'unused_bouncers', 0);

        /************ End of the game initialization *****/
    }

    /************* Initialization helper functions ***************/

    function initializePlayers($players) {
        $sql = 'DELETE FROM player WHERE 1';
        self::DbQuery($sql);

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/yellow
        // The number of colors defined here must correspond to the maximum number of players allowed for the game
        $default_color = ['ff0000', '008000', '0000ff'];

        $start_points = 0;

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialized it there.
        $sql = 'INSERT INTO player (player_id, player_score, player_color, player_canal, player_name, player_avatar) VALUES ';
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_color);
            $values[] = "('".$player_id."','$start_points','$color','".$player['player_canal']."','".addslashes($player['player_name'] )."','".addslashes($player['player_avatar'])."')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reloadPlayersBasicInfos();
    }

    function createCards() {
        $cards = [];
        for ($suit = 0; $suit <= 2; $suit++) {
            for ($value = 2; $value <= 14; $value++) {
                $cards[] = ['type' => $suit, 'type_arg' => $value, 'nbr' => 1];
            }
        }
        $this->cards->createCards($cards, 'deck');

        $cards = [];
        for ($value = 2; $value <= 14; $value++) {
            $cards[] = ['type' => 4, 'type_arg' => $value, 'nbr' => 1];
        }
        $this->cards->createCards($cards, 'points');
    }

    /************** End Initialization helper functions ****************/

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refresh the game page (F5)
    */
    protected function getAllDatas() {
        $result = ['players' => []];

        // !! We must only return informations visible by this player !!
        $player_id = self::getCurrentPlayerId();

        // Get information about players
        // Note: you can retrieve some extra field you add for "player" table in "dbmodel.sql" if you need it.
        $dbres = self::DbQuery('SELECT player_id id, player_score score FROM player WHERE 1');
        while ($player = mysql_fetch_assoc($dbres)) {
            $result['players'][intval($player['id'])] = $player;
        }

        $result['directions'] = $this->getPlayersToDirection();

        // Cards in player hand
        $result['hand'] = $this->cards->getPlayerHand($player_id);
        $result['playable_cards'] = array_keys($this->getPlayableCards($player_id));

        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation('cardsontable');

        // Point card
        $result['points_card'] = $this->cards->getCardOnTop('points')['type_arg'];
        if ($this->getGameStateValue('showUpcomingPoints') == '1') {
            $result['upcoming_points'] = $this->getUpcomingCards();
        }

        $result['special_abilities'] = $this->getGameStateValue('specialBouncerAbilities') == '1';

        $result['handNum'] = $this->getGameStateValue('handNum');

        foreach ($result['players'] as &$player) {
            $player_id = $player['id'];
            $player['score_pile'] = $this->getScorePile($player_id);
        }

        $result['point_labels'] = $this->point_labels;

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with "updateGameProgression" property (see states.inc.php)
    */
    function getGameProgression() {
        // Use max player score at the end of a hand for the progression percentage
        $max_score = $this->getUniqueValueFromDB("SELECT MIN(player_score) from player");
        return min(-1 * $max_score, 99);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

/************** Game State helper functions ****************/

    /*
        At this place, you can put any utility methods useful for your game logic
    */

    // Gets the current player whose turn it is to play a card
    function getCurrentPlayer() {
        return intval(self::getGameStateValue('currentPlayer'));
    }

    // Sets the current player
    function setCurrentPlayer($playerID) {
        self::setGameStateValue('currentPlayer', $playerID);
    }

/************** End Game State helper functions ****************/

/************** Database access helper functions ****************/

    // Set the total game score for a particular player
    function dbSetScore($playerId, $count) {
        $this->DbQuery("UPDATE player SET player_score='$count' WHERE player_id='$playerId'");
    }

/************** End Database access helper functions ****************/

/************** Other helper functions ****************/

    // Return the list of valid playable cards in the given player's hand
    function getPlayableCards($playerId) {
        $cardsInHand = $this->cards->getPlayerHand($playerId);
        $ledSuit = self::getGameStateValue('ledSuit');
        if ($ledSuit == -1) {
            // All cards in the hand are valid to play
            return $cardsInHand;
        }
        // If we have cards in our hand of the led suit, return those
        $cardsOfLedSuit = array_filter($cardsInHand, function ($card) use ($ledSuit) {
            return $card['type'] == $ledSuit;
        });
        if (count($cardsOfLedSuit) == 0) {
            return $cardsInHand;
        }
        return $cardsOfLedSuit;
    }

    // Return players => direction (N/S/E/W) from the point of view
    //  of current player (current player must be on south)
    function getPlayersToDirection() {
        $result = [];

        $players = self::loadPlayersBasicInfos();
        $nextPlayer = self::createNextPlayerTable(array_keys($players));

        $current_player = self::getCurrentPlayerId();

        $directions = ['S', 'W', 'E'];

        if (!isset($nextPlayer[$current_player])) {
            // Spectator mode: take any player for south
            $player_id = $nextPlayer[0];
            $result[$player_id] = array_shift($directions);
        } else {
            // Normal mode: current player is on south
            $player_id = $current_player;
            $result[$player_id] = array_shift($directions);
        }

        while (count($directions) > 0) {
            $player_id = $nextPlayer[$player_id];
            $result[$player_id] = array_shift($directions);
        }
        return $result;
    }

    function getScorePile($player_id) {
        // Cards with a lower location_arg were collected earlier
        $score_pile_cards = self::getObjectListFromDB("select card_type_arg from card where card_location = 'scorepile_${player_id}' order by card_location_arg", true);

        // Create mapping of value to index. Find the bouncers. Turn values into arrays with single value.
        // (E.g. [[1], [2], [3]])
        $value_to_index = [];
        $bouncers = [];
        for ($i = 0; $i < count($score_pile_cards); $i++) {
            $value = $score_pile_cards[$i];
            $score_pile_cards[$i] = [$value];
            $value_to_index[$value] = $i;

            switch ($value) {
            case 11:
                $bouncers['J'] = $i;
                break;
            case 12:
                $bouncers['Q'] = $i;
                break;
            case 13:
                $bouncers['K'] = $i;
                break;
            }
        }

        $sorted_values = array_keys($value_to_index);
        sort($sorted_values);

        if ($this->getGameStateValue('specialBouncerAbilities')) {
            // Cancel previous card
            $bouncer_index = $bouncers['Q'] ?? null;
            if ($bouncer_index !== null && $bouncer_index != 0) {
                $prev = &$score_pile_cards[$bouncer_index - 1];
                if (count($prev) == 1 && !(11 <= $prev[0] && $prev[0] <= 13)) {
                    $prev[] = 1;
                    $score_pile_cards[$bouncer_index][] = 1;
                }
            }

            // Cancel lowest card
            $bouncer_index = $bouncers['J'] ?? null;
            if ($bouncer_index !== null) {
                // Find lowest card
                for ($i = 0; $i < count($sorted_values); $i++) {
                    $value = $sorted_values[$i];
                    if (11 <= $value && $value <= 13) continue;
                    $elem = &$score_pile_cards[$value_to_index[$value]];
                    if (count($elem) == 1) {
                        $elem[] = 1;
                        $score_pile_cards[$bouncer_index][] = 1;
                    }
                    break;

                }
            }

            // Cancel highest card
            $bouncer_index = $bouncers['K'] ?? null;
            if ($bouncer_index !== null) {
                // Find highest card
                for ($i = count($sorted_values) - 1; $i >= 0; $i--) {
                    $value = $sorted_values[$i];
                    if (11 <= $value && $value <= 13) continue;
                    $elem = &$score_pile_cards[$value_to_index[$value]];
                    if (count($elem) == 1) {
                        $elem[] = 1;
                        $score_pile_cards[$bouncer_index][] = 1;
                    }
                    break;
                }
            }
        } else {
            $bouncer_count = count($bouncers);
            $cancelled_count = 0;

            // Cancel highest at-most that many non-bouncers
            for ($i = count($sorted_values) - 1; $i >= 0 && $bouncer_count > 0; $i--) {
                $value = $sorted_values[$i];
                if (11 <= $value && $value <= 13) continue;
                $cancelled_count += 1;
                $bouncer_count -= 1;
                $score_pile_cards[$value_to_index[$value]][] = 1;
            }

            $bouncer_indexes = array_values($bouncers);
            sort($bouncer_indexes);

            // Cancel the first bouncers
            for ($i = 0; $i < $cancelled_count; $i++) {
                $score_pile_cards[$bouncer_indexes[$i]][] = 1;
            }
        }

        $score = 0;
        $unused_bouncers = 0;

        foreach ($score_pile_cards as &$item)  {
            if (count($item) > 1)
                continue;
            $value = $item[0];
            switch ($value) {
                case 11:
                case 12:
                case 13:
                    $score += 25;
                    $unused_bouncers += 1;
                    break;
                case 14:
                    $score += 11;
                    break;
                default:
                    $score += $value;
                    break;
            }
        }

        return [
            'score' => $score,
            'score_pile' => $score_pile_cards,
            'unused_bouncers' => $unused_bouncers,
        ];
    }

    function getUpcomingCards() {
        return $this->getObjectListFromDB("SELECT card_type_arg FROM card WHERE card_location = 'points' ORDER BY card_location_arg DESC LIMIT 1,13", true );
    }

/************** End Other helper functions ****************/


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of this method below is called.
        (note: each method below correspond to an input method in bouncers.action.php)
    */

    // Play a card from the active player's hand
    function playCard($card_id) {
        self::checkAction('playCard');
        $player_id = self::getActivePlayerId();
        $this->playCardFromPlayer($card_id, $player_id);

        // Next player
        $this->gamestate->nextState();
    }

    // Play a card from player hand
    function playCardFromPlayer($card_id, $player_id) {
        $current_card = $this->cards->getCard($card_id);

        // Sanity check. A more thorough check is done later.
        if ($current_card['location'] != 'hand' || $current_card['location_arg'] != $player_id) {
            throw new BgaVisibleSystemException('You do not have this card');
        }

        $playable_cards = $this->getPlayableCards($player_id);

        if (!array_key_exists($card_id, $playable_cards)) {
            throw new BgaVisibleSystemException('You cannot play this card');
        }

        // Checks are done! now we can play our card
        $this->cards->moveCard($card_id, 'cardsontable', $player_id);
        if (self::getGameStateValue('ledSuit') == -1)
            self::setGameStateValue('ledSuit', $current_card['type']);

        // And notify
        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${rank_displayed} ${suit_displayed}'), [
            'i18n' => ['suit_displayed', 'rank_displayed'],
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'rank' => $current_card['type_arg'],
            'rank_displayed' => $this->rank_labels[$current_card['type_arg']],
            'suit' => $current_card['type'],
            'suit_displayed' => '<span class="bgabnc_icon bgabnc_suit'.$current_card['type'] . '"></span>',
            'currentPlayer' => $this->getCurrentPlayer()
        ]);
    }

/************** Player Action helper functions ****************/

    // Returns the card associated with the trick winner
    // The trick winner id is the location_arg of the card
    function getTrickWinner() {
        // This is the end of the trick
        $cardsOnTable = $this->cards->getCardsInLocation('cardsontable');

        if (count($cardsOnTable) != 3) {
            throw new BgaVisibleSystemException('Invalid trick card count');
        }

        $next_player_table = $this->getNextPlayerTable();
        $third_player = $this->getActivePlayerId();
        $first_player = $next_player_table[$third_player];
        $second_player = $next_player_table[$first_player];
        $value_modifiers = [
            $first_player => 0.1,
            $second_player => 0.2,
            $third_player => 0.3,
        ];

        $bestValue = 0;
        $bestValueCard = null;
        foreach ($cardsOnTable as $card) {
            $cardVal = $card['type_arg'] + $value_modifiers[$card['location_arg']];
            if ($bestValue <= $cardVal) {
                $bestValue = $cardVal;
                $bestValueCard = $card;
            }
        }

        return $bestValueCard;
    }

/************** End Player Action helper functions ****************/

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defines as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stGameSetup() {
        $this->gamestate->nextState();
    }

    function stNewHand() {

        $handCount = $this->incGameStateValue('handNum', 1);

        $this->gamestate->changeActivePlayer($this->getGameStateValue('firstPlayer'));

        self::notifyAllPlayers('newRound', clienttranslate('Starting hand ${hand_num}'), [
            'hand_num' => $handCount,
        ]);

        // Recreate points deck, deal cards to each players
        $this->cards->shuffle('deck');
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $this->cards->moveAllCardsInLocation("scorepile_${player_id}", 'points');
            $cards = $this->cards->pickCards(13, 'deck', $player_id);
            // Notify player about his cards
            self::notifyPlayer($player_id, 'newHand', '', [
                'cards' => $cards,
            ]);
        }
        $this->cards->shuffle('points');
        $args = [
            'cards' => $cards,
            'points_card' => $this->cards->getCardOnTop('points')['type_arg'],
        ];
        if ($this->getGameStateValue('showUpcomingPoints') == '1') {
            $args['upcoming_points'] = $this->getUpcomingCards();
        }
        self::notifyAllPlayers('newHandPublic', '', $args);
        $this->gamestate->nextState();
    }

    function stPlayerTurnTryAutoplay() {
        $player_id = $this->getActivePlayerId();
        $cards_in_hand = $this->cards->getPlayerHand($player_id);
        if (count($cards_in_hand) == 1) {
            $this->playCardFromPlayer(array_values($cards_in_hand)[0]['id'], $player_id);
            $this->gamestate->nextState('nextPlayer');
            return;
        } else {
            $this->gamestate->nextState('playerTurn');
            return;
        }
    }

    function stNextPlayer() {
        // Active next player OR end the trick and go to the next trick OR end the hand
        if ($this->cards->countCardInLocation('cardsontable') == 3) {
            $winningCard = $this->getTrickWinner();
            $winningPlayer = $winningCard['location_arg'];

            // The winner starts the next trick
            $this->gamestate->changeActivePlayer($winningPlayer);
            $this->setCurrentPlayer($winningPlayer);

            self::setGameStateValue('ledSuit', -1);

            // Put cards back in deck
            $this->cards->moveAllCardsInLocation('cardsontable', 'deck');

            // Give point card to player
            $current_point_card = $this->cards->getCardOnTop('points');
            $this->cards->insertCardOnExtremePosition($current_point_card['id'], "scorepile_${winningPlayer}", /* on top */ true);

            $players = self::loadPlayersBasicInfos();
            $args = [
                'player_id' => $winningPlayer,
                'player_name' => $players[$winningPlayer]['player_name'],
                'points' => $this->point_labels[$current_point_card['type_arg']],
                'score_pile' => $this->getScorePile($winningPlayer),
            ];
            if ($this->cards->countCardInLocation('hand') == 0) {
                // End of the hand
                $next_state = 'endHand';
            } else {
                // End of the trick
                $next_state = 'nextTrick';
                $args['points_card'] = $this->cards->getCardOnTop('points')['type_arg'];
            }
            self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick and the points card ${points}'), $args);
            $this->gamestate->nextState($next_state);
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player
            $player_id = self::activeNextPlayer();
            $this->setCurrentPlayer($player_id);
            self::giveExtraTime($player_id);
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function argPlayableCards() {
        $player_id = self::getActivePlayerId();
        return [
            '_private' => [
                'active' => [
                    'playable_cards' => array_keys($this->getPlayableCards($player_id)),
                ]
            ]
        ];
    }

    function stEndHand() {

        // Count and score points, then end the round / game or go to the next hand.
        $players = self::loadPlayersBasicInfos();

        $players = self::getCollectionFromDB("SELECT player_id id, player_name, player_score score FROM player");

        // Update scores
        $end_game = false;
        foreach ($players as $player_id => $player_info) {
            $score_pile = $this->getScorePile($player_id);
            $new_score = $player_info['score'] - $score_pile['score'];
            if ($new_score <= -100) {
                $end_game = true;
            }
            $this->dbSetScore($player_id, $new_score);
            self::notifyAllPlayers('points', clienttranslate('${player_name} loses ${points} points'), [
                'player_id' => $player_id,
                'player_name' => $player_info['player_name'],
                'points' => $score_pile['score'],
            ]);

            if ($score_pile['score'] == 0) {
                self::incStat(1, 'collected_0_points_in_a_round', $player_id);
            }

            if (count($score_pile['score_pile']) == 0) {
                self::incStat(1, 'collected_0_cards_in_a_round', $player_id);
            }
            self::incStat($score_pile['unused_bouncers'], 'unused_bouncers', $player_id);
        }

        if ($end_game) {
            self::notifyAllPlayers('showScores', '', [
                'end_of_game' => true,
            ]);
            $this->gamestate->nextState('gameEnd');
            return;
        }

        self::notifyAllPlayers('showScores', '', []);

        // Alternate first player
        self::setGameStateValue('firstPlayer', 
            self::getPlayerAfter(self::getGameStateValue('firstPlayer')));
        $this->gamestate->nextState('newHand');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player that quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player will end
        (ex: pass).
    */

    function zombieTurn($state, $activePlayer) {
        $statename = $state['name'];

        if ($statename == 'playerTurn') {
            // Play a card
            $playableCards = $this->getPlayableCards($activePlayer);
            $randomCard = bga_rand(0, count($playableCards) - 1);
            $keys = array_keys($playableCards);
            $cardId = $playableCards[$keys[$randomCard]]['id'];

            $this->playCardFromPlayer($cardId, $activePlayer);

            // Next player
            $this->gamestate->nextState();
        }
    }
}


