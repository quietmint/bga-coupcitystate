{OVERALL_GAME_HEADER}
<div id="game_wrap">
  <div id="myactions">
    <div class="actionrowhead">Actions on your turn:</div>
    <div class="actionrow">
      <!-- BEGIN action_any -->
      <div class="action action-{action_id}" data-action="{action_id}">
        <div class="actionhead">{name} {claimHtml}</div>
        <div class="actionwrap">
          <i class="mdi {icon}"></i>
          <div class="actiondesc">{text} {subtext} {blockHtml}</div>
        </div>
      </div>
      <!-- END action_any -->
    </div>
    <div class="actionrow">
      <!-- BEGIN action -->
      <div class="action action-{action_id}" data-action="{action_id}">
        <div class="actionhead">{name} {claimHtml}</div>
        <div class="actionwrap">
          <i class="mdi {icon}" style="color: {color}"></i>
          <div class="actiondesc">{text} {subtext} {blockHtml}</div>
        </div>
      </div>
      <!-- END action -->
    </div>
  </div>

  <div id="circle" class="circle-{player_count}">
    <div id="deck" class="oncircle" data-action="6">
      <div id="deckcount">Deck ({deck_count})</div>
      <div class="card"></div>
    </div>
    <!-- BEGIN player -->
    <div class="oncircle player-{index}">
      <div id="placemat_{player_id}" class="placemat player-{index}" style="color: #{player_color}" data-player="{player_id}">
        <div class="playerhead">
          <div id="balloon_{player_id}" class="balloon"></div>
          <div id="wealth_{player_id}" class="wealth">₤0</div>
          <div class="playername">{player_name}</div>
        </div>
        <div id="cards_{player_id}" class="cards"></div>
      </div>
    </div>
    <!-- END player -->
  </div>
</div>
{OVERALL_GAME_FOOTER}
