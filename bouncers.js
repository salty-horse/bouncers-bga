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
 * bouncers.js
 *
 * Bouncers user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
////////////////////////////////////////////////////////////////////////////////

/*
    In this file, you are describing the logic of your user interface, in Javascript language.
*/

/* global define, ebg, _, $, g_gamethemeurl */
/* eslint no-unused-vars: ["error", {args: "none"}] */

'use strict';

define([
    "dojo",
    "dojo/_base/declare",
    "dojo/dom-style",
    "dojo/_base/lang",
    "dojo/dom-attr",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare, domStyle, lang, attr) {
    return declare("bgagame.bouncers", ebg.core.gamegui, {

        constructor: function() {
            this.playerHand = null;
            this.cardwidth = 72;
            this.cardheight = 96;
            this.lastItemsSelected = [];

            // Timeouts
            this.playCardDuration = 500;
        },

        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameter.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refresh the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        setup: function(gamedatas) {
            console.log('gamedatas:', gamedatas);
            dojo.destroy('debug_output');

            // Player hand
            this.playerHand = this.setupCardStocks('bgabnc_myhand', 'onPlayerHandSelectionChanged');
            // Cards in player's hand
            this.addCardsToStock(this.playerHand, this.gamedatas.hand);

            this.showPointsCard(this.gamedatas.points_card);

            // Cards played on table
            for (var i in this.gamedatas.cardsontable) {
                var card = this.gamedatas.cardsontable[i];
                var color = card.type;
                var value = card.type_arg;
                var player_id = card.location_arg;
                this.playCardOnTable(player_id, color, value, card.id);
            }

            // Current player
            this.markActivePlayerTable(true);

            for (const [player_id, player_info] of Object.entries(this.gamedatas.players)) {
                this.updateScorePile(player_id, player_info.score_pile);
            }

            // Set scores
            this.updateGameScores(this.gamedatas.gameScores);

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            dojo.connect(window, "onresize", this, dojo.hitch(this, "adaptViewportSize"));
        },

        // Initialize a card stock
        // Arguments: div id, function which occurs when the card selection changes
        setupCardStocks: function(id, selectionChangeFunctionName) {
            var stock = new ebg.stock();
            stock.create(this, $(id), this.cardwidth, this.cardheight);
            stock.image_items_per_row = 13;
            stock.autowidth = true;
            stock.setSelectionMode(1);
            dojo.connect(stock, 'onChangeSelection', this, selectionChangeFunctionName);
            for (var suit = 0; suit < 4; suit++) {
                for (var rank = 2; rank <= 14; rank++) {
                    // Build card type id
                    var card_type_id = this.getCardUniqueId(suit, rank);
                    var card_weight = this.getCardWeight(suit, rank);
                    stock.addItemType(card_type_id, card_weight, g_gamethemeurl+'img/cards.jpg', card_type_id);
                }
            }
            return stock;
        },

        // Take care of any logic related to resizing the window
        adaptViewportSize: function() {
            this.adjustCardOverlapToAvailableSpace();
        },

        // Adjust the overlap of cards in your hand
        adjustCardOverlapToAvailableSpace: function() {
            var bodycoords = dojo.marginBox("my_hand_container");
            var numberOfCardsWhichWrap = 0;
            var contentWidth = bodycoords.w - 20; // Minus 10 pixels of padding on either side
            var cardCountInHand = this.playerHand.getAllItems().length;
            var fullSize = cardCountInHand * 76 + (cardCountInHand / 2); // plus a little extra padding
            var cardsThatCanFit = contentWidth / 77;
            numberOfCardsWhichWrap = Math.max(0, cardCountInHand - cardsThatCanFit);
            this.playerHand.setOverlap(100 - Math.min(90, numberOfCardsWhichWrap * 10));
        },

        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //

        onEnteringState: function(stateName, args) {
            console.log("Entering state: " + stateName);
            switch (stateName) {
            case 'playerTurn':
                this.markActivePlayerTable(true);
                if (!this.isCurrentPlayerActive())
                    break;
                this.markPlayableCards(args.args._private.playable_cards);
                break;

            case 'endHand':
                this.markActivePlayerTable(false);
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function(stateName) {
            console.log("Leaving state: " + stateName);
            switch (stateName) {
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function(stateName, args) {
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*
           General Utility methods
         */

        // Returns true for spectators, instant replay (during game), archive mode (after game end)
        isReadOnly: function () {
            return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
        },

        /*
           Card Utility methods
         */

        // Return the 'type id' of the card object given to us by the server
        getCardType: function(serverCard) {
            var color = serverCard.type;
            var rank = serverCard.type_arg;
            return this.getCardUniqueId(color, rank);
        },

        // Get card unique identifier based on its suit and rank
        getCardUniqueId: function(suit, rank) {
            return parseInt(suit) * 13 + (parseInt(rank) - 2);
        },

        // Return the string suit from the card id (also known as the card type)
        getCardSuitFromId: function(card_id) {
            return this.getCardSuit(this.getCardSuitNumFromId(card_id));
        },

        // Return the card suit string from the numerical representation
        getCardSuit: function(suit) {
            return ["club", "diamond", "spade", "heart"][suit];
        },

        // Return the numerical value of the suit from the card id (also known as the card type)
        getCardSuitNumFromId: function(card_id) {
            return Math.floor(card_id / 13);
        },

        // Return the rank from the card id (also known as the card type)
        getCardRankFromId: function(card_id) {
            return (card_id % 13) + 2;
        },

        // Return true if both passed cards are the same
        // Only works for cards that are retrieved from the various stocks
        isCardSame: function(cardOne, cardTwo) {
            return cardOne.id == cardTwo.id && cardOne.type == cardTwo.type;
        },

        // Return true if a card is present in the given stock
        isCardInStock: function(stock, card) {
            var foundCard = false;
            var that = this;
            stock.getAllItems().forEach(function(stockCard) {
                if (that.isCardSame(stockCard, card)) {
                    foundCard = true;
                    return;
                }
            });
            return foundCard;
        },

        getCardsFromStockById: function(stock, cardIds) {
            return stock.getAllItems().filter(function (card) {
                return cardIds.includes(parseInt(card.id));
            });
        },

        // Map cards given to us by the server into cards that stocks deal with
        serverCardsToClientCards: function(serverCards) {
            var that = this;
            return Object.entries(serverCards).map(function(serverCard) {
                var card = serverCard[1];
                var color = card.type;
                var rank = card.type_arg;
                return {
                  type: that.getCardType(card),
                  id: card.id
                };
            });
        },

        addCardsToStock: function(stock, cards) {
            this.serverCardsToClientCards(cards).forEach(function(card) {
                stock.addToStockWithId(card.type, card.id);
            });
        },

        markPlayableCards: function(cards) {
            for (let card_id of cards) {
                let elem = document.getElementById(`bgabnc_myhand_item_${card_id}`);
                if (elem) {
                    elem.classList.add('bgabnc_playable');
                }
            }
        },

        unmarkPlayableCards: function() {
            document.querySelectorAll('#bgabnc_myhand .bgabnc_playable').forEach(
                e => e.classList.remove('bgabnc_playable'));
        },

        // This is the order that cards are sorted
        getCardWeight: function(suit, rank) {
            return suit * 13 + (parseInt(rank) - 2);
        },

        // Update the game scores of all players
        updateGameScores: function(gameScores) {
            for (var playerId in gameScores) {
                this.updatePlayerScore(playerId, gameScores[playerId]);
            }
        },

        // Update a particular player's total score
        updatePlayerScore: function(playerId, playerScore) {
            if (this.scoreCtrl[playerId]) {
                this.scoreCtrl[playerId].toValue(playerScore);
            }
        },

        // Play a particular card from coming from player_id on the table
        // The card will come from the player boards, unless the card
        // already exists on the table in either the revealed player's hand
        // or 'my' hand
        playCardOnTable: function(player_id, suit, value, card_id) {
            dojo.place(
                this.format_block('jstpl_cardontable', {
                    card_id: card_id,
                    suit: this.getCardSuit(suit),
                    rank: value,
                    player_id: player_id
                }), 'bgabnc_playertablecard_'+player_id);

            var cardCameFromSomeoneElse = player_id != this.player_id || this.isSpectator;
            if (cardCameFromSomeoneElse) {
                // Some opponent played a card (or spectator is observing)
                // Move card from player panel
                this.placeOnObject('bgabnc_cardontable_'+player_id, 'overall_player_board_'+player_id);
            } else {
                // You played a card. If it exists in your hand, move card from there and remove
                // corresponding item
                if ($('bgabnc_myhand_item_'+card_id)) {
                    this.placeOnObject('bgabnc_cardontable_'+player_id, 'bgabnc_myhand_item_'+card_id);
                    this.playerHand.removeFromStockById(card_id);
                }
            }
            // In any case: move it to its final destination
            this.slideToObject('bgabnc_cardontable_'+player_id, 'bgabnc_playertablecard_'+player_id).play();

            // Adjust card overlap now that there are fewer cards in hand
            if (!cardCameFromSomeoneElse) {
                this.adjustCardOverlapToAvailableSpace();
            }
        },

        /*
           Other UI utility methods
         */

        showPointsCard: function(value) {
            let container = document.getElementById('bgabnc_points_slot');
            let elem = this.format_block('jstpl_points_card', {
                    value: this.gamedatas.rank_labels[value],
                });
            container.appendChild(elem);
        },

        updateScorePile: function(player_id, score_pile) {
            document.getElementById(`bgabnc_scorepile_total_${player_id}`).textContent = score_pile.score;
            let pile = [];
            for (let card of score_pile.score_pile) {
                let label = this.gamedatas.rank_labels[card[0]];
                pile.push((card.length == 2) ? `<s>${label}</s>`: label);
            }
            if (!pile) { 
                pile.push('-');
            }
            document.getElementById(`bgabnc_scorepile_${player_id}`).innerHTML = pile.join(', ');
        },

        // Provide a visual indication as to who's action it is
        markActivePlayerTable: function(turn_on, player_id) {
            if (!player_id) {
                player_id = this.getActivePlayerId();
            }
            dojo.query('.bgabnc_playertable').removeClass('bgabnc_activeplayer');
            if (!turn_on)
                return;
            if (!player_id)
                return;
            dojo.addClass('bgabnc_playertable_' + this.getActivePlayerId(), 'bgabnc_activeplayer');
        },

        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        // Callback for when the player hand selection changes
        onPlayerHandSelectionChanged: function() {
            let items = this.playerHand.getSelectedItems();
            if (items.length == 0)
                return;
            this.playerHand.unselectAll();
            if (!this.isCurrentPlayerActive()) {
                return;
            }
            if (!document.getElementById(this.playerHand.getItemDivId(items[0].id)).classList.contains('bgabnc_playable'))
                return;
            if (this.checkAction('playCard', true)) {
                this.playCard(items[0].id);
            }
        },

        // Play an individual card
        playCard: function(card_id) {
            this.ajaxCallWrapper('playCard', { id: card_id });
            this.playerHand.unselectAll();
        },

        // Wrap making AJAX calls to the backend
        ajaxCallWrapper: function(action, args, skipActionCheck, handler) {
            if (!args) {
                args = {};
            }
            args.lock = true;

            if (skipActionCheck || this.checkAction(action)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`,
                              args, this, (result) => {}, handler);
            }
        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to your "notifyAllPlayers" and "notifyPlayer" calls in
                  your emptygame.game.php file.

        */

        setupNotifications: function() {
			[
            	'newHand',
            	'newHandPublic',
            	'playCard',
            	'trickWin',
            	'points',
            	'newScores',
			].forEach(s => {
				dojo.subscribe(s, this, `notif_${s}`);
			});
            this.notifqueue.setSynchronous('playCard', (this.playCardDuration));
            this.notifqueue.setSynchronous('trickWin', 0);
        },

        // From this point and below, you can write your game notifications handling methods

        // We received a new full hand of 12 cards.
        // This message is sent to only you
        notif_newHand: function(notif) {
            if (this.isReadOnly()) {
                // Dismiss any score dialogs
                dojo.byId("close_btn").click();
            }

            // Just to be sure, clean up any old state
            this.playerHand.removeAll();

            this.showPointsCard(value);

            for (var i in notif.args.cards) {
                var card = notif.args.cards[i];
                var color = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }
            this.adjustCardOverlapToAvailableSpace();
        },

        // A new hand is starting
        // This message is sent to every player
        notif_newHandPublic: function(notif) {
            this.showPointsCard(notif.args.points_card);
            this.updateRoundNum(notif.args.hand_num);
        },

        // A card was played
        // This is sent to all players
        notif_playCard: function(notif) {
            // Mark the active player, in case this was an automated move (skipping playerTurn state)
            this.markActivePlayerTable(true, notif.args.player_id);
            this.unmarkPlayableCards();
            this.playCardOnTable(notif.args.player_id, notif.args.suit,
                                 notif.args.rank, notif.args.card_id);
        },

        notif_trickWin: async function(notif) {
            let winner_id = notif.args.player_id;

            for (let player_id in this.gamedatas.players) {
                if (player_id == winner_id) {
                    // Make sure the moved card is above the winner card
                    document.getElementById('bgabnc_points_card').style.zIndex = 3;
                    // TODO: Doesn't work because not relative/aboslute. Use element animate()?
                    this.slideToObjectAndDestroy('bgabnc_points_card', 'bgabnc_scorepile_' + player_id);
                }
                this.fadeOutAndDestroy('bgabnc_cardontable_' + player_id);
            }
            this.updateScorePile(winner_id, notif.args.score_pile);

            if (!this.instantaneousMode)
                await new Promise(r => setTimeout(r, 1000));

            if (notif.args.points_card)
                this.showPointsCard(notif.args.points_card);

            this.notifqueue.setSynchronousDuration(0);
        },

        // Points were awarded for the hand
        // This will be called once for each player, and the information
        // broadcast to all players
        notif_points: function(notif) {
            var playerId = notif.args.player_id;
            var score = notif.args.roundScore;
        },

        // All players scores were updated
        // This is sent to all players
        notif_newScores: function(notif) {
            // Update players' scores
            this.updateGameScores(notif.args.gameScores);
        },
   });
});


