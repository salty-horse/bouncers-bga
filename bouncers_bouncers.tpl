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
<div id="table" class="player_count_{PLAYER_COUNT}">
    <div id="middleRow">
        <div id="playertables">

            <!-- BEGIN player -->
            <div class="bgann_playertable whiteblock bgann_playertable_{DIR}" id="playertable_{PLAYER_ID}">
                <div class="bgann_playertablename" style="color:#{PLAYER_COLOR}">
                    <span id="dealerindicator_{PLAYER_ID}" class="bgann_dealerindicator bgann_hidden">(D)</span>
                    {PLAYER_NAME}
                </div>
                <div class="bgann_playertablecard" id="playertablecard_{PLAYER_ID}">
                </div>
                <span class="bgann_playertable_tricks" id="trick_info_{PLAYER_ID}">
                    <span class="">Tricks taken: </span>
                    <span id="tricks_{PLAYER_ID}">0</span>
                    <span class="bgann_bid"> | Bid: </span>
                    <span id="bid_{PLAYER_ID}" class="bgann_bid bgann_bid_value">?</span>
                </span>
            </div>
            <!-- END player -->

            <div class="whiteblock" id="trumpContainer">
                <div class="">{TRUMP_LABEL}</div>
                <div class="bgann_trump_suit" id="trumpSuit">{NONE}</div>
            </div>
        </div>
    </div>
</div>

<div class="my_cards">
    <div class="whiteblock bgann_container" id="my_hand_container">
        <div class="bgann_section" style="flex-grow: 1;">
            <div style="width: auto; display: flex">
                <h3 id="myhandlabel" class="">{MY_HAND_LABEL}</h3>
            </div>
            <div id="myhand"></div>
        </div>
    </div>
</div>



<script type="text/javascript">

var jstpl_cardontable = '<div class="bgann_cardontable bgann_suit_${suit} bgann_rank_${rank}" id="cardontable_${player_id}"></div>';
var jstpl_player_round_score = '\<div class="bgann_round_score">\
    \<span id="player_round_score_${id}" class="player_score_value">0\</span>\
    \<span class="fa fa-star bgann_round_score_icon"/>\
</div>';

</script>

{OVERALL_GAME_FOOTER}
