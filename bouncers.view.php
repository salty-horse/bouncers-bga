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
 * bouncers.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in emptygame_emptygame.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

  require_once( APP_BASE_PATH.'view/common/game.view.php' );

  class view_bouncers_bouncers extends game_view
  {
    function getGameName() {
        return 'bouncers';
    }

    function build_page($viewArgs) {
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count($players);

        /*********** Place your code below:  ************/

        $this->tpl['PLAYER_COUNT'] = $players_nbr;

        // Arrange players so that I am on south
        $player_to_dir = $this->game->getPlayersToDirection();

        $this->page->begin_block('bouncers_bouncers', 'player');
        foreach ($player_to_dir as $player_id => $dir) {
            $this->page->insert_block('player', [
                'PLAYER_ID' => $player_id,
                'PLAYER_NAME' => $players[$player_id]['player_name'],
                'PLAYER_COLOR' => $players[$player_id]['player_color'],
                'DIR' => $dir
            ]);
        }

        $this->tpl['MY_HAND'] = self::_('My hand');
        $this->tpl['ROUND_LABEL'] = self::_('Hand ');
        $this->tpl['SCORE_CARD_LABEL'] = self::_('Points Card');
        $this->tpl['UPCOMING'] = self::_('Upcoming cards');
        $this->tpl['SCORE_PILE_TOTAL'] = self::_('Points');
        $this->tpl['MY_HAND_LABEL'] = self::_('My Hand');

        /*********** Do not change anything below this line  ************/
      }
  }


