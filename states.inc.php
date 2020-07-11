<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Coup implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * Coup game states description
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

$machinestates = array(
    // The initial state. Please do not modify.
    ST_GAME_BEGIN => array(
        'name' => 'gameSetup',
        'description' => '',
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => array(
            '' => ST_ROUND_BEGIN,
        )
    ),

    ST_ROUND_BEGIN => array(
        'name' => 'roundBegin',
        'description' => '',
        'type' => 'game',
        'action' => 'stRoundBegin',
        'updateGameProgression' => true,
        'transitions' => array(
            '' => ST_PLAYER_BEGIN,
        )
    ),

    ST_PLAYER_BEGIN => array(
        'name' => 'playerBegin',
        'description' => clienttranslate('${actplayer} must take an action.'),
        'descriptionmyturn' => clienttranslate('${you} must take an action.'),
        'type' => 'activeplayer',
        'action' => 'stPlayerBegin',
        'possibleactions' => array('act'),
        'transitions' => array(
            'ask' => ST_ACT,
            'execute' => ST_EXECUTE,
            'zombiePass' => ST_PLAYER_END,
        )
    ),

    ST_ACT => array(
        'name' => 'ask',
        'description' => clienttranslate('Wait until all players have responded.'),
        'descriptionmyturn' => clienttranslate('Stop ${player_name2}\'s ${action_name}?'),
        'type' => 'multipleactiveplayer',
        'args' => 'argAsk',
        'action' => 'stAsk',
        'possibleactions' => array('actionBlock', 'actionNo', 'actionYes'),
        'transitions' => array(
            'askBlock' => ST_ASK_BLOCK,
            'yes' => ST_CHALLENGE,
            'yesAll' => ST_CHALLENGE_ALL,
            'execute' => ST_EXECUTE,
            'zombiePass' => ST_EXECUTE,
        )
    ),

    ST_SECOND_CHANCE => array(
        'name' => 'secondChance',
        'description' => clienttranslate('Wait until all players have responded.'),
        'descriptionmyturn' => clienttranslate('Stop ${player_name2}\'s ${action_name}?'),
        'type' => 'multipleactiveplayer',
        'args' => 'argAsk',
        'possibleactions' => array('actionBlock', 'actionNo'),
        'transitions' => array(
            'askBlock' => ST_ASK_BLOCK,
            'execute' => ST_EXECUTE,
            'zombiePass' => ST_EXECUTE,
        )
    ),

    ST_ASK_BLOCK => array(
        'name' => 'askBlock',
        'description' => clienttranslate('Wait until all players have responded.'),
        'descriptionmyturn' => clienttranslate('Stop ${player_name2}\'s block?'),
        'type' => 'multipleactiveplayer',
        'args' => 'argAsk',
        'action' => 'stAsk',
        'possibleactions' => array('actionNo', 'actionYes'),
        'transitions' => array(
            'yes' => ST_CHALLENGE_BLOCK,
            'execute' => ST_EXECUTE,
            'zombiePass' => ST_EXECUTE,
        )
    ),

    ST_ASK_CHOOSE_CARD => array(
        'name' => 'askChooseCard',
        'description' => clienttranslate('${actplayer} must choose a card: ${reason} (${detail}).'),
        'descriptionmyturn' => clienttranslate('${you} must choose a card: ${reason} (${detail}).'),
        'type' => 'activeplayer',
        'args' => 'argChooseCard',
        'possibleactions' => array('actionChooseCard'),
        'transitions' => array(
            'challenge' => ST_CHALLENGE,
            'challengeBlock' => ST_CHALLENGE_BLOCK,
            'killLoss' => ST_KILL_LOSS,
            'killCoup' => ST_KILL_COUP,
            'execute' => ST_EXECUTE,
        )
    ),

    ST_ASK_DISCARD => array(
        'name' => 'askDiscard',
        'description' => clienttranslate('${actplayer} must discard ${count} cards.'),
        'descriptionmyturn' => clienttranslate('${you} must discard ${count} cards.'),
        'type' => 'activeplayer',
        'args' => 'argDiscard',
        'possibleactions' => array('actionDiscard'),
        'transitions' => array(
            '' => ST_PLAYER_END,
        )
    ),

    ST_ASK_EXAMINE => array(
        'name' => 'askExamine',
        'description' => clienttranslate('${actplayer} must act on ${player_name2}\'s card.'),
        'descriptionmyturn' => clienttranslate('${you} must act on ${player_name2}\'s card.'),
        'type' => 'activeplayer',
        'args' => 'argExamine',
        'action' => 'stExamine',
        'possibleactions' => array('actionExamineKeep', 'actionExamineExchange'),
        'transitions' => array(
            '' => ST_PLAYER_END,
        )
    ),

    ST_CHALLENGE => array(
        'name' => 'challenge',
        'description' => '',
        'type' => 'game',
        'action' => 'stChallenge',
        'transitions' => array(
            'askChooseCard' => ST_ASK_CHOOSE_CARD,
            'killLoss' => ST_KILL_LOSS,
        )
    ),

    ST_CHALLENGE_BLOCK => array(
        'name' => 'challengeBlock',
        'description' => '',
        'type' => 'game',
        'action' => 'stChallengeBlock',
        'transitions' => array(
            'askChooseCard' => ST_ASK_CHOOSE_CARD,
            'killLoss' => ST_KILL_LOSS,
        )
    ),

    ST_CHALLENGE_ALL => array(
        'name' => 'challengeAll',
        'description' => '',
        'type' => 'game',
        'action' => 'stChallengeAll',
        'transitions' => array(
            'killLoss' => ST_KILL_LOSS,
        )
    ),

    ST_KILL_LOSS => array(
        'name' => 'killLoss',
        'description' => '',
        'type' => 'game',
        'action' => 'stKillLoss',
        'transitions' => array(
            'askChooseCard' => ST_ASK_CHOOSE_CARD,
            'secondChance' => ST_SECOND_CHANCE,
            'execute' => ST_EXECUTE,
            'roundEnd' => ST_ROUND_END,
        )
    ),

    ST_EXECUTE => array(
        'name' => 'execute',
        'description' => '',
        'type' => 'game',
        'action' => 'stExecute',
        'transitions' => array(
            'playerEnd' => ST_PLAYER_END,
            'killCoup' => ST_KILL_COUP,
            'askChooseCard' => ST_ASK_CHOOSE_CARD,
            'askDiscard' => ST_ASK_DISCARD,
            'askExamine' => ST_ASK_EXAMINE,
        )
    ),

    ST_KILL_COUP => array(
        'name' => 'killCoup',
        'description' => '',
        'type' => 'game',
        'action' => 'stKillCoup',
        'transitions' => array(
            'askChooseCard' => ST_ASK_CHOOSE_CARD,
            'playerEnd' => ST_PLAYER_END,
        )
    ),

    ST_PLAYER_END => array(
        'name' => 'playerEnd',
        'description' => '',
        'type' => 'game',
        'action' => 'stPlayerEnd',
        'updateGameProgression' => true,
        'transitions' => array(
            'playerBegin' => ST_PLAYER_BEGIN,
            'roundEnd' => ST_ROUND_END,
        )
    ),

    ST_ROUND_END => array(
        'name' => 'roundEnd',
        'description' => '',
        'type' => 'game',
        'action' => 'stRoundEnd',
        'updateGameProgression' => true,
        'transitions' => array(
            'roundBegin' => ST_ROUND_BEGIN,
            'gameEnd' => ST_GAME_END,
        )
    ),

    // Final state.
    // Please do not modify.
    ST_GAME_END => array(
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    )
);
