{OVERALL_GAME_HEADER}

<div id="myactions-wrap">
  <table id="myactions">
    <!-- BEGIN action -->
    <tr class="action action-{action_id}">
      <td class="has-button">
        <div class="bgabutton bgabutton_blue" data-action="{action_id}">{name}</div>
      </td>
      <td>
        {claimHtml} {text} {subtext}
        <div class="blockable">→ {blockHtml}</div>
      </td>
    </tr>
    <!-- END action -->
  </table>
</div>

<div id="placemats">
  <!-- BEGIN player -->
  <div id="placemat_{PLAYER_ID}" class="placemat whiteblock" data-player="{PLAYER_ID}">
    <div class="playername" style="color: #{PLAYER_COLOR}">{PLAYER_NAME}</div>
    <div id="board_{PLAYER_ID}" class="board">
      <div id="cards_{PLAYER_ID}" class="cards"></div>
      <div class="coinarea">
        <div id="coincount_{PLAYER_ID}" class="coincount"></div>
        <div id="coins_{PLAYER_ID}" class="coins"></div>
      </div>
    </div>
  </div>
  <!-- END player -->
</div>

{OVERALL_GAME_FOOTER}
