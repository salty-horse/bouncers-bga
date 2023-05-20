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

        self::setGameStateInitialValue('ledSuit', 0);
        self::setGameStateInitialValue('handNum', 0);

        // Activate first player (which is in general a good idea :))
        $this->activeNextPlayer();

        $player_id = self::getActivePlayerId();
        self::setGameStateInitialValue('firstPlayer', $player_id);

        // Create cards
        $this->createCards();

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
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reloadPlayersBasicInfos();
    }

    function createCards() {
        $cards = [];
        for ($suit = 1; $suit <= 3; $suit++) {
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
        $result = array('players' => []);

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
        $result['playableCards'] = $this->getPlayableCards($player_id);

        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation('cardsontable');

        // Point card
        $result['point_card'] = $this->cards->getCardOnTop('points');
        // TODO: Upcoming cards

        $result['gameScores'] = $this->dbGetScores();
        $result['handNum'] = $this->getGameStateValue('handNum');

        foreach ($result['players'] as &$player) {
            $player_id = $player['id'];
            $player['score_pile'] = $this->getScorePile($player_id);
        }

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
        // Game progression: get player minimum score
        return 1; // TODO
        /*
        $maxScore = 0;
        foreach ($currentRoundScores as $playerId => $score) {
            $maxScore = max($maxScore, $score);
        }

        $playerCount = 3;
        $roundPercentage = (int) (100 / $playerCount);
        $extra = 100 - ($playerCount * $roundPercentage);
        return ($roundPercentage * $this->getCurrentRound()) + min($roundPercentage, ($maxScore / $playerCount)) + $extra;
         */
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
        return intval(self::getGameStateValue("currentPlayer"));
    }

    // Sets the current player
    function setCurrentPlayer($playerID) {
        self::setGameStateValue("currentPlayer", $playerID);
    }

    // Allow a player to request showing the last score table
    function displayLastScoreTable() {
        $lastScoreInfo = $this->getLatestScoreTable();
        $playerId = self::getCurrentPlayerId();
        if ($lastScoreInfo == null) {
            $this->notifyPlayer($playerId, 'scoreDisplayRequest',
                clienttranslate("No score to display"), []);
        } else {
            $this->notifyPlayer($playerId, "tableWindow", '', array(
                "id" => 'scoreView',
                "title" => clienttranslate("Last hand"),
                "table" => $lastScoreInfo,
                "closing" => clienttranslate("Continue")
            ));
        }
    }

/************** End Game State helper functions ****************/

/************** Database access helper functions ****************/

    // Cache the score table to the DB
    function saveCurrentScoreTable($scoreTable) {
        $jsonscore = json_encode($scoreTable);
        $rowId = $this->getUniqueValueFromDB("SELECT id FROM gamestate");
        if ($rowId == null) {
            $this->DbQuery("INSERT INTO gamestate (scoretable) VALUES ('$jsonscore')");
        } else {
            $this->DbQuery("UPDATE gamestate SET scoretable='$jsonscore' WHERE id='$rowId'");
        }
    }

    // Get latest scoretable from the DB
    function getLatestScoreTable() {
        $jsonScore = $this->getUniqueValueFromDB("SELECT scoretable FROM gamestate");
        if ($jsonScore == null) {
            return null;
        }
        $scoreTable = json_decode($jsonScore);
        if (!is_array($scoreTable)) {
            return null;
        }
        return $scoreTable;
    }

    // Get the color of a particular player
    function getPlayerColor($playerId) {
        return $this->getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id='$playerId'");
    }

    function dbGetScores() {
        $scores = $this->getCollectionFromDB("SELECT player_id, player_score FROM player", true);
        $result = [];
        foreach ($scores as $playerId => $score) {
            $result[$playerId] = intval($score);
        }
        return $result;
    }

    // Get a particular player's game score
    function dbGetScore($playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$playerId'"));
    }

    // Set the total game score for a particular player
    function dbSetScore($playerId, $count) {
        $this->DbQuery("UPDATE player SET player_score='$count' WHERE player_id='$playerId'");
    }

    // increment score (can be negative too)
    function dbIncScore($playerId, $inc) {
        $count = $this->dbGetScore($playerId);
        if ($inc != 0) {
            $count += $inc;
            $this->dbSetScore($playerId, $count);
        }
        return $count;
    }

    // Set aux score
    // This is the score used for tiebreaking
    function dbSetAuxScore($playerId, $count) {
        $this->DbQuery("UPDATE player SET player_score_aux='$count' WHERE player_id='$playerId'");
    }

/************** End Database access helper functions ****************/

/************** Other helper functions ****************/

    // Return the list of valid playable cards in the given player's hand
    function getPlayableCards($playerId) {
        $cardsInHand = $this->cards->getPlayerHand($playerId);
        $ledSuit = self::getGameStateValue('ledSuit');
        if ($ledSuit == 0) {
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
        $score_pile_cards = self::getObjectListFromDB("select card_id from card where card_location = 'scorepile_${player_id}' order by card_location_arg");
        // This is an array of arrays with card values. E.g. [[1], [2], [3]].



        // Create mapping of value to index. Find the bouncers.
        $value_to_index = [];
        $bouncers = [];
        for ($i = 0; $i < count($score_pile_cards); $i++) {
            $value = $score_pile_cards[$i][0];
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
        foreach ($score_pile_cards as &$item)  {
            if (count($item) > 1)
                continue;
            $value = $item[0];
            switch ($value) {
                case 11:
                case 12:
                case 13:
                    $score += 25;
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
        ];
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
        $player_id = self::getActivePlayerId();
        $this->playCardFromPlayer($card_id, $player_id);
    }

    // Play a card from player hand
    function playCardFromPlayer($card_id, $player_id) {
        self::checkAction('playCard');

        $current_card = $this->deck->getCard($card_id);

        // Sanity check. A more thorough check is done later.
        if ($current_card['location'] != 'hand' || $current_card['location_arg'] != $player_id) {
            throw new BgaUserException(self::_('You do not have this card'));
        }

        $playable_cards = $this->getPlayableCards($player_id);

        if (!array_key_exists($card_id, $playable_cards)) {
            throw new BgaUserException(self::_('You cannot play this card'));
        }

        // Checks are done! now we can play our card
        $this->cards->moveCard($card_id, 'cardsontable', $player_id);
        if (self::getGameStateValue('ledSuit') == 0)
            self::setGameStateValue('ledSuit', $current_card['type']);

        // And notify
        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${rank_displayed} ${suit_displayed}'), array(
            'i18n' => array('suit_displayed', 'rank_displayed'),
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'rank' => $currentCard['type_arg'],
            'rank_displayed' => $this->rank_label[$currentCard['type_arg']],
            'suit' => $currentCard['type'],
            'suit_displayed' => '<span class="bgabnc_icon bgabnc_suit'.$currentCard['type'] . '"></span>',
            'currentPlayer' => $this->getCurrentPlayer()
        ));

        // Next player
        $this->gamestate->nextState('playCard');
    }

/************** Player Action helper functions ****************/

    // Returns the card associated with the trick winner
    // The trick winner id is the location_arg of the card
    function getTrickWinner() {
        // This is the end of the trick
        $cardsOnTable = $this->cards->getCardsInLocation('cardsontable');

        if (count($cardsOnTable) != $this->getPlayerCount()) {
            throw new feException(self::_('Invalid trick card count'));
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

        self::notifyAllPlayers('newRound', clienttranslate('Starting hand ${hand_num}'), array(
            'hand_num' => $handCount,
        ));

        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation('scorepile', 'points');
        // TODO: Take back cards from score piles
        $this->cards->shuffle('deck');
        $this->cards->shuffle('points');
        // Deal cards to each players
        // Create deck, shuffle it and give initial cards
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $cards = $this->cards->pickCards(13, 'deck', $player_id);
            // Notify player about his cards
            self::notifyPlayer($player_id, 'newHand', '', array(
              'cards' => $cards,
              'hand_num' => $handCount));
        }
        $this->gamestate->nextState();
    }

    function stNextPlayer() {
        // Active next player OR end the trick and go to the next trick OR end the hand
        if ($this->cards->countCardInLocation('cardsontable') == 3) {
            $winningCard = $this->getTrickWinner();
            $winningPlayer = $winningCard['location_arg'];

            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer($winningPlayer);
            $this->setCurrentPlayer($winningPlayer);

            // Put cards back in deck
            $this->cards->moveAllCardsInLocation('cardsontable', 'deck');

            // Give point card to player
            $current_point_card = $this->getCardOnTop('points');
            $this->cards->insertCardOnExtremePosition($current_point_card['id'], "scorepile_${winningPlayer}", /* on top */ true);

            // Notify
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick'), array(
                    'player_id' => $winningPlayer,
                    'player_name' => $players[$winningPlayer]['player_name'],
            ));

            self::setGameStateValue("ledSuit", 0);

            if ($this->cards->countCardInLocation('hand') == 0) {
                // End of the hand
                $this->gamestate->nextState("endHand");
            } else {
                // End of the trick
                $this->gamestate->nextState("nextTrick");
            }
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player
            $player_id = self::activeNextPlayer();
            $this->setCurrentPlayer($player_id);
            self::notifyAllPlayers('currentPlayer', '', array(
                'currentPlayer' => $this->getCurrentPlayer()
            ));
            self::giveExtraTime($player_id);
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function argPlayableCards() {
        $player_id = self::getActivePlayerId();
        return array(
            '_private' => array(
                'active' => array(
                    'playableCards' => self::getPlayableCards($player_id)
                )
            )
        );
    }

    function stEndHand() {

        $handScoreInfo = $this->generateScoreInfo();

        // Count and score points, then end the round / game or go to the next hand.
        $players = self::loadPlayersBasicInfos();

        // Check if anyone exceeded 100
        $countPlayersExceeded100 = 0;
        foreach ($handScoreInfo['currentScore'] as $playerId => $currentScore) {
            if ($currentScore + $handScoreInfo['total'][$playerId] >= 100) {
                $countPlayersExceeded100++;
            }
        }

        // Apply scores to player
        foreach ($handScoreInfo['total'] as $player_id => $points) {
            // Calculate the round score
            $playerRoundScore = $handScoreInfo['currentScore'][$player_id] +
                $handScoreInfo['total'][$player_id];
            $this->dbSetRoundScore($player_id, $playerRoundScore);

            if ($points != 0) {
                $this->dbIncScore($player_id, $points);

                self::notifyAllPlayers("points", clienttranslate('${player_name} bid ${bid} and gets ${points} points'), array(
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name'],
                    'bid' => $handScoreInfo['bid'][$player_id],
                    'points' => $points,
                    'roundScore' => $playerRoundScore
                ));
            } else {
                // No point lost (just notify)
                self::notifyAllPlayers("points", clienttranslate('${player_name} bid ${bid} but did not get any points'), array (
                    'player_id' => $player_id,
                    'bid' => $handScoreInfo['bid'][$player_id],
                    'player_name' => $players[$player_id]['player_name']));
            }
        }
        $newScores = $this->getCurrentRoundScores();
        $gameScores = $this->dbGetScores();
        self::notifyAllPlayers("newScores", '', array('newScores' => $newScores, 'gameScores' => $gameScores));

        // Test if this is the end of the round
        // Display the score for the hand
        $handScoreInfo = $this->generateScoreInfo();
        $scoreTable = $this->createHandScoringTable($handScoreInfo);

        // TODO: Check if max score was reached
        if (false) {
            $this->notifyScore($scoreTable, clienttranslate('Final Score'));
            $this->finalizeGameEndState();
            $this->gamestate->nextState("gameEnd");
            return;
        }

        $this->notifyScore($scoreTable, clienttranslate('Hand Score'));

        // Alternate first player
        self::setGameStateValue('firstPlayer', 
            self::getPlayerAfter(self::getGameStateValue('firstPlayer')));
        $this->gamestate->nextState("newHand");
    }

/************** Game state helper functions ****************/

    function finalizeGameEndState() {
        // This will get the scores from Round 3
        $roundScoreInfo = $this->generateRoundScoreInfo();

        $gameScores = $this->dbGetScores();
        self::notifyAllPlayers("newScores", '', array('newScores' => $roundScores, 'gameScores' => $gameScores));
    }

    /**
        Return an array containing all the information needed
        to display the score at the end of a hand. This includes
        trick information, bonus information, and total game score.

        Output:
        {
            'name': {
                <player id> => 'Player name'
                ...
            },
            'bid': {
                <player id> => 1
                ...
            },
            'tricks': {
                <player id> => 1
                ...
            },
            'correctBidCount': {
                <player id> => 1
                ...
            },
            'bonus': {
                <player id> => 20
                ...
            },
            'decrev': {
                <player id> => 30
                ...
            },
            'total': { // Total round score so far
                <player id> => 56
                ...
            },
            'currentScore': { // Total game score so far
                <player id> => 146
                ...
            }
        }
    **/
    function generateScoreInfo() {
        $players = self::loadPlayersBasicInfos();
        $playerBids = [];
        $playerBidsStr = [];
        $playerNames = [];
        $roundScore = [];
        $round = $this->getCurrentRound();
        foreach ($players as $player_id => $player) {
            $bid = $this->getPlayerBid($player_id);
            $playerBids[$player_id] = $bid;
            $playerNames[$player_id] = $player['player_name'];
            $roundScore[$player_id] = $this->dbGetRoundScore($player_id, $round);
        }
        return $this->generateScoreInfoHelper($playerNames, $playerBids,
            $decRevVal, $decRevPlayer, $roundScore);
    }

    function generateScoreInfoHelper($playerNames, $bid, $decRev,
            $decRevPlayer, $currentScores) {
        $result = [];
        $total = [];
        $result['name'] = [];
        foreach ($playerNames as $playerId => $name) {
            $result['name'][$playerId] = $name;
        }
        $madeBid = [];
        $result['total'] = $total;
        $result['currentScore'] = [];
        foreach ($currentScores as $playerId => $score) {
            $result['currentScore'][$playerId] = intval($score);
        }
        return $result;
    }

    /**
        Given the hand score information, create a table to display the
        scores.
    **/
    function createHandScoringTable($scoreInfo) {
        $players = self::loadPlayersBasicInfos();
        $table = [];
        $firstRow = array('');
        foreach ($players as $player_id => $player) {
            $firstRow[] = array('str' => '${player_name}',
                                'args' => array('player_name' => $player['player_name']),
                                'type' => 'header');
        }
        $table[] = $firstRow;

        $bidRow = array(clienttranslate("Bid"));
        foreach ($players as $player_id => $player) {
            $bidRow[] = $scoreInfo['bidStr'][$player_id];
        }
        $table[] = $bidRow;

        $tricksRow = array(clienttranslate("Tricks Taken"));
        foreach ($players as $player_id => $player) {
            $tricksRow[] = $scoreInfo['tricks'][$player_id];
        }
        $table[] = $tricksRow;

        $bonusRow = array(clienttranslate("Bonus"));
        foreach ($players as $player_id => $player) {
            $bonusRow[] = $scoreInfo['bonus'][$player_id];
        }
        $table[] = $bonusRow;

        $totalRow = array(clienttranslate("Total"));
        foreach ($players as $player_id => $player) {
            $totalRow[] = $scoreInfo['total'][$player_id];
        }
        $table[] = $totalRow;

        // Having a separater between hand total and round total is nice
        $table[] = $this->createEmptyScoringRow();

        $roundScoreRow = array(clienttranslate("Game Score"));
        foreach ($players as $player_id => $player) {
            $roundScoreRow[] = $scoreInfo['currentScore'][$player_id];
        }
        $table[] = $roundScoreRow;
        return $table;
    }

    // Display the score
    function notifyScore($table, $message) {
        $this->saveCurrentScoreTable($table);
        $this->notifyAllPlayers("tableWindow", '', array(
            "id" => 'scoreView',
            "title" => $message,
            "table" => $table,
            "closing" => clienttranslate("Continue")
        ));
    }

    /**
        Return an array containing all the information needed
        to display the score at the end of a round. This includes
        trick information, bonus information, and total game score.

        Output:
        {
            'name': {
                <player id> => 'Player name'
                ...
            },
            'roundScore': {
                <player id> => [87, 48, 121]
                ...
            },
            'roundWins': {
                <player id> => 1
                ...
            },
            'gameScore': {
                <player id> => 276
                ...
            },
            'roundBonus': {
                <player id> => [0, 0, 20]
                ...
            },
            'roundTotal': {
                <player id> => 146
                ...
            }
        }
    **/
    function generateRoundScoreInfo() {
        $players = self::loadPlayersBasicInfos();
        $result = [];
        $result['name'] = [];
        $result['roundScore'] = [];
        $result['roundWins'] = [];
        $result['gameScore'] = [];
        $round = $this->getCurrentRound();
        $playerBroke100 = [];
        $countBroke100 = [];
        foreach ($players as $playerId => $player) {
            $result['name'][$playerId] = $player['player_name'];
            $result['roundScore'][$playerId] = [];
            $result['roundWins'][$playerId] = 0;
            $playerBroke100[$playerId] = [];
            for ($i = 0; $i < $round + 1; $i++) {
                $roundScore = $this->dbGetRoundScore($playerId, $i);
                if (!array_key_exists($i, $countBroke100)) {
                    $countBroke100[$i] = 0;
                }
                if ($roundScore >= 100) {
                    $playerBroke100[$playerId][$i] = true;
                    $countBroke100[$i]++;
                    $result['roundWins'][$playerId] += 1;
                } else {
                    $playerBroke100[$playerId][$i] = false;
                }
                $result['roundScore'][$playerId][$i] = $roundScore;
            }
        }
        $result['roundTotal'] = [];
        foreach ($players as $playerId => $player) {
            $result['roundTotal'][$playerId] = [];
            $playerGameScoreTotal = 0;
            for ($i = 0; $i < $round + 1; $i++) {
                $roundBonusPoints = 0;
                $result['roundTotal'][$playerId][$i] =
                    $result['roundScore'][$playerId][$i] + $roundBonusPoints;
                $playerGameScoreTotal += $result['roundTotal'][$playerId][$i];
            }
            $result['gameScore'][$playerId] = $playerGameScoreTotal;
        }
        return $result;
    }

    function createEmptyScoringRow() {
        $players = self::loadPlayersBasicInfos();

        // Add a blank link to separate the hand information from the round info
        $emptyRow = array('');
        foreach ($players as $player_id => $player) {
            $emptyRow[] = '';
        }
        return $emptyRow;
    }

/************** End Game state helper functions ****************/

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
        }
    }
}


