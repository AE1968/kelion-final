<?php
function page_header(string $title): void {
  global $CONFIG;
  $u = current_user();
  $name = $u ? h($u['username']).' <span class="pill">'.h($u['role']).'</span>' : 'Guest';
  $ver = h((string)($CONFIG['app']['version'] ?? 'v1'));

  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>'.h($title).' - KELION AI</title>';
  echo '<link rel="stylesheet" href="'.h(asset('public/assets/style.css')).'">';
  echo '</head><body>';

  echo '<div class="hud">';
  echo '<div class="hudLeft"><div class="hudTitle">KELION AI</div><div class="hudMeta">'.$ver.' • <span id="hudDate"></span></div></div>';
  echo '<div class="hudClock"><div class="clockFace"><span id="hudTime"></span></div></div>';
  echo '</div>';

  echo '<header class="topbar"><div class="brand">KELION<span>AI</span></div><div class="who">'.$name.'</div></header>';

  echo '<script>
  (function(){
    function pad(n){ return String(n).padStart(2,"0"); }
    function tick(){
      const d = new Date();
      const days=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
      const months=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
      const t = pad(d.getHours())+":"+pad(d.getMinutes())+":"+pad(d.getSeconds());
      const date = days[d.getDay()]+" "+pad(d.getDate())+" "+months[d.getMonth()]+" "+d.getFullYear();
      const elT=document.getElementById("hudTime"); if(elT) elT.textContent=t;
      const elD=document.getElementById("hudDate"); if(elD) elD.textContent=date;
    }
    tick(); setInterval(tick, 250);
  })();
  </script>';
}
function page_footer(): void {
  echo '<footer class="footer">KELION AI • Futuristic Hologram Platform • <a href="k.php?r=privacy">Privacy & GDPR</a> • <a href="k.php?r=terms">Terms</a> • <a href="k.php?r=safety">Safety</a></footer></body></html>';
}
