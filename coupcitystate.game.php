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

// Character constants
define('DUKE', 1);
define('ASSASSIN', 2);
define('AMBASSADOR', 3);
define('CAPTAIN', 4);
define('CONTESSA', 5);
define('INQUISITOR', 6);

// Action constants
define('INCOME', 1);
define('FOREIGN_AID', 2);
define('COUP', 3);
define('TAX', 4);
define('ASSASSINATE', 5);
define('EXCHANGE', 6);
define('STEAL', 7);
define('CONVERT', 8);
define('EMBEZZLE', 9);
define('EXCHANGE1', 10);
define('EXAMINE', 11);

// Reason constants
define('REASON_CHALLENGE', 1);
define('REASON_LOSS', 2);
define('REASON_KILL', 3);
define('REASON_EXAMINE', 4);

// State constants
define('ST_GAME_BEGIN', 1);
define('ST_ROUND_BEGIN', 2);
define('ST_PLAYER_BEGIN', 3);
define('ST_ACT', 10);
define('ST_ASK_BLOCK', 11);
define('ST_ASK_CHOOSE_CARD', 12);
define('ST_ASK_DISCARD', 13);
define('ST_ASK_EXAMINE', 14);
define('ST_CHALLENGE', 15);
define('ST_CHALLENGE_ALL', 16);
define('ST_CHALLENGE_BLOCK', 17);
define('ST_DISCARD', 18);
define('ST_EXECUTE', 19);
define('ST_KILL_COUP', 20);
define('ST_KILL_LOSS', 21);
define('ST_PLAYER_END', 97);
define('ST_ROUND_END', 98);
define('ST_GAME_END', 99);

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
            'almshouse' => 13,
            'skipExecute' => 14,
            'playerTurn' => 20,
            'playerChallenge' => 21,
            'playerBlock' => 22,
            'playerKill' => 23,
            'playerTarget' => 24,
            'cardReveal' => 31,
            'cardKill' => 32,
            'cardCoup' => 33,
            'cardExamine' => 34,
            'typeBlock' => 40,
            'variantFactions' => 101,
            'variantInquisitor' => 102,
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
        self::initStat('player', 'action8', 0);
        self::initStat('player', 'action9', 0);
        self::initStat('player', 'action11', 0);
        self::initStat('player', 'blockIssued', 0);
        self::initStat('player', 'blockReceived', 0);
        self::initStat('player', 'challengeIssued', 0);
        self::initStat('player', 'challengeReceived', 0);
        self::initStat('player', 'challengeWin', 0);
        self::initStat('player', 'challengeLoss', 0);

        // Create 3 cards of each type
        $cards = array();
        foreach ($this->characters as $character => $character_ref) {
            if ($character == 0 || !$this->meetsVariant($character_ref['variant'])) {
                continue;
            }
            $cards[] = array('type' => "$character", 'type_arg' => 0, 'nbr' => 3);
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
            'characters' => $this->characters,
            'factions' => $this->factions
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
        $tableauCount = $this->cards->countCardInLocation('tableau');
        $handCount = $this->cards->countCardInLocation('hand');
        $cardCount = $tableauCount + $handCount - 1;
        $cardValue = $cardCount <= 0 ? 0 : 100 / $cardCount;
        $coinValue = $cardValue / 10;
        $coins = min(9, self::getUniqueValueFromDB('SELECT MAX(player_wealth) FROM player'));
        $progress = min(100, $cardValue * $tableauCount + $coinValue * $coins);
        return floor(min(100, $progress));
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    public function getPlayers($cards=false)
    {
        $players = self::getCollectionFromDb('SELECT player_id id, player_name, player_color, player_score score, player_wealth wealth, player_zombie zombie, player_eliminated eliminated, faction, balloon FROM player ORDER BY player_no');
        foreach ($players as $player_id => $player) {
            $players[$player_id]['score'] = intval($players[$player_id]['score']);
            $players[$player_id]['wealth'] = intval($players[$player_id]['wealth']);
            $players[$player_id]['zombie'] = intval($players[$player_id]['zombie']);
            $players[$player_id]['eliminated'] = intval($players[$player_id]['eliminated']);
            $players[$player_id]['faction'] = intval($players[$player_id]['faction']);
            if ($player['balloon']) {
                $players[$player_id]['balloon'] = unserialize($player['balloon']);
            }
            if ($cards) {
                $players[$player_id]['handCount'] = intval($this->cards->countCardInLocation('hand', $player_id));
                $players[$player_id]['tableau'] = array_values($this->cards->getCardsInLocation('tableau', $player_id));
            }
        }
        return $players;
    }

    public function getPublicData()
    {
        $public = array(
            'players' => $this->getPlayers(true),
            'turn' => self::getGameStateValue('playerTurn'),
            'deckCount' => intval($this->cards->countCardInLocation('deck')),
            'variantFactions' => intval(self::getGameStateValue('variantFactions'))
        );
        if ($public['variantFactions']) {
            $public['almshouse'] = intval(self::getGameStateValue('almshouse'));
        }
        return $public;
    }

    public function getPrivateData($player_id)
    {
        $players = self::loadPlayersBasicInfos();
        $spectator = !array_key_exists($player_id, $players);
        $result = array();
        if (!$spectator) {
            $result['hand'] = array_values($this->cards->getCardsInLocation('hand', $player_id));
        }
        return $result;
    }

    public function meetsVariant($conditions)
    {
        if (is_array($conditions)) {
            foreach ($conditions as $key => $value) {
                if (self::getGameStateValue($key) != $value) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getName($player_id)
    {
        return self::getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id=$player_id");
    }

    public function getActivePlayerIds($skipPlayerId=null, $skipFaction=null)
    {
        $sql = 'SELECT player_id FROM player WHERE player_eliminated = 0 AND player_zombie = 0';
        if ($skipPlayerId != null) {
            $sql .= " AND player_id != $skipPlayerId";
        }
        if ($skipFaction != null) {
            $sql .= " AND faction != $skipFaction";
        }
        $sql .= ' ORDER BY player_no';
        return self::getObjectListFromDB($sql, true);
    }

    public function getPlayerFactions()
    {
        return self::getCollectionFromDb('SELECT player_id, faction FROM player WHERE player_eliminated = 0 AND player_zombie = 0', true);
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

        $card['name']= $this->characters[$card['type']]['name'];
        return $card;
    }

    public function getCardIds($location, $location_arg)
    {
        // Get multiple cards as ID list
        $cards = array_values($this->cards->getCardsInLocation($location, $location_arg));
        return array_column($cards, 'id');
    }

    public function getWealth($player_id)
    {
        return self::getUniqueValueFromDB("SELECT player_wealth FROM player WHERE player_id=$player_id");
    }

    public function addWealth($player_id, $amount)
    {
        self::DbQuery("UPDATE player SET player_wealth = GREATEST(0, player_wealth + $amount) WHERE player_id=$player_id");
        return $this->getWealth($player_id);
    }

    public function updateHonesty($player_id)
    {
        $lies = self::getStat('lie', $player_id);
        $truths = self::getStat('truth', $player_id);
        self::setStat(round($truths / ($lies + $truths) * 100), 'honesty', $player_id);
    }

    public function toggleFaction($player_id)
    {
        $faction = self::getUniqueValueFromDB("SELECT faction FROM player WHERE player_id=$player_id");
        $faction = $faction == 1 ? 2 : 1;
        self::DbQuery("UPDATE player SET faction = $faction WHERE player_id=$player_id");
        return $faction;
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

    public function doReplace($player_id, $card_ids)
    {
        $count = count($card_ids);
        $oldCards = $this->cards->getCards($card_ids);
        $this->cards->moveCards($card_ids, 'deck');
        $this->cards->shuffle('deck');
        $newCards = $this->cards->pickCards($count, 'deck', $player_id);
        $deckCount = $this->cards->countCardInLocation('deck');
        $args = array(
            'i18n' => array('card_name'),
            'player_id' => $player_id,
            'player_name' => $this->getName($player_id),
            'card_name' => $this->characters[array_shift($oldCards)['type']]['name'],
            'card_ids' => $card_ids,
            'deck_count' => $deckCount + $count
        );
        if ($count == 2) {
            $args['i18n'][] = 'card_name2';
            $args['card_name2'] = $this->characters[array_shift($oldCards)['type']]['name'];
            self::notifyAllPlayers('discard', clienttranslate('${player_name} discards the ${card_name} and the ${card_name2}, shuffles the deck, and draws replacements.'), $args);
        } else {
            self::notifyAllPlayers('discard', clienttranslate('${player_name} discards the ${card_name}, shuffles the deck, and draws a replacement.'), $args);
        }
        self::notifyPlayer($player_id, 'drawInstant', '', array(
            'player_id' => $player_id,
            'cards' => $newCards
        ));
        self::notifyAllPlayers('draw', '', array(
            'player_id' => $player_id,
            'count' => $count,
            'deck_count' => $deckCount
        ));
    }

    public function doAutoChoose($player_id)
    {
        // Auto-select card from hand if only one
        $hand = $this->getCardIds('hand', $player_id);
        $count = count($hand);
        if ($count > 1) {
            return 0; // multiple cards
        } elseif ($count == 1) {
            return array_shift($hand);
        } else {
            return -1; // eliminated
        }
    }

    public function doKill($player_id, $reason, $card_id)
    {
        $card = $this->getCard($card_id);
        $this->cards->moveCard($card_id, 'tableau', $player_id);
        $handCount = $this->cards->countCardInLocation('hand', $player_id);
        if ($handCount == 0) {
            $msg = clienttranslate('${player_name} loses the ${card_name} and is eliminated!');
        } else {
            $msg = clienttranslate('${player_name} loses the ${card_name}.');
        }
        $this->doBalloon('reveal', $msg, array(
            'i18n' => array('card_name'),
            'player_id' => $player_id,
            'player_name' => $this->getName($player_id),
            'card_name' => $card['name'],
            'cards' => array($card),
            'balloon' => $this->balloons['die']
        ));

        // Check for elimination
        if ($handCount == 0) {
            $this->eliminate($player_id);
            return true;
        }
        return false;
    }

    public function eliminate($player_id, $forever=false)
    {
        // Erase money
        self::DbQuery("UPDATE player SET player_wealth = 0 WHERE player_id = $player_id");
        self::notifyAllPlayers('wealthInstant', '', array(
            'player_id' => $player_id,
            'wealth' => 0
        ));

        // Reveal cards in hand
        $hand = array_values($this->cards->getCardsInLocation('hand', $player_id));
        if (count($hand) > 0) {
            $card_ids = array_column($hand, 'id');
            $this->cards->moveCards($card_ids, 'tableau', $player_id);
            self::notifyAllPlayers('revealInstant', '', array(
              'player_id' => $player_id,
              'cards' => $hand
          ));
        }

        // Eliminate
        self::notifyAllPlayers('eliminate', '', array(
            'player_id' => $player_id
        ));
        self::eliminatePlayer($player_id);
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
        $players = $this->getActivePlayerIds();
        if (count($players) == 1) { // win
            self::DbQuery("UPDATE player SET player_score = 1 WHERE player_id = $players[0]");
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

        // Check variant
        if (!$this->meetsVariant($action_ref['variant'])) {
            throw new BgaVisibleSystemException("Invalid move. Action ($action) not allowed in this game variant.");
        }

        // Check wealth
        $wealth = $this->getWealth($player_id);
        $cost = $action_ref['cost'];
        if ($action == CONVERT) {
            $cost = $target == $player_id ? 1 : 2;
        }
        if ($action != COUP && $wealth >= 10) {
            throw new BgaUserException(self::_('Invalid move. You must Coup because you have ₤10.'));
        } elseif ($wealth < $cost) {
            throw new BgaUserException(sprintf(self::_('Invalid move. You need ₤%d for this action.'), $cost));
        } elseif ($action == EMBEZZLE && self::getGameStateValue('almshouse') < 1) {
            throw new BgaUserException(self::_('Invalid move. Almshouse has no money.'));
        }

        // Check target
        if ($action_ref['target']) {
            if ($target == 0 || $this->cards->countCardInLocation('hand', $target) == 0) {
                throw new BgaUserException(self::_('Invalid move. No target player selected.'));
            }
            if ($action != CONVERT && $target == $player_id) {
                throw new BgaUserException(self::_('Invalid move. Cannot target yourself for this action.'));
            }
            if ($action == STEAL && $this->getWealth($target) == 0) {
                throw new BgaUserException(self::_('Invalid move. Target player has no money.'));
            }

            $factions = $this->getPlayerFactions();
            $factionCount = count(array_unique(array_values($factions)));
            if ($action != CONVERT && $factionCount > 1 && $factions[$player_id] == $factions[$target]) {
                throw new BgaUserException(self::_('Invalid move. Target player must belong to opposing faction.'));
            }
            self::setGameStateValue('playerTarget', $target);
        }
        self::setGameStateValue('action', $action);

        // Is this a lie? (only for statistics, we'll recompute later)
        if ($action_ref['character'] > 0) {
            $hand = array_values($this->cards->getCardsInLocation('hand', $player_id));
            $lie = !in_array($action_ref['character'], array_column($hand, 'type'));
            self::incStat(1, $lie ? 'lie' : 'truth', $player_id);
            $this->updateHonesty($player_id);
        }

        // Ask other players?
        if ($action_ref['logAttempt']) {
            $args = array(
                'i18n' => array(),
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
                $args['i18n'][] = 'card_name';
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
        self::incStat(1, 'challengeIssued', $player_id);
        self::incStat(1, 'challengeReceived', $player);

        if ($action_ref['forbid'] > 0) {
            $this->doBalloon('balloon', clienttranslate('${player_name} challenges ${player_name2} to reveal all cards!'), array(
                'player_id' => $player_id,
                'player_name' => $this->getName($player_id),
                'player_name2' => $this->getName($player),
                'balloon' => $this->balloons['challengeAll']
            ));
            $this->gamestate->nextState('yesAll');
        } else {
            // Delay if target is auto-choosing
            $balloonType = $this->cards->countCardInLocation('hand', $player) == 1 ? 'balloon' : 'balloonInstant';
            $this->doBalloon($balloonType, clienttranslate('${player_name} challenges ${player_name2} to reveal the ${card_name}!'), array(
                'i18n' => array('card_name'),
                'player_id' => $player_id,
                'player_name' => $this->getName($player_id),
                'player_name2' => $this->getName($player),
                'card_name' => $this->characters[$type]['name'],
                'balloon' => $this->balloons['challenge']
            ));
            $this->gamestate->nextState('yes');
        }
    }

    public function actionBlock($card_type)
    {
        self::checkAction('actionBlock');
        $player_id = self::getCurrentPlayerId();
        $playerTurn = self::getGameStateValue('playerTurn');
        self::setGameStateValue('playerBlock', $player_id);
        self::setGameStateValue('typeBlock', $card_type);

        // Is this a lie? (only for statistics, we'll recompute later)
        $hand = array_values($this->cards->getCardsInLocation('hand', $player_id));
        $lie = !in_array($card_type, array_column($hand, 'type'));
        self::incStat(1, $lie ? 'lie' : 'truth', $player_id);
        $this->updateHonesty($player_id);

        $this->doBalloon('balloonInstant', clienttranslate('${player_name} claims the ${card_name} to block ${player_name2}...'), array(
            'i18n' => array('card_name'),
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
        $reason = self::getGameStateValue('reasonChoose');
        switch ($reason) {
        case REASON_CHALLENGE:
            self::setGameStateValue('cardReveal', $card_id);
            $character = self::getGameStateValue('typeBlock');
            if ($character == 0) { // challenge
                $this->gamestate->nextState('challenge');
            } else { // block
                $this->gamestate->nextState('challengeBlock');
            }
            break;

        case REASON_LOSS:
            self::setGameStateValue('cardKill', $card_id);
            $this->gamestate->nextState('killLoss');
            break;

        case REASON_KILL:
            self::setGameStateValue('cardCoup', $card_id);
            $this->gamestate->nextState('killCoup');
            break;

        case REASON_EXAMINE:
            self::setGameStateValue('cardExamine', $card_id);
            $playerTurn = self::getGameStateValue('playerTurn');
            $this->gamestate->nextState('execute');
            break;

        default:
            throw new BgaVisibleSystemException("Unknown reason ($reason).");
        }
    }

    public function actionDiscard($card_ids)
    {
        self::checkAction('actionDiscard');
        $player_id = self::getActivePlayerId();

        // Ensure the correct number of cards are selected
        $action = self::getGameStateValue('action');
        $action_ref = $this->actions[$action];
        if (count($card_ids) != $action_ref['count']) {
            throw new BgaUserException(sprintf(self::_('Invalid move. You must discard %d cards.'), $action_ref['count']));
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
            'count' => $action_ref['count'],
            'deck_count' => $deckCount,
            'balloon' => $this->balloons['discard'],
            'action' => $action,
        ));
        $this->gamestate->nextState('');
    }

    public function actionExamineKeep()
    {
        self::checkAction('actionExamineKeep');
        $player_id = self::getCurrentPlayerId();
        $playerTarget = self::getGameStateValue('playerTarget');
        $card = $this->getCard(self::getGameStateValue('cardExamine'));
        self::notifyPlayer($player_id, 'unrevealInstant', '', array(
            'player_id' => $playerTarget,
            'cards' => array($card)
        ));
        self::notifyAllPlayers('message', '${player_name} keeps the card.', array(
            'player_name' => $this->getName($playerTarget)
        ));
        $this->gamestate->nextState('');
    }

    public function actionExamineExchange()
    {
        self::checkAction('actionExamineExchange');
        $player_id = self::getCurrentPlayerId();
        $playerTarget = self::getGameStateValue('playerTarget');
        $cardExamine = self::getGameStateValue('cardExamine');

        // Target discards and redraws
        $this->cards->moveCard($cardExamine, 'deck');
        $this->cards->shuffle('deck');
        $newCard = $this->cards->pickCard('deck', $playerTarget);
        $deckCount = $this->cards->countCardInLocation('deck');

        // Target and turn player see visible discard
        // Others see unknown discard
        $idsVisible = array($player_id, intval($playerTarget));
        foreach ($idsVisible as $id) {
            self::notifyPlayer($id, 'discardInstant', '', array(
                'player_id' => $playerTarget,
                'card_ids' => array($cardExamine),
                'deck_count' => $deckCount + 1,
            ));
        }
        self::notifyAllPlayers('discardInstant', '', array(
            'player_id' => $playerTarget,
            'count' => 1,
            'deck_count' => $deckCount + 1,
            'ignored_by' => $idsVisible,
        ));
        $this->doBalloon('balloon', clienttranslate('${player_name} discards the card, shuffles the deck, and draws a replacement.'), array(
            'player_id' => $playerTarget,
            'player_name' => $this->getName($playerTarget),
            'balloon' => $this->balloons['replace'],
            'action' => EXAMINE,
        ));

        // Target sees visible draw
        // Everyone see mystery draw (target will ignore)
        self::notifyPlayer($playerTarget, 'drawInstant', '', array(
            'player_id' => $playerTarget,
            'cards' => array($newCard)
        ));
        self::notifyAllPlayers('draw', '', array(
            'player_id' => $playerTarget,
            'count' => 1,
            'deck_count' => $deckCount
        ));

        $this->gamestate->nextState('');
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
        $output = array('i18n' => array());
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
                if ($action == FOREIGN_AID) {
                    // Anyone can block Foreign Aid
                    $output['blockers'] = $action_ref['blockers'];
                } else {
                    // Only target can block others
                    $target = self::getGameStateValue('playerTarget');

                    // Only include characters in this game variant
                    $blockers = array();
                    foreach ($action_ref['blockers'] as $character) {
                        $character_ref = $this->characters[$character];
                        if ($this->meetsVariant($character_ref['variant'])) {
                            $blockers[] = $character;
                        }
                    }
                    $output['_private'] = array(
                        $target => array('blockers' => $blockers)
                    );
                }
            }
            if ($action_ref['forbid']) {
                $output['forbid'] = $action_ref['forbid'];
            }
            $output['i18n'][] = 'action_name';
            $output['action_name'] = $action_ref['name'];
        }

        $output['player_name2'] = $this->getName($player);
        if ($type > 0) {
            $output['i18n'][] = 'card_name';
            $output['card_name'] = $this->characters[$type]['name'];
        }
        return $output;
    }

    public function argChooseCard()
    {
        $args = array();
        $reason = self::getGameStateValue('reasonChoose');
        switch ($reason) {
        case REASON_CHALLENGE:
            $character = self::getGameStateValue('typeBlock');
            if ($character == 0) {
                $action = self::getGameStateValue('action');
                $action_ref = $this->actions[$action];
                $character = $action_ref['character'];
            }
            $args['reason'] = clienttranslate('Challenged');
            $args['detail'] = $this->characters[$character]['name'];
            break;

        case REASON_LOSS:
            $args['reason'] = clienttranslate('Lost challenge');
            $player_id = self::getGameStateValue('playerBlock');
            if ($player_id == 0) {
                $player_id = self::getGameStateValue('playerTurn');
            }
            $args['detail'] = $this->getName($player_id);
            break;

        case REASON_KILL:
            $args['reason'] = clienttranslate('Killed');
            $args['detail'] = $this->getName(self::getGameStateValue('playerTurn'));
            break;

        case REASON_EXAMINE:
            $args['reason'] = clienttranslate('Examined');
            $args['detail'] = $this->getName(self::getGameStateValue('playerTurn'));
            break;

        default:
            throw new BgaVisibleSystemException("Unknown reason ($reason).");
        }
        return $args;
    }

    public function argDiscard()
    {
        // Ambassador discards 2, Inquisitor discards 1
        $action = self::getGameStateValue('action');
        $action_ref = $this->actions[$action];
        return array('count' => $action_ref['count']);
    }

    public function argExamine()
    {
        $playerTurn = self::getGameStateValue('playerTurn');
        $playerTarget = self::getGameStateValue('playerTarget');
        $card = $this->getCard(self::getGameStateValue('cardExamine'));
        return array(
            'player_name2' => $this->getName($playerTarget),
            '_private' => array(
                $playerTurn => array(
                    'i18n' => array('card_name'),
                    'card_id' => $card['id'],
                    'card_name' => $card['name'],
                )
            )
        );
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
        // Reset almshouse
        self::setGameStateValue('almshouse', 0);

        // Reset active players
        self::DbQuery('UPDATE player SET player_wealth = 0, faction = 0, balloon = NULL WHERE player_eliminated = 0');
        $players = $this->getPlayers();
        $activePlayers = $this->getActivePlayerIds();
        shuffle($activePlayers);

        // Shuffle all cards
        $this->cards->moveAllCardsInLocation(null, 'deck');
        $this->cards->shuffle('deck');

        // Randomize factions, if using
        // Default is faction 1, with about half of players in faction 2
        $variantFactions = self::getGameStateValue('variantFactions');
        if ($variantFactions) {
            $altFactionPlayers = array_slice($activePlayers, 0, intdiv(count($activePlayers), 2));
        }

        // Give money, factions, cards to active players
        $wealth = count($activePlayers) == 2 ? 1 : 2;
        foreach ($activePlayers as $player_id) {
            if ($variantFactions) {
                $faction = in_array($player_id, $altFactionPlayers) ? 2 : 1;
            } else {
                $faction = 0;
            }
            self::DbQuery("UPDATE player SET player_wealth = $wealth, faction = $faction WHERE player_id = $player_id");
            $this->cards->pickCards(2, 'deck', $player_id);
        }

        // Send data to all players
        $public = $this->getPublicData();
        foreach ($public['players'] as $player_id => $player) {
            $private = $this->getPrivateData($player_id);
            self::notifyPlayer($player_id, 'roundBegin', '', $public + $private);
        }
        $this->gamestate->nextState('');
    }

    public function stRoundEnd()
    {
        $players = self::getCollectionFromDb('SELECT player_id, player_score FROM player ORDER BY player_no');
        $scores = array();
        foreach ($players as $player_id => $player) {
            $scores[$player_id] = $player['player_score'];
        }

        // Send current scores
        self::notifyAllPlayers('scores', '', array('scores' => $scores));

        // Game over
        $this->gamestate->nextState('gameEnd');
    }

    public function stPlayerBegin()
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
        self::setGameStateValue('cardReveal', 0);
        self::setGameStateValue('cardKill', 0);
        self::setGameStateValue('cardCoup', 0);
        self::setGameStateValue('cardExamine', 0);
        self::setGameStateValue('typeBlock', 0);
        self::setGameStateValue('skipExecute', 0);
    }

    public function stPlayerEnd()
    {
        // Check for winner, or continue
        if (!$this->checkWin()) {
            // We can't trust the active player
            // Use saved state value to determine next player
            $players = $this->getActivePlayerIds();
            $playerTurn = self::getGameStateValue('playerTurn');
            $nextPlayer = $playerTurn;
            do {
                $nextPlayer = self::getPlayerAfter($nextPlayer);
            } while (!in_array($nextPlayer, $players));
            $this->gamestate->changeActivePlayer($nextPlayer);
            $this->gamestate->nextState('playerBegin');
        }
    }

    public function stAsk()
    {
        $skipFaction = null;
        $skipPlayerId = self::getGameStateValue('playerBlock');
        if ($skipPlayerId == 0) {
            $skipPlayerId = self::getGameStateValue('playerTurn');

            // For Foreign Aid, only opposing faction can block
            $action = self::getGameStateValue('action');
            $action_ref = $this->actions[$action];
            if ($action == FOREIGN_AID) {
                $factions = $this->getPlayerFactions();
                if (count(array_unique(array_values($factions))) > 1) {
                    $skipFaction = $factions[$skipPlayerId];
                    foreach ($factions as $player_id => $faction) {
                        if ($player_id != $skipPlayerId && $factions[$player_id] == $skipFaction) {
                            $this->doBalloon('balloonInstant', '', array(
                                'player_id' => $player_id,
                                'balloon' => 'no'
                            ));
                        }
                    }
                }
            }
        }

        // Activate players
        self::DbQuery('UPDATE player SET player_is_multiactive = 0');
        $players = $this->getActivePlayerIds($skipPlayerId, $skipFaction);
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
            $args = array(
                'i18n' => array('card_name'),
                'player_id' => $playerTurn,
                'player_name' => $this->getName($playerTurn),
                'player_name2' => $this->getName($playerChallenge),
                'card_name' => $card['name'],
                'cards' => array($card),
                'alive' => true,
            );

            if ($card['type'] == $action_ref['character']) { // truth, action occurs
                $this->cards->moveCard($cardReveal, 'tableau', $playerTurn);
                $args['balloon'] = $this->balloons['truth'];
                $this->doBalloon('reveal', clienttranslate('${player_name} reveals the ${card_name} as claimed.'), $args);
                self::incStat(1, 'challengeLoss', $playerChallenge);

                // Turn player must replace card
                $this->doReplace($playerTurn, array($cardReveal));

                // Challenger must kill a card
                self::setGameStateValue('playerKill', $playerChallenge);
                self::setGameStateValue('cardKill', 0);
            } else { // lie, action cancelled
                $args['balloon'] = $this->balloons['lie'];
                $this->doBalloon('reveal', clienttranslate('${player_name} reveals the ${card_name} and was bluffing!'), $args);
                self::incStat(1, 'challengeWin', $playerChallenge);

                // Turn player must kill revealed card
                self::setGameStateValue('playerKill', $playerTurn);
                self::setGameStateValue('cardKill', $cardReveal);
                self::setGameStateValue('skipExecute', 1);
            }
            $this->gamestate->nextState('killLoss');
        } else { // turn reveal prompt
            self::setGameStateValue('reasonChoose', REASON_CHALLENGE);
            $this->gamestate->changeActivePlayer($playerTurn);
            $this->gamestate->nextState('askChooseCard');
        }
    }

    public function stChallengeAll()
    {
        $playerTurn = self::getGameStateValue('playerTurn');
        $playerChallenge = self::getGameStateValue('playerChallenge');
        $action = self::getGameStateValue('action');
        $action_ref = $this->actions[$action];

        $hand = array_values($this->cards->getCardsInLocation('hand', $playerTurn));
        $card_ids = array_column($hand, 'id');
        $card_name = $this->characters[$action_ref['forbid']]['name'];
        $index = array_search($action_ref['forbid'], array_column($hand, 'type'));
        $args = array(
            'i18n' => array('card_name'),
            'player_id' => $playerTurn,
            'player_name' => $this->getName($playerTurn),
            'player_name2' => $this->getName($playerChallenge),
            'card_name' => $card_name,
            'cards' => $hand,
            'alive' => true,
        );

        if ($index === false) { // truth, action occurs
            $this->cards->moveCards($card_ids, 'tableau', $playerTurn);
            $args['balloon'] = $this->balloons['truth'];
            $this->doBalloon('reveal', clienttranslate('${player_name} reveals no ${card_name} as claimed.'), $args);
            self::incStat(1, 'challengeLoss', $playerChallenge);

            // Turn player must replace both cards
            $this->doReplace($playerTurn, $card_ids);

            // Challenger must kill a card
            self::setGameStateValue('playerKill', $playerChallenge);
            self::setGameStateValue('cardKill', 0);
        } else { // lie, action cancelled
            $args['balloon'] = $this->balloons['lie'];
            $this->doBalloon('reveal', clienttranslate('${player_name} reveals the ${card_name} and was bluffing!'), $args);
            self::incStat(1, 'challengeWin', $playerChallenge);

            // Turn player must replace AND kill revealed cards
            if (count($card_ids) == 2) {
                $other_index = $index == 0 ? 1 : 0;
                $this->doReplace($playerTurn, array($card_ids[$other_index]));
            }
            self::setGameStateValue('playerKill', $playerTurn);
            self::setGameStateValue('cardKill', $card_ids[$index]);
            self::setGameStateValue('skipExecute', 1);
        }
        $this->gamestate->nextState('killLoss');
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
            $args = array(
                'i18n' => array('card_name'),
                'player_id' => $playerBlock,
                'player_name' => $this->getName($playerBlock),
                'player_name2' => $this->getName($playerChallenge),
                'card_name' => $card['name'],
                'cards' => array($card),
                'alive' => true,
            );

            if ($card['type'] == $typeBlock) { // truth, action blocked
                $this->cards->moveCard($cardReveal, 'tableau', $playerBlock);
                $args['balloon'] = $this->balloons['truth'];
                $this->doBalloon('reveal', clienttranslate('${player_name} reveals the ${card_name} as claimed.'), $args);
                self::incStat(1, 'challengeLoss', $playerChallenge);

                // Blocker must replace card
                $this->doReplace($playerBlock, array($cardReveal));

                // Challenger must kill a card
                self::setGameStateValue('playerKill', $playerChallenge);
                self::setGameStateValue('cardKill', 0);
                self::setGameStateValue('skipExecute', 1);
            } else { // lie, action occurs
                $args['balloon'] = $this->balloons['lie'];
                $this->doBalloon('reveal', clienttranslate('${player_name} reveals the ${card_name} and was bluffing!'), $args);
                self::incStat(1, 'challengeWin', $playerChallenge);

                // Action still occurs
                self::setGameStateValue('playerBlock', 0);

                // Blocker must kill revealed card
                self::setGameStateValue('playerKill', $playerBlock);
                self::setGameStateValue('cardKill', $cardReveal);
            }
            $this->gamestate->nextState('killLoss');
        } else { // block reveal prompt
            self::setGameStateValue('reasonChoose', REASON_CHALLENGE);
            $this->gamestate->changeActivePlayer($playerBlock);
            $this->gamestate->nextState('askChooseCard');
        }
    }

    public function stKillLoss()
    {
        $nextState = 'execute';
        $playerKill = self::getGameStateValue('playerKill');
        if ($playerKill > 0) {
            $cardKill = self::getGameStateValue('cardKill') ?: $this->doAutoChoose($playerKill);
            if ($cardKill > 0) { // kill
                $this->doKill($playerKill, REASON_LOSS, $cardKill);
                if ($this->checkWin()) {
                    return;
                }
            } elseif ($cardKill == 0) { // prompt
                self::setGameStateValue('reasonChoose', REASON_LOSS);
                $this->gamestate->changeActivePlayer($playerKill);
                $nextState = 'askChooseCard';
            } else { // already eliminated
                self::warn("stKillLoss: Nothing to do for playerKill=$playerKill, cardKill=$cardKill");
            }
        }
        $this->gamestate->nextState($nextState);
    }

    public function stExecute()
    {
        $action = self::getGameStateValue('action');
        $action_ref = $this->actions[$action];
        $playerTurn = self::getGameStateValue('playerTurn');
        $playerBlock = self::getGameStateValue('playerBlock');
        $skipExecute = self::getGameStateValue('skipExecute');

        // Ensure active player is correct
        $this->gamestate->changeActivePlayer($playerTurn);

        // If not blocked, action occurs
        // Determine how to log it
        $logAs = 'wealthInstant';
        $transition = 'playerEnd';
        $target = self::getGameStateValue('playerTarget');
        $args = array(
            'i18n' => array(),
            'action' => $action,
            'player_id' => $playerTurn,
            'player_name' => $this->getName($playerTurn)
        );

        if ($skipExecute == 0 && $playerBlock == 0) { // not blocked
            switch ($action) {
            case INCOME:
            case FOREIGN_AID:
            case TAX:
                $args['balloon'] = $this->balloons['wealth'];
                $args['amount'] = $action_ref['amount'];
                $args['wealth'] = $this->addWealth($playerTurn, $args['amount']);
                self::incStat($args['amount'], 'wealthIn', $playerTurn);
                break;

            case COUP:
            case ASSASSINATE:
                $logAs = 'wealth';
                $transition = 'killCoup';
                $args['balloon'] = $this->balloons['kill'];
                $args['wealth'] = $this->addWealth($playerTurn, $action_ref['cost'] * -1);
                self::incStat($action_ref['cost'], 'wealthOut', $playerTurn);
                break;

            case EXCHANGE:
            case EXCHANGE1:
                $logAs = 'draw';
                $transition = 'askDiscard';
                $args['balloon'] = $this->balloons['draw'];
                $args['count'] = $action_ref['count'];
                $newCards = $this->cards->pickCards($args['count'], 'deck', $playerTurn);
                $args['deck_count'] = $this->cards->countCardInLocation('deck');
                self::notifyPlayer($playerTurn, 'drawInstant', '', array(
                    'player_id' => $playerTurn,
                    'cards' => $newCards
                ));
                break;

            case STEAL:
                $args['balloon'] = $this->balloons['wealth'];
                $args['amount'] = min(2, $this->getWealth($target));
                $args['wealth'] = $this->addWealth($playerTurn, $args['amount']);
                self::notifyAllPlayers('wealthInstant', '', array(
                    'player_id' => $target,
                    'wealth' => $this->addWealth($target, $args['amount'] * -1)
                ));
                self::incStat($args['amount'], 'wealthIn', $playerTurn);
                break;

            case CONVERT:
                $logAs = 'convert';
                $args['amount'] = $target == $playerTurn ? 1 : 2;
                // Transfer money from player to almshouse
                $almshouse = self::getGameStateValue('almshouse') + $args['amount'];
                self::setGameStateValue('almshouse', $almshouse);
                self::notifyAllPlayers('wealthInstant', '', array(
                    'player_id' => $playerTurn,
                    'wealth' => $this->addWealth($playerTurn, $args['amount'] * -1),
                    'almshouse' => $almshouse
                ));
                // Switch faction (balloon is spoken by convert)
                $args['balloon'] = $this->balloons['convert'];
                $args['player_id'] = $target;
                $args['faction'] = $this->toggleFaction($target);
                $args['i18n'][] = 'faction_name';
                $args['faction_name'] = $this->factions[$args['faction']]['name'];
                self::incStat($args['amount'], 'wealthOut', $playerTurn);
                break;

            case EMBEZZLE:
                $args['balloon'] = $this->balloons['wealth'];
                $args['amount'] = self::getGameStateValue('almshouse');
                self::setGameStateValue('almshouse', 0);
                $args['wealth'] = $this->addWealth($playerTurn, $args['amount']);
                $args['almshouse'] = 0;
                self::incStat($args['amount'], 'wealthIn', $playerTurn);
                break;

            case EXAMINE:
                $logAs = 'balloonInstant';
                $transition = 'askExamine';
                $cardExamine = self::getGameStateValue('cardExamine');
                if ($cardExamine == 0) {
                    $hand = $this->getCardIds('hand', $target);
                    $handCount = count($hand);
                    if ($handCount == 0) {
                        // Target has no cards (lost challenge)
                        $this->doBalloon('balloonInstant', clienttranslate('${player_name}\'s ${action_name} does not occur.'), array(
                            'i18n' => array('action_name'),
                            'player_id' => $playerTurn,
                            'player_name' => $this->getName($playerTurn),
                            'action_name' => $action_ref['name'],
                            'balloon' => ''
                        ));
                        $this->gamestate->nextState('playerEnd');
                        return;
                    } elseif ($handCount == 1) {
                        // Auto-select only card
                        $cardExamine = array_shift($hand);
                        self::setGameStateValue('cardExamine', $cardExamine);
                    } else {
                        // Prompt target to choose a card
                        self::setGameStateValue('reasonChoose', REASON_EXAMINE);
                        $this->gamestate->changeActivePlayer($target);
                        $this->gamestate->nextState('askChooseCard');
                        return;
                    }
                }
                $args['balloon'] = $this->balloons['examine'];
                break;
            }

            if ($target) {
                $args['player_name2'] = $this->getName($target);
                if ($action == STEAL) {
                    // Only Steal action is a wealth transfer
                    $args['player_id2'] = $target;
                }
            }
            if ($action_ref['logExecute']) {
                $this->doBalloon($logAs, $action_ref['logExecute'], $args);
            }
            $stat = array_key_exists('stat', $action_ref) ? $action_ref['stat'] : "action$action";
            self::incStat(1, $stat, $playerTurn);
        } else { // blocked or skipped
            $this->doBalloon('balloonInstant', clienttranslate('${player_name}\'s ${action_name} does not occur.'), array(
                'i18n' => array('action_name'),
                'player_id' => $playerTurn,
                'player_name' => $this->getName($playerTurn),
                'action_name' => $action_ref['name'],
                'balloon' => ''
            ));
            if ($playerBlock != 0 && $action == ASSASSINATE) {
                // Still must pay if blocked
                $wealth = $this->addWealth($playerTurn, -3);
                self::notifyAllPlayers('wealthInstant', '', array(
                    'player_id' => $playerTurn,
                    'wealth' => $wealth
                ));
                self::incStat(3, 'wealthOut', $playerTurn);
            }
        }

        $this->gamestate->nextState($transition);
    }

    public function stExamine()
    {
        // Show card to turn player only
        $playerTurn = self::getGameStateValue('playerTurn');
        $cardExamine = self::getGameStateValue('cardExamine');
        $target = self::getGameStateValue('playerTarget');
        $card = $this->getCard($cardExamine, 'hand', $target);
        self::notifyPlayer($playerTurn, 'revealInstant', 'Shh! ${player_name}\'s card is the ${card_name}.', array(
            'i18n' => array('card_name'),
            'player_id' => $target,
            'player_name' => $this->getName($target),
            'cards' => array($card),
            'card_name' => $card['name'],
            'alive' => true,
            'secret' => true,
        ));
    }

    public function stKillCoup()
    {
        $nextState = 'playerEnd';
        $playerTarget = self::getGameStateValue('playerTarget');
        if ($playerTarget > 0) {
            $cardCoup = self::getGameStateValue('cardCoup') ?: $this->doAutoChoose($playerTarget);
            if ($cardCoup > 0) { // kill
                $this->doKill($playerTarget, REASON_KILL, $cardCoup);
            } elseif ($cardCoup == 0) { // prompt
                self::setGameStateValue('reasonChoose', REASON_KILL);
                $this->gamestate->changeActivePlayer($playerTarget);
                $nextState = 'askChooseCard';
            } else { // already eliminated
                self::warn("stKillCoup: Nothing to do for playerTarget=$playerTarget, cardCoup=$cardCoup");
            }
        }
        $this->gamestate->nextState($nextState);
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
            if ($state['name'] == 'askChooseCard') {
                $this->actionChooseCard($hand[0]);
            } elseif ($state['name'] == 'askDiscard') {
                $action = self::getGameStateValue('action');
                $action_ref = $this->actions[$action];
                $discard = array_slice($hand, 0, $action_ref['count']);
                $this->actionDiscard($discard);
            } elseif ($state['name'] == 'askExamine') {
                $this->actionExamineKeep();
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
    }
}
