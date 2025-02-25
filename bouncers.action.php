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
 * bouncers.action.php
 *
 * Bouncers main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/emptygame/emptygame/myAction.html", ...)
 *
 */

class action_bouncers extends APP_GameAction {
   	public function __default() {
  	    if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
  	    } else {
            $this->view = "bouncers_bouncers";
            self::trace( "Complete reinitialization of board game" );
        }
  	}

    public function playCard() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->playCard($card_id);
        self::ajaxResponse();
    }

    public function submitBid() {
        self::setAjaxMode();
        $cards_raw = self::getArg("cards", AT_numberlist, true);
        $decrev = self::getArg("declareOrReveal", AT_posint, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';')
            $cards_raw = substr($cards_raw, 0, -1);
        if ($cards_raw == '')
            $cards = array();
        else
            $cards = explode(';', $cards_raw);

        $this->game->submitBid($cards, $decrev);
        self::ajaxResponse();
    }

    public function undoBid() {
        self::setAjaxMode();
        $this->game->undoBid();
        self::ajaxResponse();
    }

    public function displayScore() {
        self::setAjaxMode();
        $this->game->displayLastScoreTable();
        self::ajaxResponse();
    }

    private function base64url_decode($data) {
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
    }
}


