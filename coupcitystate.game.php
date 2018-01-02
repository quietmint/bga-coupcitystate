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
  * coupcitystate.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */

require_once(APP_GAMEMODULE_PATH.'module/table/table.game.php');

class coupcitystate extends Table
{
    public function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels(array(
            'action' => 10,
            'reasonChoose' => 11,
            'round' => 12,
            'playerTurn' => 20,
            'playerChallenge' => 21,
            'playerBlock' => 22,
            'playerKill' => 23,
            'playerTarget' => 24,
            'cardReplace' => 30,
            'cardReveal' => 31,
            'cardKill' => 32,
            'cardCoup' => 33,
            'typeBlock' => 40,
            'requiredScore' => 100
        ));

        $this->cards = self::getNew('module.common.deck');
        $this->cards->init('card');
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return 'coupcitystate';
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {
        self::DbQuery('DELETE FROM player');

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = 'INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ';
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."')";
        }
        self::DbQuery($sql . implode($values, ','));
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue('playerTurn', 0);

        // Init game statistics
        self::initStat('player', 'turns', 0);
        self::initStat('player', 'wealthIn', 0);
        self::initStat('player', 'wealthOut', 0);
        self::initStat('player', 'truth', 0);
        self::initStat('player', 'lie', 0);
        self::initStat('player', 'honesty', 100);
        self::initStat('player', 'action1', 0);
        self::initStat('player', 'action2', 0);
        self::initStat('player', 'action3', 0);
        self::initStat('player', 'action4', 0);
        self::initStat('player', 'action5', 0);
        self::initStat('player', 'action6', 0);
        self::initStat('player', 'action7', 0);
        self::initStat('player', 'blockIssued', 0);
        self::initStat('player', 'blockReceived', 0);
        self::initStat('player', 'challengeIssued', 0);
        self::initStat('player', 'challengeReceived', 0);
        self::initStat('player', 'challengeWin', 0);
        self::initStat('player', 'challengeLoss', 0);

        // Create 3 cards of each type
        $cards = array();
        foreach ($this->characters as $character => $character_ref) {
            if ($character > 0) {
                $cards[] = array('type' => "$character", 'type_arg' => 0, 'nbr' => 3);
            }
        }
        $this->cards->createCards($cards, 'deck');
        self::notifyAllPlayers('message', clienttranslate('The deck has ${size} cards with ${copies} copies of each character.'), array(
            'size' => 15,
            'copies' => 3
        ));

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $player_id = self::getCurrentPlayerId();
        $public = $this->getPublicData();
        $private = $this->getPrivateData($player_id);

        return array(
            'actions' => $this->actions,
            'characters' => $this->characters
        ) + $public + $private;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    public function getGameProgression()
    {
        // Calculate progress for prior rounds
        $requiredScore = self::getGameStateValue('requiredScore');
        $score = 0;
        if ($requiredScore > 1) {
            $score = self::getUniqueValueFromDB('SELECT MAX(player_score) FROM player');
        }
        $scoreProgress = $score / $requiredScore * 100;

        // Calculate progress for current round
        $tableauCount = $this->cards->countCardInLocation('tableau');
        $handCount = $this->cards->countCardInLocation('hand');
        $cardCount = $tableauCount + $handCount;
        $cardValue = $cardCount == 0 ? 0 : 100 / $cardCount;
        $coinValue = $cardValue / 10;
        $coins = min(9, self::getUniqueValueFromDB('SELECT MAX(player_wealth) FROM player'));
        $roundProgress = min(100, $cardValue * $tableauCount + $coinValue * $coins);

        // Calculate total progress
        $progress = $scoreProgress + ($roundProgress / $requiredScore);
        return floor(min(100, $progress));
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    public function getPublicData()
    {
        $players = self::getCollectionFromDb('SELECT player_id id, player_score score, player_wealth wealth, player_eliminated eliminated, round_eliminated, balloon FROM player ORDER BY player_no');
        foreach ($players as $player_id => $player) {
            if ($player['balloon']) {
                $players[$player_id]['balloon'] = unserialize($player['balloon']);
            }
            $players[$player_id]['handCount'] = $this->cards->countCardInLocation('hand', $player_id);
            $players[$player_id]['tableau'] = $this->cards->getCardsInLocation('tableau', $player_id);
        }

        return array(
            'players' => $players,
            'turn' => self::getGameStateValue('playerTurn'),
            'deckCount' => $this->cards->countCardInLocation('deck')
        );
    }

    public function getPrivateData($player_id)
    {
        $players = self::loadPlayersBasicInfos();
        $spectator = !array_key_exists($player_id, $players);
        $result = array();
        if (!$spectator) {
            $result['hand'] = $this->cards->getCardsInLocation('hand', $player_id);
        }
        return $result;
    }

    public function getName($player_id)
    {
        return self::getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id=$player_id");
    }

    public function getPlayerIds($skip=null)
    {
        $sql = 'SELECT player_id FROM player WHERE round_eliminated = 0 AND player_eliminated = 0 AND player_zombie = 0';
        if ($skip != null) {
            $sql .= " AND player_id != $skip";
        }
        $sql .= ' ORDER BY player_no';
        return self::getObjectListFromDB($sql, true);
    }

    public function getCard($card_id, $location=null, $location_arg=0)
    {
        // Get a card include character attributes
        $card = $this->cards->getCard($card_id);

        // Optionally verify the card's location
        if ($card == null) {
            throw new BgaVisibleSystemException('Card ' . $card_id . ' not found.');
        }
        if ($location != null && $card['location'] != $location || $location_arg != 0 && $card['location_arg'] != $location_arg) {
            throw new BgaVisibleSystemException('Card ' . $card_id . ' not in ' . $card['location'] . ($location_arg ? ' for player ' . $location_arg : ''));
        }

        $card = array_merge($this->characters[$card['type']], $card);
        return $card;
    }

    public function getCardIds($location, $location_arg)
    {
        // Get multiple cards as ID list
        $ids = array();
        $cards = $this->cards->getCardsInLocation($location, $location_arg);
        foreach ($cards as $card) {
            $ids[] = $card['id'];
        }
        return $ids;
    }

    public function getWealth($player_id)
    {
        return self::getUniqueValueFromDB("SELECT player_wealth FROM player WHERE player_id=$player_id");
    }

    public function addWealth($player_id, $amount)
    {
        self::DbQuery("UPDATE player SET player_wealth = GREATEST(0, player_wealth + $amount) WHERE player_id='$player_id'");
        return $this->getWealth($player_id);
    }

    public function updateHonesty($player_id)
    {
        $lies = self::getStat('lie', $player_id);
        $truths = self::getStat('truth', $player_id);
        self::setStat(round($truths / ($lies + $truths) * 100), 'honesty', $player_id);
    }

    public function doBalloon($action, $msg, $args)
    {
        $args_str = self::escapeStringForDB(serialize($args));
        if ($args['balloon'] != 'no') {
            self::DbQuery("UPDATE player SET balloon=NULL");
        }
        if ($args['balloon']) {
            $player_id = $args['player_id'];
            self::DbQuery("UPDATE player SET balloon='$args_str' WHERE player_id=$player_id");
        }
        self::notifyAllPlayers($action, $msg, $args);
    }

    public function doKill($player_id, $playerTurn, $reason, $card_id, $card_id2=0)
    {
        if ($card_id2 > 0) {
            // Double elimination
            $this->cards->moveCards(array($card_id, $card_id2), 'tableau', $player_id);
            $handCount = $this->cards->countCardInLocation('hand', $player_id);
            $card = $this->getCard($card_id);
            $card2 = $this->getCard($card_id2);
            $this->doBalloon('reveal', clienttranslate('${player_name} loses both the ${card_name} and the ${card_name2} in a double elimination') . ' (' . $this->reasonText[$reason] . ').', array(
                'player_id' => $player_id,
                'player_name' => $this->getName($player_id),
                'player_name2' => $this->getName($playerTurn),
                'card_ids' => array($card['id'], $card2['id']),
                'card_types' => array($card['type'], $card2['type']),
                'card_name' => $card['name'],
                'card_name2' => $card2['name'],
                'balloon' => $this->balloons['die2']
            ));
        } else {
            $this->cards->moveCard($card_id, 'tableau', $player_id);
            $handCount = $this->cards->countCardInLocation('hand', $player_id);
            $card = $this->getCard($card_id);
            if ($handCount == 0) {
                $msg = clienttranslate('${player_name} loses the ${card_name} and is eliminated') . ' (' . $this->reasonText[$reason] . ').';
            } else {
                $msg = clienttranslate('${player_name} loses the ${card_name}'). ' (' . $this->reasonText[$reason] . ').';
            }
            $args = array(
                'player_id' => $player_id,
                'player_name' => $this->getName($player_id),
                'card_ids' => array($card['id']),
                'card_types' => array($card['type']),
                'card_name' => $card['name'],
                'balloon' => $this->balloons['die']
            );
            if ($playerTurn > 0) {
                $args['player_name2'] = $this->getName($playerTurn);
            }
            $this->doBalloon('reveal', $msg, $args);
        }

        // Check for elimination
        if ($handCount == 0) {
            self::notifyAllPlayers('wealthInstant', '', array(
                'player_id' => $player_id,
                'wealth' => 0
            ));
            $this->eliminate($player_id);
            return true;
        }
        return false;
    }

    public function eliminate($player_id, $forever=false)
    {
        // Reveal cards in hand
        $hand = $this->cards->getCardsInLocation('hand', $player_id);
        if (count($hand) > 0) {
            $ids = array();
            $types = array();
            foreach ($hand as $card) {
                $ids[] = $card['id'];
                $types[] = $card['type'];
            }
            $this->cards->moveCards($ids, 'tableau', $player_id);
            self::notifyAllPlayers('revealInstant', '', array(
              'player_id' => $player_id,
              'card_ids' => $ids,
              'card_types' => $types
          ));
        }
        self::DbQuery("UPDATE player SET round_eliminated = 1, player_wealth = 0 WHERE player_id = $player_id");
        self::notifyAllPlayers('eliminate', '', array(
            'player_id' => $player_id
        ));
        if ($forever) {
            self::eliminatePlayer($player_id);
        }
    }

    public function checkWin()
    {
        // Eliminate new zombies
        $newZombies = self::getObjectListFromDB('SELECT player_id FROM player WHERE player_eliminated = 0 AND player_zombie = 1', true);
        if (count($newZombies) > 0) {
            foreach ($newZombies as $zombie_id) {
                $this->eliminate($zombie_id, true);
            }
        }

        // Count active players
        $players = $this->getPlayerIds();
        if (count($players) == 1) { // win
            $requiredScore = self::getGameStateValue('requiredScore');
            if ($requiredScore > 1) {
                self::notifyAllPlayers('message', clienttranslate('End of round ${round}: ${player_name} wins!'), array(
                    'player_name' => $this->getName($players[0]),
                    'round' => self::getGameStateValue('round')
                ));
            }
            self::DbQuery("UPDATE player SET player_score = player_score + 1 WHERE player_id = $players[0]");
            $this->gamestate->nextState('roundEnd');
            return true;
        }
        return false;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    ////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in coupcitystate.action.php)
    */

    public function act($action, $target)
    {
        self::checkAction('act');
        $player_id = self::getActivePlayerId();
        $action_ref = $this->actions[$action];

        // Check wealth
        $wealth = $this->getWealth($player_id);
        if ($wealth >= 10 && $action_ref['name'] != 'Coup') {
            throw new BgaUserException(self::_('You must Coup because you have ₤10.'));
        } elseif ($wealth < $action_ref['cost']) {
            throw new BgaUserException(sprintf(self::_('You need ₤%d for this action.'), $action_ref['cost']));
        }

        // Check target
        if ($action_ref['target'] == true) {
            if ($target == 0 || $target == $player_id || $this->cards->countCardInLocation('hand', $target) == 0) {
                throw new BgaUserException(self::_('Choose an active player.'));
            }
            if ($action_ref['name'] == 'Steal' && $this->getWealth($target) == 0) {
                throw new BgaUserException(self::_('Choose an active player with money.'));
            }
            self::setGameStateValue('playerTarget', $target);
        }
        self::setGameStateValue('action', $action);

        // Is this a lie? (only for statistics, we'll recompute later)
        if ($action_ref['character'] > 0) {
            $hand = $this->cards->getCardsInLocation('hand', $player_id);
            $lie = true;
            foreach ($hand as $card_id => $card) {
                if ($action_ref['character'] == $card['type']) {
                    $lie = false;
                    break;
                }
            }
            self::incStat(1, $lie ? 'lie' : 'truth', $player_id);
            $this->updateHonesty($player_id);
        }

        // Ask other players?
        if ($action_ref['logAttempt']) {
            $args = array(
                'player_id' => $player_id,
                'player_name' => $this->getName($player_id),
                'balloon' => $action_ref['balloonAttempt'],
                'action' => $action
            );
            if ($target) {
                $args['player_name2'] = $this->getName($target);
            }
            if ($action_ref['character'] > 0) {
                $character = $action_ref['character'];
                $args['card_name'] = $this->characters[$character]['name'];
            }
            $this->doBalloon('balloonInstant', $action_ref['logAttempt'], $args);
            $this->gamestate->nextState('ask');
        } else {
            $this->gamestate->nextState('execute');
        }
    }

    public function actionNo()
    {
        self::checkAction('actionNo');
        $player_id = self::getCurrentPlayerId();
        $this->doBalloon('balloonInstant', '', array(
            'player_id' => $player_id,
            'balloon' => 'no'
        ));
        $this->gamestate->setPlayerNonMultiactive($player_id, 'execute');
    }

    public function actionYes()
    {
        self::checkAction('actionYes');
        $player_id = self::getCurrentPlayerId();
        self::setGameStateValue('playerChallenge', $player_id);

        $action = self::getGameStateValue('action');
        $action_ref = $this->actions[$action];
        $playerBlock = self::getGameStateValue('playerBlock');
        if ($playerBlock > 0) {
            $player = $playerBlock;
            $type = self::getGameStateValue('typeBlock');
        } else {
            $player = self::getGameStateValue('playerTurn');
            $type = $action_ref['character'];
        }
        // Delay if target is auto-choosing
        $action = $this->cards->countCardInLocation('hand', $player) == 1 ? 'balloon' : 'balloonInstant';
        $this->doBalloon($action, clienttranslate('${player_name} challenges ${player_name2} to reveal the ${card_name}!'), array(
            'player_id' => $player_id,
            'player_name' => $this->getName($player_id),
            'player_name2' => $this->getName($player),
            'card_name' => $this->characters[$type]['name'],
            'balloon' => $this->balloons['challenge']
        ));
        self::incStat(1, 'challengeIssued', $player_id);
        self::incStat(1, 'challengeReceived', $player);

        $this->gamestate->nextState('yes');
    }

    public function actionBlock($card_type)
    {
        self::checkAction('actionBlock');
        $player_id = self::getCurrentPlayerId();
        $playerTurn = self::getGameStateValue('playerTurn');
        self::setGameStateValue('playerBlock', $player_id);
        self::setGameStateValue('typeBlock', $card_type);

        // Is this a lie? (only for statistics, we'll recompute later)
        $hand = $this->cards->getCardsInLocation('hand', $player_id);
        $lie = true;
        foreach ($hand as $card_id => $card) {
            if ($card_type == $card['type']) {
                $lie = false;
                break;
            }
        }
        self::incStat(1, $lie ? 'lie' : 'truth', $player_id);
        $this->updateHonesty($player_id);

        $this->doBalloon('balloonInstant', clienttranslate('${player_name} claims the ${card_name} to block ${player_name2}...'), array(
            'player_id' => $player_id,
            'player_name' => $this->getName($player_id),
            'player_name2' => $this->getName($playerTurn),
            'card_name' => $this->characters[$card_type]['name'],
            'balloon' => $this->balloons['block']
        ));
        self::incStat(1, 'blockIssued', $player_id);
        self::incStat(1, 'blockReceived', $playerTurn);

        $this->gamestate->nextState('askBlock');
    }

    public function actionChooseCard($card_id)
    {
        self::checkAction('actionChooseCard');
        $reasonNum = self::getGameStateValue('reasonChoose');

        if ($reasonNum == 1) { // reveal for challenge/block
            $character = self::getGameStateValue('typeBlock');
            if ($character == 0) { // challenge
                self::setGameStateValue('cardReveal', $card_id);
                $this->gamestate->nextState('challenge');
            } else { // block
                self::setGameStateValue('cardReveal', $card_id);
                $this->gamestate->nextState('challengeBlock');
            }
        } elseif ($reasonNum == 2) { // lost challenge
            self::setGameStateValue('cardKill', $card_id);
            $this->gamestate->nextState('killLoss');
        } elseif ($reasonNum == 3) { // killed
            self::setGameStateValue('cardCoup', $card_id);
            $this->gamestate->nextState('killCoup');
        }
    }

    public function actionDiscard($card_ids)
    {
        self::checkAction('actionDiscard');
        $player_id = self::getActivePlayerId();

        // Ensure the correct number of cards are selected
        $requiredCount = 2;
        if (count($card_ids) != $requiredCount) {
            throw new BgaUserException(self::_('You must choose 2 active cards to discard.'));
        }
        foreach ($card_ids as $card_id) {
            $card = $this->getCard($card_id, 'hand', $player_id);
        }

        $this->cards->moveCards($card_ids, 'deck');
        $this->cards->shuffle('deck');
        $deckCount = $this->cards->countCardInLocation('deck');
        self::notifyPlayer($player_id, 'discardInstant', '', array(
            'player_id' => $player_id,
            'card_ids' => $card_ids
        ));
        $this->doBalloon('discard', clienttranslate('${player_name} discards ${count} cards and shuffles the deck.'), array(
            'player_id' => $player_id,
            'player_name' => $this->getName($player_id),
            'count' => 2,
            'deck_count' => $deckCount,
            'balloon' => $this->balloons['discard'],
            'action' => 6
        ));
        $this->gamestate->nextState('killLoss');
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    public function argAsk()
    {
        $output = array();
        $playerBlock = self::getGameStateValue('playerBlock');
        $playerTurn = self::getGameStateValue('playerTurn');
        if ($playerBlock > 0) {
            $player = $playerBlock;
            $type = self::getGameStateValue('typeBlock');
        } else {
            $player = $playerTurn;
            $action = self::getGameStateValue('action');
            $action_ref = $this->actions[$action];
            $type = $action_ref['character'];
            if (count($action_ref['blockers']) > 0) {
                if ($action_ref['name'] == 'Foreign Aid') {
                    // Anyone can block Foreign Aid
                    $output['blockers'] = $action_ref['blockers'];
                } else {
                    // Only target can block Steal/Assassinate
                    $target = self::getGameStateValue('playerTarget');
                    $output['_private'] = array($target => array('blockers' => $action_ref['blockers']));
                }
            }
            $output['action'] = $action_ref['name'];
        }

        $output['player_name2'] = $this->getName($player);
        if ($type > 0) {
            $output['card_name'] = $this->characters[$type]['name'];
        }
        return $output;
    }

    public function argChooseCard()
    {
        $reasonNum = self::getGameStateValue('reasonChoose');
        $args = array(
            'reason' => $this->reasonText[$reasonNum]
        );
        if ($reasonNum == 1) { // reveal for challenge/block
            $character = self::getGameStateValue('typeBlock');
            if ($character == 0) {
                $action = self::getGameStateValue('action');
                $action_ref = $this->actions[$action];
                $character = $action_ref['character'];
            }
            $args['reason'] = str_replace('${card_name}', $this->characters[$character]['name'], $args['reason']);
        } elseif ($reasonNum == 2) { // lost challenge
            // nothing
        } elseif ($reasonNum == 3) { // killed
            $playerTurn = self::getGameStateValue('playerTurn');
            $args['reason'] = str_replace('${player_name2}', $this->getName($playerTurn), $args['reason']);
        }
        return $args;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    public function stRoundBegin()
    {
        // Increment round counter
        $requiredScore = self::getGameStateValue('requiredScore');
        $round = self::getGameStateValue('round') + 1;
        self::setGameStateValue('round', $round);

        // Reset active players
        self::DbQuery('UPDATE player SET player_wealth = 0, round_eliminated = 0, balloon = NULL WHERE player_eliminated = 0');
        $players = $this->getPlayerIds();
        $playerCount = count($players);

        // Shuffle cards
        $this->cards->moveAllCardsInLocation(null, 'deck');
        $this->cards->shuffle('deck');

        // Give money and cards to active players
        $wealth = $playerCount == 2 ? 1 : 2;
        self::DbQuery("UPDATE player SET player_wealth = $wealth WHERE player_eliminated = 0");
        foreach ($players as $player_id) {
            $this->cards->pickCards(2, 'deck', $player_id);
            self::incStat($wealth, 'wealthIn', $player_id);
        }

        // Send data to all players
        $public = $this->getPublicData();
        foreach ($public['players'] as $player_id => $player) {
            $private = $this->getPrivateData($player_id);
            self::notifyPlayer($player_id, 'roundBegin', '', $public + $private);
        }
        if ($requiredScore > 1) {
            self::notifyAllPlayers('message', clienttranslate('Begin round ${round}. The game ends when someone wins ${count} rounds.'), array(
                'round' => $round,
                'count' => $requiredScore
            ));
        }
        $this->gamestate->nextState('');
    }

    public function stRoundEnd()
    {
        $players = self::getCollectionFromDb('SELECT player_id, player_name, player_score, player_eliminated FROM player ORDER BY player_no');
        $active = false;
        $scores = array();
        foreach ($players as $player_id => $player) {
            $scores[$player_id] = $player['player_score'];
            if ($player['player_eliminated'] == 0) {
                $active = true;
            }
        }

        // Send current scores
        self::notifyAllPlayers('scores', '', array( 'scores' => $scores ));

        // Game over?
        $requiredScore = self::getGameStateValue('requiredScore');
        $gameOver = !$active || max(array_values($scores)) >= $requiredScore;
        foreach ($scores as $player_id => $score) {
            if ($score >= $requiredScore) {
                $gameOver = true;
                break;
            }
        }

        // Display score table?
        if ($requiredScore > 1) {
            $tableWindow = array(
                'id' => 'finalScoring',
                'title' => $gameOver ? clienttranslate('End of game') : clienttranslate('End of round')
            );
            $headerRow = array();
            $scoreRow = array();
            foreach ($players as $player_id => $player) {
                $headerRow[] = array(
                    'str' => '${player_name}',
                    'args' => array('player_name' => $player['player_name']),
                    'type' => 'header'
                );
                $scoreRow[] = $scores[$player_id];
            }
            $tableWindow['table'] = array($headerRow, $scoreRow);
            $this->notifyAllPlayers('tableWindow', '', $tableWindow);
        }

        // Go to next state
        $this->gamestate->nextState($gameOver ? 'gameEnd' : 'roundBegin');
    }

    public function stPlayerStart()
    {
        // Turns are chaos, so save the current player
        $playerTurn = self::getActivePlayerId();
        self::setGameStateValue('playerTurn', $playerTurn);
        self::incStat(1, 'turns', $playerTurn);

        // Add a bit of thinking time
        self::giveExtraTime($playerTurn);

        // Clear all other saved state values
        self::setGameStateValue('action', 0);
        self::setGameStateValue('reasonChoose', 0);
        self::setGameStateValue('playerChallenge', 0);
        self::setGameStateValue('playerBlock', 0);
        self::setGameStateValue('playerKill', 0);
        self::setGameStateValue('playerTarget', 0);
        self::setGameStateValue('cardReplace', 0);
        self::setGameStateValue('cardReveal', 0);
        self::setGameStateValue('cardKill', 0);
        self::setGameStateValue('cardCoup', 0);
        self::setGameStateValue('typeBlock', 0);
    }

    public function stPlayerEnd()
    {
        // Check for winner, or continue
        if (!$this->checkWin()) {
            // We can't trust the active player
            // Use saved state value to determine next player
            $players = $this->getPlayerIds();
            $playerTurn = self::getGameStateValue('playerTurn');
            $nextPlayer = $playerTurn;
            do {
                $nextPlayer = self::getPlayerAfter($nextPlayer);
            } while (!in_array($nextPlayer, $players));
            $this->gamestate->changeActivePlayer($nextPlayer);
            $this->gamestate->nextState('playerStart');
        }
    }

    public function stAsk()
    {
        $skip = self::getGameStateValue('playerBlock');
        if ($skip == 0) {
            $skip = self::getGameStateValue('playerTurn');
        }
        // Activate other players
        self::DbQuery("UPDATE player SET player_is_multiactive = 0");
        $players = $this->getPlayerIds($skip);
        $this->gamestate->setPlayersMultiactive($players, 'execute');
    }

    public function stChallenge()
    {
        $playerTurn = self::getGameStateValue('playerTurn');
        $cardReveal = self::getGameStateValue('cardReveal');

        if ($cardReveal == 0) {
            // Can we auto-select?
            $hand = $this->getCardIds('hand', $playerTurn);
            if (count($hand) == 1) {
                $cardReveal = array_shift($hand);
            }
        }

        if ($cardReveal > 0) {
            $playerChallenge = self::getGameStateValue('playerChallenge');
            $action = self::getGameStateValue('action');
            $action_ref = $this->actions[$action];
            $card = $this->getCard($cardReveal, 'hand', $playerTurn);
            if ($card['type'] == $action_ref['character']) { // truth, action occurs
                $this->cards->moveCard($cardReveal, 'tableau', $playerTurn);
                $this->doBalloon('reveal', clienttranslate('${player_name} reveals the ${card_name} as claimed.'), array(
                    'player_id' => $playerTurn,
                    'player_name' => $this->getName($playerTurn),
                    'player_name2' => $this->getName($playerChallenge),
                    'card_name' => $card['name'],
                    'card_ids' => array($card['id']),
                    'card_types' => array($card['type']),
                    'balloon' => $this->balloons['truth']
                ));
                self::incStat(1, 'challengeLoss', $playerChallenge);

                // Turn player must replace card
                self::setGameStateValue('cardReplace', $cardReveal);

                // Challenger must kill a card
                self::setGameStateValue('playerKill', $playerChallenge);
                self::setGameStateValue('cardKill', 0);

                $this->gamestate->nextState('execute');
            } else { // lie, action cancelled
                $this->doBalloon('balloon', clienttranslate('${player_name} reveals the ${card_name} and was bluffing! The ${action} does not occur.'), array(
                    'player_id' => $playerTurn,
                    'player_name' => $this->getName($playerTurn),
                    'player_name2' => $this->getName($playerChallenge),
                    'card_name' => $card['name'],
                    'action' => $action_ref['name'],
                    'balloon' => $this->balloons['lie']
                ));
                self::incStat(1, 'challengeWin', $playerChallenge);

                // Turn player must kill revealed card
                self::setGameStateValue('playerKill', $playerTurn);
                self::setGameStateValue('cardKill', $cardReveal);

                // Skip replace and action, go to kill
                $this->gamestate->nextState('killLoss');
            }
        } else { // turn reveal prompt
            self::setGameStateValue('reasonChoose', 1);
            $this->gamestate->changeActivePlayer($playerTurn);
            $this->gamestate->nextState('askChooseCard');
        }
    }

    public function stChallengeBlock()
    {
        $playerBlock = self::getGameStateValue('playerBlock');
        $cardReveal = self::getGameStateValue('cardReveal');

        if ($cardReveal == 0) {
            // Can we auto-select?
            $hand = $this->getCardIds('hand', $playerBlock);
            if (count($hand) == 1) {
                $cardReveal = array_shift($hand);
            }
        }

        if ($cardReveal > 0) {
            $playerChallenge = self::getGameStateValue('playerChallenge');
            $typeBlock = self::getGameStateValue('typeBlock');
            $card = $this->getCard($cardReveal, 'hand', $playerBlock);
            if ($card['type'] == $typeBlock) { // truth, action blocked
                $this->cards->moveCard($cardReveal, 'tableau', $playerBlock);
                $this->doBalloon('reveal', clienttranslate('${player_name} reveals the ${card_name} as claimed.'), array(
                    'player_id' => $playerBlock,
                    'player_name' => $this->getName($playerBlock),
                    'player_name2' => $this->getName($playerChallenge),
                    'card_name' => $card['name'],
                    'card_ids' => array($card['id']),
                    'card_types' => array($card['type']),
                    'balloon' => $this->balloons['truth']
                ));
                self::incStat(1, 'challengeLoss', $playerChallenge);

                // Blocker must replace card
                self::setGameStateValue('cardReplace', $cardReveal);

                // Challenger must kill a card
                self::setGameStateValue('playerKill', $playerChallenge);
                self::setGameStateValue('cardKill', 0);
            } else { // lie, action occurs
                $this->doBalloon('balloon', clienttranslate('${player_name} reveals the ${card_name} and was bluffing!'), array(
                    'player_id' => $playerBlock,
                    'player_name' => $this->getName($playerBlock),
                    'player_name2' => $this->getName($playerChallenge),
                    'card_name' => $card['name'],
                    'balloon' => $this->balloons['lie']
                ));
                self::incStat(1, 'challengeWin', $playerChallenge);

                // Action still occurs
                self::setGameStateValue('playerBlock', 0);

                // Blocker must kill revealed card
                self::setGameStateValue('playerKill', $playerBlock);
                self::setGameStateValue('cardKill', $cardReveal);
            }
            $this->gamestate->nextState('execute');
        } else { // block reveal prompt
            self::setGameStateValue('reasonChoose', 1);
            $this->gamestate->changeActivePlayer($playerBlock);
            $this->gamestate->nextState('askChooseCard');
        }
    }

    public function stExecute()
    {
        $action = self::getGameStateValue('action');
        $action_ref = $this->actions[$action];
        $playerTurn = self::getGameStateValue('playerTurn');
        $playerBlock = self::getGameStateValue('playerBlock');

        // First replace any revealed card
        $cardReplace = self::getGameStateValue('cardReplace');
        if ($cardReplace > 0) {
            $player = $playerBlock ? $playerBlock : $playerTurn;
            $oldCard = $this->getCard($cardReplace, 'tableau', $player);
            $this->cards->moveCard($cardReplace, 'deck');
            $this->cards->shuffle('deck');
            $newCard = $this->cards->pickCard('deck', $player);
            $deckCount = $this->cards->countCardInLocation('deck');
            self::notifyAllPlayers('discard', clienttranslate('${player_name} discards the ${card_name}, shuffles the deck, and draws a replacement.'), array(
                'player_id' => $player,
                'player_name' => $this->getName($player),
                'card_name' => $oldCard['name'],
                'card_ids' => array($oldCard['id']),
                'deck_count' => $deckCount + 1
            ));
            self::notifyPlayer($player, 'drawInstant', '', array(
                'player_id' => $player,
                'cards' => array($newCard)
            ));
            self::notifyAllPlayers('draw', '', array(
                'player_id' => $player,
                'count' => 1,
                'deck_count' => $deckCount
            ));
        }

        // If not blocked, action occurs
        // Determine how to log it
        $transition = 'killLoss';
        $target = self::getGameStateValue('playerTarget');
        $logAs = 'wealth';
        $args = array(
            'action' => $action,
            'player_id' => $playerTurn,
            'player_name' => $this->getName($playerTurn)
        );

        if ($playerBlock == 0) { // not blocked
            switch ($action_ref['name']) {
            case 'Income':
                $args['balloon'] = $this->balloons['wealth'];
                $args['amount'] = 1;
                $args['wealth'] = $this->addWealth($playerTurn, 1);
                self::incStat(1, 'wealthIn', $playerTurn);
                break;

            case 'Foreign Aid':
                $args['balloon'] = $this->balloons['wealth'];
                $args['amount'] = 2;
                $args['wealth'] = $this->addWealth($playerTurn, 2);
                self::incStat(2, 'wealthIn', $playerTurn);
                break;

            case 'Coup':
                $args['balloon'] = $this->balloons['kill'];
                $args['wealth'] = $this->addWealth($playerTurn, -7);
                self::incStat(7, 'wealthOut', $playerTurn);
                $transition = 'killCoup';
                break;

            case 'Tax':
                $args['balloon'] = $this->balloons['wealth'];
                $args['amount'] = 3;
                $args['wealth'] = $this->addWealth($playerTurn, 3);
                self::incStat(3, 'wealthIn', $playerTurn);
                break;

            case 'Assassinate':
                $args['balloon'] = $this->balloons['kill'];
                $args['wealth'] = $this->addWealth($playerTurn, -3);
                self::incStat(3, 'wealthOut', $playerTurn);
                $transition = 'killCoup';
                break;

            case 'Exchange':
                $args['balloon'] = $this->balloons['draw'];
                $args['count'] = 2;
                $newCards = $this->cards->pickCards($args['count'], 'deck', $playerTurn);
                $args['deck_count'] = $this->cards->countCardInLocation('deck');
                self::notifyPlayer($playerTurn, 'drawInstant', '', array(
                    'player_id' => $playerTurn,
                    'cards' => $newCards
                ));
                $transition = 'askDiscard';
                $logAs = 'draw';
                break;

            case 'Steal':
                $args['balloon'] = $this->balloons['wealth'];
                $args['amount'] = min(2, $this->getWealth($target));
                $args['wealth'] = $this->addWealth($playerTurn, $args['amount']);
                $target_wealth = $this->addWealth($target, $args['amount'] * -1);
                self::notifyAllPlayers('wealthInstant', '', array(
                    'player_id' => $target,
                    'wealth' => $target_wealth
                ));
                self::incStat($args['amount'], 'wealthIn', $playerTurn);
                break;
            }
            if ($target) {
                $args['player_name2'] = $this->getName($target);
                if ($action_ref['name'] == 'Steal') {
                    // Only Steal action is a wealth transfer
                    $args['player_id2'] = $target;
                }
            }
            if ($action_ref['logExecute']) {
                //self::notifyAllPlayers($logAs, $action_ref['logExecute'], $args);
                $this->doBalloon($logAs, $action_ref['logExecute'], $args);
            }
            self::incStat(1, 'action' . $action, $playerTurn);
        } else { // blocked
            $this->doBalloon('balloonInstant', clienttranslate('${player_name}\'s ${action} does not occur.'), array(
                'player_id' => $playerTurn,
                'player_name' => $this->getName($playerTurn),
                'action' => $action_ref['name'],
                'balloon' => ''
            ));
            if ($action_ref['name'] == 'Assassinate') {
                // Still must pay if blocked
                $wealth = $this->addWealth($playerTurn, -3);
                self::notifyAllPlayers('wealth', '', array(
                    'player_id' => $playerTurn,
                    'wealth' => $wealth
                ));
                self::incStat(3, 'wealthOut', $playerTurn);
            }
        }

        $this->gamestate->nextState($transition);
    }

    public function stKillCoup()
    {
        $playerTarget = self::getGameStateValue('playerTarget');
        if ($playerTarget > 0) {
            $playerKill = self::getGameStateValue('playerKill');
            $cardCoup = self::getGameStateValue('cardCoup');
            $cardCoup2 = 0;
            $reason = 3;
            $hand = $this->getCardIds('hand', $playerTarget);
            $doubleElimination = count($hand) == 2 && $playerTarget == $playerKill;
            if ($doubleElimination || ($cardCoup == 0 && count($hand) == 1)) {
                // Can we auto-select or double eliminate?
                $cardCoup = array_shift($hand);
                if ($doubleElimination) {
                    $cardCoup2 = array_shift($hand);
                    $reason = 4;
                }
            }

            if ($cardCoup > 0) { // kill
                $playerTurn = self::getGameStateValue('playerTurn');
                $eliminated = $this->doKill($playerTarget, $playerTurn, $reason, $cardCoup, $cardCoup2);
                if ($eliminated) {
                    // Can't kill this player again if eliminated
                    if ($playerTarget == $playerKill) {
                        self::setGameStateValue('playerKill', 0);
                    }
                }

                // Check for a winner, or continue
                if (!$this->checkWin()) {
                    $this->gamestate->nextState('killLoss');
                }
            } else { // prompt
                $playerTurn = self::getGameStateValue('playerTurn');
                self::setGameStateValue('reasonChoose', 3);
                self::notifyAllPlayers('message', clienttranslate('${player_name} must choose a card') . ' (' . $this->reasonText[$reason] . ').', array(
                    'player_name' => $this->getName($playerTarget),
                    'player_name2' => $this->getName($playerTurn),
                ));
                $this->gamestate->changeActivePlayer($playerTarget);
                $this->gamestate->nextState('askChooseCard');
            }
        } else { // continue
            $this->gamestate->nextState('killLoss');
        }
    }

    public function stKillLoss()
    {
        $playerKill = self::getGameStateValue('playerKill');
        if ($playerKill > 0) {
            $cardKill = self::getGameStateValue('cardKill');
            $reason = 2;
            if ($cardKill == 0) {
                // Can we auto-select?
                $hand = $this->getCardIds('hand', $playerKill);
                if (count($hand) == 1) {
                    $cardKill = array_shift($hand);
                }
            }

            if ($cardKill > 0) { // kill
                $this->doKill($playerKill, 0, $reason, $cardKill);
                $this->gamestate->nextState('playerEnd');
            } else { // prompt
                self::setGameStateValue('reasonChoose', 2);
                self::notifyAllPlayers('message', clienttranslate('${player_name} must choose a card') . ' (' . $this->reasonText[$reason] . ').', array(
                    'player_name' => $this->getName($playerKill)
                ));
                $this->gamestate->changeActivePlayer($playerKill);
                $this->gamestate->nextState('askChooseCard');
            }
        } else { // continue
            $this->gamestate->nextState('playerEnd');
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    public function zombieTurn($state, $active_player)
    {
        if (array_key_exists('zombiePass', $state['transitions'])) {
            if ($state['type'] == 'multipleactiveplayer') {
                $this->gamestate->setPlayerNonMultiactive($active_player, 'zombiePass');
            } else {
                $this->gamestate->nextState('zombiePass');
            }
        } else {
            // Zombie always choose the first card(s)
            $hand = $this->getCardIds('hand', $active_player);
            $first = array_shift($hand);
            if ($state['name'] == 'askChooseCard') {
                $this->actionChooseCard($first['id']);
            } elseif ($state['name'] == 'askDiscard') {
                $second = array_shift($hand);
                $this->actionDiscard(array($first['id'], $second['id']));
            } else {
                throw new BgaVisibleSystemException('Zombie player ' . $active_player . ' stuck in unexpected state ' . $state['name']);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    public function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        if ($from_version <= 1709251328) {
            self::DbQuery('ALTER TABLE `player` ADD `balloon` MEDIUMTEXT');
        }
        if ($from_version <= 1712281820) {
            self::DbQuery('ALTER TABLE `player` ADD `round_eliminated` INT NOT NULL DEFAULT 0');
        }
    }
}
