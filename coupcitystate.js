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

define([
        "dojo", "dojo/_base/declare", "dojo/dom-attr", "dojo/dom-style",
        "ebg/core/gamegui",
        "ebg/counter",
        "ebg/stock"
    ],
    function(dojo, declare, domAttr, domStyle) {
        return declare("bgagame.coupcitystate", ebg.core.gamegui, {
            constructor: function() {
                this.cardwidth = 128;
                this.cardheight = 205;
                this.coinwidth = 40;
                this.coinheight = 40;

                // Stylize character names in the game log
                var orig_playerNameFilterGame = this.notifqueue.playerNameFilterGame;
                this.notifqueue.playerNameFilterGame = function(args) {
                    function stylizeCharacter(characters, card_name) {
                        for (var character in characters) {
                            var character_ref = characters[character];
                            if (card_name == character_ref.name) {
                                return '<div class="character-name" style="background-color: ' + character_ref.color_bright + '">' + card_name + '</div>';
                            }
                        }
                        return card_name;
                    }

                    var characters = this.game.gamedatas.characters;
                    if (args.card_name) {
                        args.card_name = stylizeCharacter(characters, args.card_name);
                    }
                    if (args.card_name2) {
                        args.card_name2 = stylizeCharacter(characters, args.card_name2);
                    }
                    return orig_playerNameFilterGame.call(this, args);
                }
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

            setup: function(gamedatas) {
                this.coins = {};
                this.tableau = {};
                for (var player_id in gamedatas.players) {
                    var player = gamedatas.players[player_id];
                    var handCount = gamedatas.handCounts[player_id] || 0;

                    // Eliminated?
                    if (player.eliminated) {
                        dojo.query('#placemat_' + player_id).addClass('eliminated');
                    }

                    // Setup my coins
                    $('coincount_' + player_id).innerHTML = player.wealth;
                    var myCoins = new ebg.stock();
                    myCoins.create(this, $('coins_' + player_id), this.coinwidth, this.coinheight);
                    myCoins.image_items_per_row = 1;
                    myCoins.setSelectionMode(0);
                    myCoins.setOverlap(15, 30);
                    myCoins.onItemCreate = dojo.hitch(this, 'setupCoin');
                    myCoins.addItemType(0, 0, g_gamethemeurl + 'img/fiorino.png', 0);

                    // Add coins
                    for (var i = 0; i < player.wealth; i++) {
                        myCoins.addToStock(0);
                    }
                    this.coins[player_id] = myCoins;

                    // Setup my cards
                    var myCards = new ebg.stock();
                    myCards.create(this, $('cards_' + player_id), this.cardwidth, this.cardheight);
                    myCards.image_items_per_row = 1;
                    myCards.setSelectionMode(0);
                    myCards.setOverlap(50, 0);
                    myCards.onItemCreate = dojo.hitch(this, 'setupCard');
                    for (var i in gamedatas.characters) {
                        myCards.addItemType(i, i, g_gamethemeurl + 'img/cards.jpg', i);
                    }

                    // Add visible cards (dead)
                    for (var i in gamedatas.tableau) {
                        var card = gamedatas.tableau[i];
                        if (card.location_arg == player_id) {
                            myCards.addToStockWithId(+card.type, +card.id);
                            dojo.addClass(myCards.getItemDivId(+card.id), 'dead');
                        }
                    }

                    // Add secret cards
                    if (player_id == this.player_id) {
                        for (var i in gamedatas.hand) {
                            var card = gamedatas.hand[i];
                            myCards.addToStockWithId(+card.type, +card.id);
                        }
                        dojo.connect(myCards, 'onChangeSelection', this, 'onSelectCard');
                    } else {
                        this.addUnknownCards(myCards, handCount);
                    }
                    this.tableau[player_id] = myCards;
                }

                // Player actions
                if (!this.isSpectator) {
                    dojo.query('#placemat_' + this.player_id).addClass('mine');
                    dojo.place('myactions', 'board_' + this.player_id, 'before');
                    dojo.query('#myactions .bgabutton').connect('onclick', this, 'onAct');

                    var osp = dojo.hitch(this, 'onSelectPlayer');
                    var placemats = document.querySelectorAll('.placemat');
                    for (var i = 1; i < placemats.length; i++) {
                        placemats[i].addEventListener('click', osp, true);
                    }
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();
            },

            setupTooltip: function(element, character) {
                var character_ref = this.gamedatas.characters[character];
                var tip = '<div class="character-name" style="background-color: ' + character_ref.color_bright + '">' + character_ref.name + '</div>' +
                    '<div style="max-width: 200px">' + character_ref.text + ' ' + character_ref.subtext + '</div>';
                this.addTooltipHtml(element, tip);
            },

            setupCard: function(card_div, card_type_id, card_id) {
                dojo.addClass(card_div, 'card');
                var character_ref = this.gamedatas.characters[card_type_id];
                if (card_type_id > 0) {
                    domStyle.set(card_div, 'color', character_ref.color);
                    this.setupTooltip(card_div.id, card_type_id);
                    dojo.place('<div class="card-name">' + character_ref.name + '</div>' +
                        '<div class="card-detail">' + character_ref.text + ' ' + character_ref.subtext + '</div>', card_div.id);
                }
            },

            setupCoin: function(card_div, card_type_id, card_id) {
                dojo.addClass(card_div, 'coin');
            },

            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function(stateName, args) {
                console.log('Entering state: ' + stateName, args.args);
                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'playerStart':
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
            onLeavingState: function(stateName) {
                console.log('Leaving state: ' + stateName);
                if (!this.isSpectator) {
                    if (window.passIntervalId) {
                        clearInterval(window.passIntervalId);
                        delete window.passIntervalId;
                    }
                    if (stateName == 'playerStart') {
                        dojo.query('.placemat.selected').removeClass('selected');
                    }
                    this.tableau[this.player_id].setSelectionMode(0);
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //
            onUpdateActionButtons: function(stateName, args) {
                console.log('Update action buttons: ' + stateName, args);
                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'ask':
                        case 'askBlock':
                            this.addActionButton('button_no', _('I allow'), 'onActionNo');

                            // If not directly involved, auto pass after 15 seconds
                            var hasPrivate = args != null && args._private != null && args._private.blockers != null;
                            if (!hasPrivate) {
                                if (window.passIntervalId) {
                                    clearInterval(window.passIntervalId);
                                }
                                window.passIntervalSeconds = 15;
                                window.passIntervalId = window.setInterval(dojo.hitch(this, 'passIntervalTick'), 1000);
                            }

                            if (args != null) {
                                if (args.card_name != null) {
                                    var str = dojo.string.substitute(_('I challenge your ${card_name}!'), {
                                        card_name: args.card_name
                                    });
                                    this.addActionButton('button_yes', str, 'onActionYes');
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
                                    var str = dojo.string.substitute(_('I block with my ${card_name}!'), {
                                        card_name: character_ref.name
                                    });
                                    this.addActionButton('button_block' + blocker, str, 'onBlock');
                                }
                            }
                            break;

                        case 'askDiscard':
                            this.addActionButton('button_exchange', _('Discard'), 'onActionDiscard');
                            break;
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            doAction: function(action, args) {
                if (this.checkAction(action)) {
                    console.log('Taking action: ' + action, args);
                    args = args || {};
                    args.lock = true;
                    this.ajaxcall('/coupcitystate/coupcitystate/' + action + '.html', args, this, function(result) {});
                }
            },

            hasCard: function(stock, id) {
                return stock.getAllItems().some(function(card) {
                    return card.id == id;
                });
            },

            addUnknownCards: function(stock, count, from) {
                if (count > 0) {
                    var unknownCount = stock.getAllItems().filter(function(card) {
                        return card.type == 0;
                    }).length;
                    for (var i = 0; i < count; i++) {
                        stock.addToStockWithId(0, 100 + unknownCount + i, from);
                    }
                }
            },

            removeUnknownCards: function(stock, count, to) {
                if (count > 0) {
                    var removed = 0;
                    stock.getAllItems().reverse().some(function(card) {
                        if (card.type == 0) {
                            stock.removeFromStockById(card.id, to);
                            removed++;
                        }
                        // Stop if we've removed enough cards
                        return removed == count;
                    });
                }
            },

            passIntervalTick: function() {
                var button_no = document.getElementById('button_no');
                if (button_no != null && window.passIntervalSeconds > 0) {
                    // Tick down the time
                    button_no.textContent = _('I allow') + ' (' + window.passIntervalSeconds-- + ')';
                } else {
                    // Stop the timer if the button doesn't exist
                    if (button_no != null) {
                        console.info('Automatically passing via countdown timer');
                        button_no.click();
                    }
                    clearInterval(window.passIntervalId);
                    delete window.passIntervalId;
                }
            },

            ///////////////////////////////////////////////////
            //// Player's action

            onSelectPlayer: function(evt) {
                if (this.isCurrentPlayerActive() && this.checkAction('act', true)) {
                    var select_id = domAttr.get(evt.currentTarget, 'data-player');
                    if (dojo.hasClass(evt.currentTarget, 'selected')) {
                        dojo.stopEvent(evt);
                        dojo.removeClass(evt.currentTarget, 'selected');
                    } else if (!dojo.hasClass(evt.currentTarget, 'eliminated')) {
                        dojo.stopEvent(evt);
                        dojo.query('.placemat').removeClass('selected');
                        dojo.addClass(evt.currentTarget, 'selected');
                    }
                }
            },

            onSelectCard: function(htmlId) {
                var selectCards = this.tableau[this.player_id];
                var items = selectCards.getSelectedItems();
                // Unselect dead cards
                items.forEach(function(card) {
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
                            for (var action in this.gamedatas.actions) {
                                var action_ref = this.gamedatas.actions[action];
                                if (action_ref.character == items[0].type) {
                                    this.onAct(+action);
                                    break;
                                }
                            }

                        } else if (this.checkAction('actionChooseCard', true)) {
                            this.doAction('actionChooseCard', {
                                card_id: items[0].id
                            });
                        }
                    }
                }
            },

            onAct: function(evt) {
                if (typeof evt == 'number') {
                    // Happens when clicking your own card
                    var action = evt;
                } else {
                    // Happens when clicking an action button
                    dojo.stopEvent(evt);
                    var button = evt.currentTarget;
                    var action = domAttr.get(button, 'data-action');
                }

                if (action > 0 && this.checkAction('act')) {
                    var action_ref = this.gamedatas.actions[action];
                    var args = {
                        act: action
                    };

                    // Check wealth
                    var wealth = this.coins[this.player_id].count();
                    if (wealth >= 10 && action_ref.name != 'Coup') {
                        this.showMessage(_('You must Coup because you have 10 or more coins.'), 'error');
                        return;
                    } else if (wealth < action_ref.cost) {
                        var str = _('You need %d coins for this action.').replace('%d', action_ref.cost);
                        this.showMessage(str, 'error');
                        return;
                    }

                    // Check target
                    if (action_ref.target) {
                        var target = dojo.query('.placemat.selected')[0];
                        if (target == null) {
                            this.showMessage(_('Choose an active player before performing this action.'), 'error');
                            return;
                        }
                        args.target = +domAttr.get(target, 'data-player');

                        if (action_ref.name == 'Steal' && this.coins[args.target].count() == 0) {
                            this.showMessage(_('Choose an active player with coins before performing this action.'), 'error');
                            return;
                        }
                    }

                    this.doAction('act', args);
                }
            },

            onActionDiscard: function(evt) {
                dojo.stopEvent(evt);
                var items = this.tableau[this.player_id].getSelectedItems();
                if (this.checkAction('actionDiscard')) {
                    if (items.length == 2) {
                        var card_ids = items.map(function(item) {
                            return item.id;
                        }).join(';');
                        this.doAction('actionDiscard', {
                            card_ids: card_ids
                        });
                        this.tableau[this.player_id].unselectAll();
                    } else {
                        this.showMessage(_('You must choose 2 active cards to discard.'), 'error');
                        return;
                    }
                } else {
                    this.tableau[this.player_id].unselectAll();
                }
            },

            onActionNo: function(evt) {
                dojo.stopEvent(evt);
                this.doAction('actionNo');
            },

            onActionYes: function(evt) {
                dojo.stopEvent(evt);
                this.doAction('actionYes');
            },

            onBlock: function(evt) {
                dojo.stopEvent(evt);
                this.doAction('actionBlock', {
                    card_type: +evt.currentTarget.id.substr(-1)
                });
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:

                In this method, you associate each of your game notifications with your local method to handle it.

                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your coupcitystate.game.php file.

            */
            setupNotifications: function() {
                dojo.subscribe('tableInfosChanged', this, 'notif_tableInfosChanged');

                dojo.subscribe('wealthNoDelay', this, 'notif_wealth');
                dojo.subscribe('wealth', this, 'notif_wealth');
                this.notifqueue.setSynchronous('reveal', 2000);

                dojo.subscribe('reveal', this, 'notif_reveal');
                this.notifqueue.setSynchronous('reveal', 3000);

                dojo.subscribe('discardNoDelay', this, 'notif_discard');
                dojo.subscribe('discard', this, 'notif_discard');
                this.notifqueue.setSynchronous('discard', 2000);

                dojo.subscribe('drawNoDelay', this, 'notif_draw');
                dojo.subscribe('draw', this, 'notif_draw');
                this.notifqueue.setSynchronous('draw', 2000);
            },

            notif_tableInfosChanged: function(n) {
                // Detect player elimination
                if (n.args.reload_reason == 'playerElimination') {
                    dojo.addClass('placemat_' + n.args.who_quits, 'eliminated');
                }
            },

            notif_wealth: function(n) {
                var player_id = n.args.player_id;
                $('coincount_' + player_id).innerText = n.args.wealth || 0;

                var fromElement = undefined;
                if (n.args.player_id2) {
                    fromElement = 'coins_' + n.args.player_id2;
                } else if (n.log) {
                    fromElement = 'player_boards';
                }

                var myCoins = this.coins[player_id];
                var diff = n.args.wealth - myCoins.count();
                if (diff > 0) {
                    for (; diff > 0; diff--) {
                        myCoins.addToStock(0, fromElement);
                    }
                } else if (diff < 0) {
                    for (; diff < 0; diff++) {
                        myCoins.removeFromStock(0, fromElement);
                    }
                }
            },

            notif_reveal: function(n) {
                var player_id = n.args.player_id;
                var myCards = this.tableau[player_id];
                if (myCards != null) {
                    for (var i = 0; i < n.args.card_ids.length; i++) {
                        var id = +n.args.card_ids[i];
                        var cardtype = +n.args.card_types[i];
                        if (!this.hasCard(myCards, id)) {
                            this.removeUnknownCards(myCards, 1);
                        }
                        myCards.addToStockWithId(cardtype, id);
                        var newClass = 'reveal';
                        if (!n.args.alive) {
                            newClass += ' dead';
                        }
                        dojo.addClass(myCards.getItemDivId(id), newClass);
                    }
                }
            },

            notif_discard: function(n) {
                var player_id = n.args.player_id;
                var isMe = this.player_id == player_id;
                var myCards = this.tableau[player_id];
                var card_ids = n.args.card_ids;
                if (card_ids) {
                    // Discard visible cards by ID
                    for (var i = 0; i < card_ids.length; i++) {
                        var id = +card_ids[i];
                        myCards.removeFromStockById(id, 'player_boards');
                    }
                } else if (!isMe && n.args.count) {
                    // Discard hidden cards
                    this.removeUnknownCards(myCards, n.args.count, 'player_boards');
                }
            },

            notif_draw: function(n) {
                var player_id = n.args.player_id;
                var isMe = this.player_id == player_id;
                var myCards = this.tableau[player_id];
                var cards = n.args.cards;
                if (cards) {
                    // Draw visible cards by ID
                    for (var i = 0; i < cards.length; i++) {
                        var card = n.args.cards[i];
                        myCards.addToStockWithId(+card.type, +card.id, 'player_boards');
                    }
                } else if (!isMe && n.args.count) {
                    // Draw hidden card(s)
                    this.addUnknownCards(myCards, n.args.count, 'player_boards');
                }
            },
        });
    });
