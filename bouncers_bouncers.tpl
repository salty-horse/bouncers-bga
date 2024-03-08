{OVERALL_GAME_HEADER}

<!--
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Bouncers implementation : © Ori Avtalion <ori@avtalion.name>
-- Based on NinetyNine implementation: © Eric Kelly <boardgamearena@useric.com>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    bouncers_bouncers.tpl

    This is the HTML template of your game.

    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.

    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format

    See your "view" PHP file to check how to set variables and control blocks
-->
<div id="bgbnc_table">
    <div id="bgabnc_playarea">
        <div id="bgabnc_playertables">
            <div class="bgabnc_flex_break"></div>

            <!-- BEGIN player -->
            <div class="bgabnc_playertable whiteblock bgabnc_playertable_{DIR}" id="bgabnc_playertable_{PLAYER_ID}">
                <div class="bgabnc_playertablename" style="color:#{PLAYER_COLOR}">
                    {PLAYER_NAME}
                </div>
                <div class="bgabnc_playertablecard" id="bgabnc_playertablecard_{PLAYER_ID}"></div>
                <div>
                    <span>{SCORE_PILE_TOTAL}:</span> <span id="bgabnc_scorepile_total_{PLAYER_ID}">0</span>
                    <div id="bgabnc_scorepile_{PLAYER_ID}"></div>
                </div>
            </div>
            <!-- END player -->

            <div class="whiteblock" id="bgabnc_pointstable">
                <div>{SCORE_CARD_LABEL}</div>
                <div id="bgabnc_points_slot"></div>
            </div>
        </div>
    </div>
</div>

<div class="bgabnc_my_cards">
    <div class="whiteblock bgabnc_container" id="bgabnc_my_hand_container">
        <div class="bgabnc_section" style="flex-grow: 1;">
            <div style="width: auto; display: flex">
                <h3>{MY_HAND_LABEL}</h3>
            </div>
            <div id="bgabnc_myhand"></div>
        </div>
    </div>
</div>



<script type="text/javascript">

var jstpl_cardontable = '<div class="bgabnc_cardontable bgabnc_suit_${suit} bgabnc_rank_${rank}" id="bgabnc_cardontable_${player_id}"></div>';
var jstpl_points_card = '<div class="bgabnc_points_card"><div class="bgabnc_points_card_x"></div><div class="bgabnc_points_card_letter">${value}</div></div>';
var jstpl_points_card_small = '<div class="bgabnc_points_card bgabnc_small"><div class="bgabnc_points_card_x ${on_class}"></div><div class="bgabnc_points_card_letter">${value}</div></div>';

</script>

{OVERALL_GAME_FOOTER}
