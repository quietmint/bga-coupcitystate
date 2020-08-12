/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Coup implementation : © quietmint
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * coupcitystate.js
 *
 * Coup user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;

define(["dojo", "dojo/_base/declare", "dojo/dom-attr", "ebg/core/gamegui", "ebg/counter", "ebg/stock"], function (dojo, declare, domAttr) {
    function override_format_string_recursive(str, args) {
        if (str && args && !args.formatted) {
            args.formatted = true;
            if (args.player_name && !args.player_name.startsWith('<')) {
                args.player_name = this.format_player_name(args.player_name);
            }
            if (args.player_name2 && !args.player_name2.startsWith('<')) {
                args.player_name2 = this.format_player_name(args.player_name2);
            }
            if (args.card_name) {
                args.card_name = this.format_character_name(args.card_name);
            }
            if (args.card_name2) {
                args.card_name2 = this.format_character_name(args.card_name2);
            }
            if (args.detail) {
                args.detail = this.format_character_name(args.detail);
            }
            if (args.faction_name) {
                args.faction_name = this.format_faction_name(args.faction_name);
            }
        }
        return this.inherited(override_format_string_recursive, arguments);
    };

    return declare("bgagame.coupcitystate", ebg.core.gamegui, {
        constructor: function () { },

        // Returns true for spectators, instant replay (during game), archive mode (after game end)
        isReadOnly: function () {
            return this.isSpectator || typeof g_replayFrom != "undefined" || g_archive_mode;
        },

        isDialog: function () {
            var dialogList = dojo.query('.dijitDialogUnderlayWrapper');
            return dialogList.length > 0 && dialogList[0].style.display != 'none';
        },

        /* Stylize character and faction names in logs, balloons, buttons, etc. */
        format_string_recursive: override_format_string_recursive,

        format_player_name: function (player_name) {
            for (var id in this.gamedatas.players) {
                var player = this.gamedatas.players[id];
                if (player_name == player.player_name) {
                    var bg = '';
                    if (player.color_back) {
                        bg = '; background-color: #' + player.color_back;
                    }
                    return '<span style="font-weight: bold; color: #' + player.color + bg + '">' + player_name + '</span>';
                }
            }
            return player_name;
        },

        format_character_name: function (character_name) {
            for (var character in this.gamedatas.characters) {
                var character_ref = this.gamedatas.characters[character];
                if (character_name == character_ref.name) {
                    return '<div class="character-name character-' + character + '">' + _(character_name) + '</div>';
                }
            }
            return character_name;
        },

        format_faction_name: function (faction_name) {
            for (var faction in this.gamedatas.factions) {
                var faction_ref = this.gamedatas.factions[faction];
                if (faction_name == faction_ref.name) {
                    return '<div class="faction-name faction-' + faction + '"><i class="iconify" data-icon="' + faction_ref.icon + '"></i> ' + _(faction_name) + '</div>';
                }
            }
            return faction_name;
        },

        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameters.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

        setup: function (gamedatas) {
            console.log('Gamedatas:', gamedatas);
            var _this = this;

            // Player actions
            gamedatas.actionsOrder.forEach(function (action) {
                var action_ref = gamedatas.actions[action];
                action_ref.id = action;

                action_ref.textName = _(action_ref.name);
                action_ref.textDesc = _(action_ref.text1) + ' ' + _(action_ref.text2);

                action_ref.textClaim = '';
                if (action_ref.forbid && action_ref.forbid.length > 0) {
                    var card_name = action_ref.forbid.filter(function (id) {
                        return gamedatas.characters[id] != null;
                    }).map(function (id) {
                        return gamedatas.characters[id].name;
                    });
                    action_ref.textClaim = _this.format_string_recursive(_('not as ${card_name}'), {
                        card_name: card_name
                    });
                } else if (action_ref.character) {
                    var card_name = gamedatas.characters[action_ref.character].name;
                    action_ref.textClaim = _this.format_string_recursive(_('as ${card_name}'), {
                        card_name: card_name
                    });
                }

                action_ref.textBlock = _('Cannot block.');
                if (action_ref.blockers && action_ref.blockers.length > 0) {
                    var card_name = action_ref.blockers.filter(function (id) {
                        return gamedatas.characters[id] != null;
                    }).map(function (id) {
                        return _this.format_character_name(gamedatas.characters[id].name);
                    }).join(' / ');
                    action_ref.textBlock = _this.format_string_recursive(_('${card_name} can block.'), {
                        card_name: card_name
                    });
                }

                dojo.place(_this.format_block('jstpl_action', action_ref), 'myactions');
            });

            if (!this.isSpectator) {
                dojo.addClass('placemat_' + this.player_id, 'mine');
                dojo.query('#myactions .action').connect('onclick', this, 'onAct');
                dojo.query('#almshouse').connect('onclick', this, 'onAct');
                dojo.query('#deck').connect('onclick', this, 'onAct');

                var osp = dojo.hitch(this, 'onSelectPlayer');
                var placemats = document.querySelectorAll('.placemat');
                for (var i = 0; i < placemats.length; i++) {
                    placemats[i].addEventListener('click', osp, i > 0);
                }
            }

            // Almshouse
            if (gamedatas.variantFactions) {
                dojo.removeClass('almshouse', 'hide');
            }

            this.tableau = {};
            for (var player_id in gamedatas.players) {
                // Add coin counter
                dojo.place('<span id="panel_wealth_' + player_id + '"></span>', 'player_score_' + player_id, 'before');

                // Setup stock
                var myCards = new ebg.stock();
                myCards.create(this, $('cards_' + player_id), 90, 128);
                myCards.image_items_per_row = 1;
                myCards.item_margin = 2;
                myCards.apparenceBorderWidth = '2px';
                myCards.setSelectionMode(0);
                myCards.onItemCreate = dojo.hitch(this, 'setupCard');
                for (var i in gamedatas.characters) {
                    var pos = i == 7 ? 1 : i
                    myCards.addItemType(i, i, g_gamethemeurl + 'img/cards.jpg', pos);
                }
                if (player_id == this.player_id) {
                    dojo.connect(myCards, 'onChangeSelection', this, 'onSelectCard');
                }
                this.tableau[player_id] = myCards;
            }

            // Reset for new round
            this.roundBegin(gamedatas);

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
        },

        setupCard: function (card_div, card_type_id, card_id) {
            dojo.addClass(card_div, 'card');
            if (card_type_id > 0) {
                var character_ref = this.gamedatas.characters[card_type_id];
                // Translate all character text
                var text = character_ref.text.map(function (str) {
                    return _(str)
                }).join(' ');
                var tip = '<div class="character-name character-' + card_type_id + '">' + _(character_ref.name) + '</div>' +
                    '<div style="max-width: 200px">' + text + '</div>';
                this.addTooltipHtml(card_div.id, tip);
                dojo.place('<div class="card-name">' + _(character_ref.name) + '</div>', card_div.id);
            }
        },

        roundBegin: function (gamedatas) {
            // Reset current player
            dojo.query('.placemat.active').removeClass('active');
            dojo.addClass('placemat_' + gamedatas.turn, 'active');

            // Reset balloons
            dojo.query('.balloon').forEach(dojo.empty);

            // Reset almshouse count
            $('almshousecount').innerText = '₤' + (gamedatas.almshouse || 0);

            // Reset deck count
            $('deckcount').innerText = gamedatas.deckCount || 0;

            for (var player_id in gamedatas.players) {
                var player = gamedatas.players[player_id];

                // Reset elimination status
                if (+player.eliminated) {
                    this.notif_eliminate({
                        args: {
                            player_id: player_id
                        }
                    });
                } else {
                    dojo.removeClass('placemat_' + player_id, 'eliminated');
                    dojo.removeClass('overall_player_board_' + player_id, 'eliminated');
                }

                // Reset balloon
                if (player.balloon) {
                    this.notif_balloon({
                        args: player.balloon
                    }, true);
                }

                // Reset faction
                if (player.faction) {
                    this.notif_convert({
                        args: {
                            player_id: player_id,
                            faction: player.faction
                        }
                    })
                }

                // Reset wealth
                this.notif_wealth({
                    args: {
                        player_id: player_id,
                        wealth: player.wealth
                    }
                });

                // Reset cards
                myCards = this.tableau[player_id];
                myCards.removeAll();

                // Add visible cards (dead)
                if (player.tableau) {
                    for (var i in player.tableau) {
                        var card = player.tableau[i];
                        myCards.addToStockWithId(+card.type, +card.id, 'deckcard');
                        var divId = myCards.getItemDivId(+card.id);
                        dojo.addClass(divId, 'dead');
                        this.removeTooltip(divId);
                    }
                }

                // Add secret cards
                var player_hand = player.hand;
                if (player_id == this.player_id) {
                    player_hand = gamedatas.hand;
                }
                if (player_hand) {
                    for (var i in player_hand) {
                        var card = player_hand[i];
                        myCards.addToStockWithId(+card.type, +card.id, 'deckcard');
                    }
                } else {
                    this.addUnknownCards(myCards, player.handCount, 'deckcard');
                }
                this.resizeHand(player_id, myCards.getAllItems().length);
            }
        },

        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function (stateName, args) {
            console.info('Entering state: ' + stateName, args.args);
            if (stateName == 'playerBegin') {
                var active = this.getActivePlayerId();
                dojo.query('.placemat.active').removeClass('active');
                dojo.addClass('placemat_' + active, 'active');
            }

            if (this.isCurrentPlayerActive()) {
                switch (stateName) {
                    case 'playerBegin':
                    case 'askChooseCard':
                        this.tableau[this.player_id].setSelectionMode(1);
                        break;

                    case 'askDiscard':
                        this.tableau[this.player_id].setSelectionMode(2);
                        break;
                }
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function (stateName) {
            console.info('Leaving state: ' + stateName);
            if (!this.isReadOnly()) {
                this.stopActionTimer();
            }
            if (!this.isSpectator) {
                if (stateName == 'playerBegin') {
                    dojo.query('.placemat.selected').removeClass('selected');
                }
                this.tableau[this.player_id].setSelectionMode(0);
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function (stateName, args) {
            console.info('Update action buttons: ' + stateName, args);
            if (this.isCurrentPlayerActive()) {
                switch (stateName) {
                    case 'ask':
                    case 'askBlock':
                    case 'secondChance':
                        this.addActionButton('button_no', _('I allow'), 'onActionNo');
                        if (args != null) {
                            // Challenge not allowed during second chance
                            if (stateName != 'secondChance') {
                                if (args.card_name != null) {
                                    var str = this.format_string_recursive(_('I challenge your ${card_name}'), args);
                                    this.addActionButton('button_yes', str, 'onActionYes');
                                } else if (args.forbid != null) {
                                    var str = _('I challenge');
                                    this.addActionButton('button_yes', str, 'onActionYes');
                                }
                            }

                            // Combine public and private block actions
                            var blockers = [];
                            if (args.blockers != null) {
                                Array.prototype.push.apply(blockers, args.blockers);
                            }
                            if (args._private != null && args._private.blockers != null) {
                                Array.prototype.push.apply(blockers, args._private.blockers);
                            }
                            for (var i = 0; i < blockers.length; i++) {
                                var blocker = blockers[i];
                                var character_ref = this.gamedatas.characters[blocker];
                                var str = this.format_string_recursive(_('I block with my ${card_name}'), {
                                    card_name: character_ref.name
                                });
                                this.addActionButton('button_block' + blocker, str, 'onBlock');
                            }
                        }
                        this.startActionTimer('button_no');
                        break;

                    case 'askDiscard':
                        this.addActionButton('button_exchange', _('Discard'), 'onActionDiscard');
                        break;

                    case 'askExamine':
                        var keepText = this.format_string_recursive(_('Return ${card_name}'), args._private);
                        var exchangeText = this.format_string_recursive(_('Exchange ${card_name}'), args._private);
                        this.addActionButton('button_keep', keepText, 'onActionExamineKeep');
                        this.addActionButton('button_exchange', exchangeText, 'onActionExamineExchange');
                        this.startActionTimer('button_keep');
                        break;
                }
            }
        },

        startActionTimer: function startActionTimer(buttonId) {
            this.stopActionTimer();
            var button = $(buttonId);
            if (isDebug || button == null || this.isReadOnly() || this.isDialog()) {
                return;
            }

            var _this = this;
            this.actionTimerLabel = button.innerHTML;
            this.actionTimerSeconds = 15;
            this.actionTimerFunction = function () {
                var button = $(buttonId);
                if (button == null) {
                    _this.stopActionTimer();
                } else if (_this.actionTimerSeconds-- > 1) {
                    console.log('Timer ' + buttonId + ' has ' + _this.actionTimerSeconds + ' seconds left');
                    button.innerHTML = _this.actionTimerLabel + ' (' + _this.actionTimerSeconds + ')';
                } else {
                    console.log('Timer ' + buttonId + ' execute');
                    button.click();
                }
            };
            this.actionTimerFunction();
            this.actionTimerId = window.setInterval(this.actionTimerFunction, 1000);
            console.info('Timer #' + this.actionTimerId + ' ' + buttonId + ' start');
        },

        stopActionTimer: function stopActionTimer() {
            if (this.actionTimerId != null) {
                console.info('Timer #' + this.actionTimerId + ' stop');
                window.clearInterval(this.actionTimerId);
                delete this.actionTimerId;
            }
        },

        onWouldLikeToThink: function (evt) {
            // Add 15 seconds to the timer
            if (this.actionTimerId != null) {
                this.actionTimerSeconds += 15;
                console.info('Timer #' + this.actionTimerId + ' extend');
            }
            return this.inherited(arguments);
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        doAction: function (action, args) {
            this.stopActionTimer();
            if (this.checkAction(action)) {
                console.info('Taking action: ' + action, args);
                // Deselect target
                dojo.query('.placemat.selected').removeClass('selected');
                args = args || {};
                args.lock = true;
                this.ajaxcall('/coupcitystate/coupcitystate/' + action + '.html', args, this, function (result) { });
            }
        },

        hasCard: function (stock, id) {
            return stock.getAllItems().some(function (card) {
                return card.id == id;
            });
        },

        addUnknownCards: function (stock, count, from) {
            var card_ids = [];
            if (count > 0) {
                var unknownCount = stock.getAllItems().filter(function (card) {
                    return card.type == 0;
                }).length;
                for (var i = 0; i < count; i++) {
                    var card_id = 100 + unknownCount + i;
                    card_ids.push(card_id);
                    stock.addToStockWithId(0, card_id, from);
                }
            }
            return card_ids;
        },

        removeUnknownCards: function (stock, count, to) {
            if (count > 0) {
                var removed = 0;
                stock.getAllItems().reverse().some(function (card) {
                    if (card.type == 0) {
                        stock.removeFromStockById(card.id, to);
                        removed++;
                    }
                    // Stop if we've removed enough cards
                    return removed == count;
                });
            }
        },

        resizeHand: function (player_id, total) {
            // Set width and overlap based on number of cards
            var myCards = this.tableau[player_id];
            var width = null;
            if (total > 2) {
                var width = 92 + (47 * (total - 1)) + 'px';
                myCards.setOverlap(50, 0);
            } else {
                myCards.setOverlap(75, 0);
            }
            document.getElementById('cards_' + player_id).style.width = width;
        },

        ///////////////////////////////////////////////////
        //// Player's action

        onSelectPlayer: function (evt) {
            if (this.isCurrentPlayerActive() && this.checkAction('act', true)) {
                var select_id = domAttr.get(evt.currentTarget, 'data-player');
                if (dojo.hasClass(evt.currentTarget, 'selected')) {
                    dojo.stopEvent(evt);
                    dojo.removeClass(evt.currentTarget, 'selected');
                } else if (!dojo.hasClass(evt.currentTarget, 'eliminated')) {
                    dojo.stopEvent(evt);
                    dojo.query('.placemat').removeClass('selected');
                    dojo.addClass(evt.currentTarget, 'selected');
                    if (this.pendingAction) {
                        this.onAct(this.pendingAction);
                    }
                }
            }
        },

        onSelectCard: function (htmlId) {
            var selectCards = this.tableau[this.player_id];
            var items = selectCards.getSelectedItems();
            // Unselect dead cards
            items.forEach(function (card) {
                var isDead = dojo.hasClass(selectCards.getItemDivId(card.id), 'dead');
                if (isDead) {
                    selectCards.unselectItem(card.id);
                }
            });
            items = selectCards.getSelectedItems();
            if (items.length > 0) {
                if (this.checkAction('actionDiscard', true)) {
                    // Allow multi-select, do nothing
                } else {
                    selectCards.unselectAll();
                    if (this.checkAction('act', true)) {
                        // Clicking your own card performs its action
                        // (unless character has multiple actions)
                        var characterActions = [];
                        for (var action in this.gamedatas.actions) {
                            var action_ref = this.gamedatas.actions[action];
                            if (action_ref.character == items[0].type) {
                                characterActions.push(+action);
                            }
                        }
                        if (characterActions.length == 1) {
                            this.onAct(characterActions[0]);
                        }

                    } else if (this.checkAction('actionChooseCard', true)) {
                        this.doAction('actionChooseCard', {
                            card_id: items[0].id
                        });
                    }
                }
            }
        },

        onAct: function (evt) {
            delete this.pendingAction;
            dojo.query('#myactions .action.pending').removeClass('pending');
            if (typeof evt == 'number') {
                // Happens when clicking your own card
                var action = evt;
            } else {
                // Happens when clicking an action button
                dojo.stopEvent(evt);
                var button = evt.currentTarget;
                var action = +domAttr.get(button, 'data-action');
            }

            if (action > 0 && this.checkAction('act')) {
                var action_ref = this.gamedatas.actions[action];
                var args = {
                    act: action
                };

                // Check wealth
                var wealth = this.gamedatas.players[this.player_id].wealth;
                if (wealth >= 10 && action_ref.name != 'Coup') {
                    this.showMessage(_('Invalid move. You must Coup because you have ₤10.'), 'error');
                    return;
                } else if (wealth < action_ref.cost) {
                    var str = _('Invalid move. You need ₤%d for this action.').replace('%d', action_ref.cost);
                    this.showMessage(str, 'error');
                    return;
                }

                // Check target
                if (action_ref.target) {
                    var target = dojo.query('.placemat.selected')[0];
                    // Automatic targeting with 2 active players
                    if (target == null && action_ref.name != 'Convert') {
                        var possibleTargets = dojo.query('.placemat:not(.eliminated):not(#placemat_' + this.player_id + ')');
                        if (possibleTargets.length == 1) {
                            target = possibleTargets[0];
                        }
                    }
                    if (target == null) {
                        this.pendingAction = action;
                        dojo.query('#myactions .action.action-' + action).addClass('pending');
                        var msg = _('Choose a target player for this action.');
                        this.showMessage(msg, 'info');
                        return;
                    }
                    args.target = +domAttr.get(target, 'data-player');
                }

                this.doAction('act', args);
            }
        },

        onActionDiscard: function (evt) {
            dojo.stopEvent(evt);
            var items = this.tableau[this.player_id].getSelectedItems();
            if (this.checkAction('actionDiscard')) {
                var card_ids = items.map(function (item) {
                    return item.id;
                }).join(';');
                this.doAction('actionDiscard', {
                    card_ids: card_ids
                });
            }
            this.tableau[this.player_id].unselectAll();
        },

        onActionNo: function (evt) {
            dojo.stopEvent(evt);
            this.doAction('actionNo');
        },

        onActionYes: function (evt) {
            dojo.stopEvent(evt);
            this.doAction('actionYes');
        },

        onBlock: function (evt) {
            dojo.stopEvent(evt);
            this.doAction('actionBlock', {
                card_type: +evt.currentTarget.id.substr(-1)
            });
        },

        onActionExamineKeep: function (evt) {
            dojo.stopEvent(evt);
            this.doAction('actionExamineKeep');
        },

        onActionExamineExchange: function (evt) {
            dojo.stopEvent(evt);
            this.doAction('actionExamineExchange');
        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your coupcitystate.game.php file.

        */
        setupNotifications: function () {
            dojo.subscribe('roundBegin', this, 'notif_roundBegin');
            dojo.subscribe('eliminate', this, 'notif_eliminate');
            dojo.subscribe('scores', this, 'notif_scores');

            dojo.subscribe('convertInstant', this, 'notif_convert');
            dojo.subscribe('convert', this, 'notif_convert');
            this.notifqueue.setSynchronous('convert', 2000);

            dojo.subscribe('wealthInstant', this, 'notif_wealth');
            dojo.subscribe('wealth', this, 'notif_wealth');
            this.notifqueue.setSynchronous('wealth', 2000);

            dojo.subscribe('balloonInstant', this, 'notif_balloon');
            dojo.subscribe('balloonPause', this, 'notif_balloon');
            dojo.subscribe('balloon', this, 'notif_balloon');
            this.notifqueue.setSynchronous('balloonPause', 1000);
            this.notifqueue.setSynchronous('balloon', 2000);

            dojo.subscribe('revealInstant', this, 'notif_reveal');
            dojo.subscribe('reveal', this, 'notif_reveal');
            this.notifqueue.setSynchronous('reveal', 2000);

            dojo.subscribe('unrevealInstant', this, 'notif_unreveal');
            dojo.subscribe('unreveal', this, 'notif_unreveal');
            this.notifqueue.setSynchronous('unreveal', 2000);

            dojo.subscribe('discardInstant', this, 'notif_discard');
            dojo.subscribe('discard', this, 'notif_discard');
            this.notifqueue.setSynchronous('discard', 2000);

            dojo.subscribe('drawInstant', this, 'notif_draw');
            dojo.subscribe('draw', this, 'notif_draw');
            this.notifqueue.setSynchronous('draw', 2000);
        },

        notif_roundBegin: function (n) {
            this.roundBegin(n.args);
        },

        notif_eliminate: function (n) {
            var player_id = n.args.player_id;
            dojo.addClass('placemat_' + player_id, 'eliminated');
            dojo.addClass('overall_player_board_' + player_id, 'eliminated');
        },

        notif_scores: function (n) {
            var scores = n.args.scores;
            for (player_id in scores) {
                this.scoreCtrl[player_id].toValue(scores[player_id]);
            }
        },

        notif_convert: function (n) {
            var player_id = n.args.player_id;
            var faction = n.args.faction;
            var faction_ref = this.gamedatas.factions[faction];
            this.gamedatas.players[player_id].faction = faction;
            dojo.removeClass('faction_' + player_id, 'faction-1 faction-2');
            dojo.addClass('faction_' + player_id, 'faction-' + faction);
            var factionEl = document.getElementById('faction_' + player_id);
            factionEl.innerHTML = '<i class="iconify" data-icon="' + faction_ref.icon + '"></i>';
            factionEl.title = _(faction_ref.name);
            dojo.removeClass('placemat_' + player_id, 'faction-1 faction-2');
            dojo.addClass('placemat_' + player_id, 'faction-' + faction);

            if (n.args.balloon) {
                this.notif_balloon(n);
            }
        },

        notif_wealth: function (n) {
            var player_id = n.args.player_id;
            var wealth = n.args.wealth || 0;
            this.gamedatas.players[player_id].wealth = wealth;
            $('wealth_' + player_id).innerText = '₤' + wealth;
            $('panel_wealth_' + player_id).innerText = '₤' + wealth + ' • ';
            if (n.args.almshouse != null) {
                $('almshousecount').innerText = '₤' + n.args.almshouse;
            }

            if (n.args.balloon) {
                this.notif_balloon(n);
            }
        },

        notif_balloon: function (n, fromInit) {
            var player_id = n.args.player_id;
            if (player_id) {
                // Clear all balloons
                if (!fromInit && !n.args.balloonKeep) {
                    dojo.query('.balloon').forEach(dojo.empty);
                }

                // New balloon
                var html = '';
                if (n.args.balloon == 'no') {
                    html = '<i class="icon-action-no iconify" data-icon="mdi-thumb-up-outline"></i>';
                } else if (n.args.balloon != null) {
                    if (n.args.action) {
                        var action_ref = this.gamedatas.actions[n.args.action];
                        if (action_ref != null) {
                            html += '<i class="icon-action-' + n.args.action + ' iconify" data-icon="' + action_ref.icon + '"></i> ';
                        }
                    }
                    html += this.format_string_recursive(_(n.args.balloon), n.args);
                }
                if (html) {
                    $('balloon_' + player_id).innerHTML = html;
                }
            }
        },

        notif_reveal: function (n) {
            var player_id = n.args.player_id;
            var myCards = this.tableau[player_id];
            if (myCards != null) {
                var cards = n.args.cards;
                for (var i = 0; i < cards.length; i++) {
                    var card = cards[i];
                    if (!this.hasCard(myCards, +card.id)) {
                        this.removeUnknownCards(myCards, 1);
                        myCards.addToStockWithId(+card.type, +card.id);
                    }
                    var divId = myCards.getItemDivId(+card.id);
                    var newClass = 'reveal';
                    if (n.args.secret) {
                        newClass += ' secret';
                    } else if (!n.args.alive) {
                        newClass += ' dead';
                        this.removeTooltip(divId);
                    }
                    dojo.addClass(divId, newClass);
                }
            }

            if (n.args.balloon) {
                this.notif_balloon(n);
            }
        },

        notif_unreveal: function (n) {
            var player_id = n.args.player_id;
            var myCards = this.tableau[player_id];
            if (myCards != null) {
                var cards = n.args.cards;
                for (var i = 0; i < cards.length; i++) {
                    var card = cards[i];
                    if (this.hasCard(myCards, +card.id)) {
                        myCards.removeFromStockById(+card.id);
                        var card_ids = this.addUnknownCards(myCards, 1);
                        var divId = myCards.getItemDivId(+card_ids[0]);
                        dojo.addClass(divId, 'reveal');
                    }
                }
            }

            if (n.args.balloon) {
                this.notif_balloon(n);
            }
        },

        notif_discard: function (n) {
            if (n.args.ignored_by && n.args.ignored_by.includes(+this.player_id)) {
                return false;
            }

            var player_id = n.args.player_id;
            var isMe = this.player_id == player_id;
            var myCards = this.tableau[player_id];
            var card_ids = n.args.card_ids;
            if (card_ids) {
                // Discard visible cards by ID
                for (var i = 0; i < card_ids.length; i++) {
                    var id = +card_ids[i];
                    myCards.removeFromStockById(id, 'deckcard');
                }
            } else if (!isMe && n.args.count) {
                // Discard hidden cards
                this.removeUnknownCards(myCards, n.args.count, 'deckcard');
            }
            this.resizeHand(player_id, myCards.getAllItems().length);

            if (n.args.deck_count) {
                $('deckcount').innerText = n.args.deck_count;
            }

            if (n.args.balloon) {
                this.notif_balloon(n);
            }
        },

        notif_draw: function (n) {
            var player_id = n.args.player_id;
            var isMe = this.player_id == player_id;
            var myCards = this.tableau[player_id];
            var cards = n.args.cards;
            if (cards) {
                var total = myCards.getAllItems().length + cards.length;
                this.resizeHand(player_id, total);

                // Draw cards
                for (var i = 0; i < cards.length; i++) {
                    var card = cards[i];
                    myCards.addToStockWithId(+card.type, +card.id, 'deckcard');
                }
            } else if (!isMe && n.args.count) {
                var total = myCards.getAllItems().length + n.args.count;
                this.resizeHand(player_id, total);

                // Draw hidden cards
                this.addUnknownCards(myCards, n.args.count, 'deckcard');
            }

            if (n.args.deck_count) {
                $('deckcount').innerText = n.args.deck_count;
            }

            if (n.args.balloon) {
                this.notif_balloon(n);
            }
        },
    });
});