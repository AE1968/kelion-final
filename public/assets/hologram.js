// Procedural hologram (canvas). Replace with real 3D later.
window.HOLOGRAM = window.HOLOGRAM || { state:"idle", emotion:"calm", voiceLevel:0, lookX:0, lookY:0, skinHue:120, subtone:"neutral", focus:0 };

export function initHologram(canvas){
  const ctx = canvas.getContext("2d");
  const H = window.HOLOGRAM;

  function fit(){
    const r = canvas.getBoundingClientRect();
    canvas.width = Math.floor(r.width * devicePixelRatio);
    canvas.height = Math.floor(r.height * devicePixelRatio);
    ctx.setTransform(devicePixelRatio,0,0,devicePixelRatio,0,0);
  }
  window.addEventListener("resize", fit); fit();

  document.addEventListener("mousemove", e=>{
    const r = canvas.getBoundingClientRect();
    H.lookX = ((e.clientX - r.left)/r.width - .5) * 20;
    H.lookY = ((e.clientY - r.top)/r.height - .5) * 20;
  });

  let t=0, blink=0, blinkT=120+Math.random()*220;
  let microTwitch=0, microTimer=180+Math.random()*300;
  let eyeDriftX=0, eyeDriftY=0;

  function draw(){
    t += 0.016;
    blinkT--;
    if(blinkT<0){ blink=1; blinkT=120+Math.random()*220; }
    blink=Math.max(0, blink-0.12);

    microTimer--;
    if(microTimer<0){
      microTwitch=1;
      eyeDriftX=(Math.random()-0.5)*4;
      eyeDriftY=(Math.random()-0.5)*3;
      microTimer=180+Math.random()*300;
    }
    microTwitch=Math.max(0,microTwitch-0.12);

    const w = canvas.getBoundingClientRect().width;
    const h = canvas.getBoundingClientRect().height;
    ctx.clearRect(0,0,w,h);

    let hue = H.skinHue ?? 120;
    if (H.emotion==="active") hue = (hue + 20) % 360;
    if (H.emotion==="alert") hue = (hue + 60) % 360;

    const focus = Math.max(0, Math.min(1, H.focus || 0));
    if (focus > 0.6) blink *= 0.55;
    microTwitch *= (1 - focus*0.7);

    const glow = ctx.createRadialGradient(w*0.5,h*0.42,40,w*0.5,h*0.45,260);
    glow.addColorStop(0,`hsla(${hue},90%,60%,0.30)`);
    glow.addColorStop(1,"rgba(0,0,0,0)");
    ctx.fillStyle = glow;
    ctx.fillRect(0,0,w,h);

    const bob = Math.sin(t*2)*4;

    ctx.save();
    ctx.translate(w*0.5, h*0.42 + bob);

    const skin = ctx.createRadialGradient(0,-40,40,0,0,170);
    skin.addColorStop(0,`hsla(${hue},80%,65%,0.88)`);
    skin.addColorStop(1,`hsla(${(hue-35+360)%360},70%,28%,0.88)`);
    ctx.fillStyle = skin;
    ctx.beginPath();
    ctx.ellipse(0,0,95,125,0,0,Math.PI*2);
    ctx.fill();

    ctx.globalAlpha = 0.14;
    ctx.strokeStyle = `hsla(${hue},90%,85%,0.9)`;
    for(let y=-80;y<90;y+=14){
      for(let x=-60;x<60;x+=14){
        ctx.beginPath();
        ctx.arc(x+(y%28),y,5,0,Math.PI*2);
        ctx.stroke();
      }
    }
    ctx.globalAlpha = 1;

    const eyeOpen = Math.max(0.05, 1 - blink);
    const ex=35, ey=-20;

    function eye(side){
      ctx.save();
      ctx.translate(
        side*ex + (H.lookX||0) + eyeDriftX,
        ey + (H.lookY||0) + eyeDriftY - microTwitch*2
      );

      ctx.fillStyle="rgba(230,255,240,0.92)";
      ctx.beginPath();
      ctx.ellipse(0,0,18,12*eyeOpen,0,0,Math.PI*2);
      ctx.fill();

      ctx.fillStyle=`hsla(${(hue+90)%360},90%,60%,0.95)`;
      ctx.beginPath();
      ctx.ellipse(0,0,7,9*eyeOpen,0,0,Math.PI*2);
      ctx.fill();

      let pupilBase=2.2;
      if (H.state==="listening") pupilBase=3.2;
      if (H.state==="thinking") pupilBase=1.8;
      if (H.state==="speaking") pupilBase=2.8;
      if (H.emotion==="alert") pupilBase += 0.8;
      if (H.emotion==="active") pupilBase += 0.4;
      pupilBase += (H.voiceLevel||0)*2.2;

      ctx.fillStyle="#031b12";
      ctx.beginPath();
      ctx.ellipse(0,0,pupilBase,(9+(H.voiceLevel||0)*6)*eyeOpen,0,0,Math.PI*2);
      ctx.fill();

      ctx.strokeStyle=`hsla(${(hue+90)%360},90%,70%,0.55)`;
      ctx.stroke();
      ctx.restore();
    }
    eye(-1); eye(1);

    const lvl = Math.max(0, Math.min(1, H.voiceLevel||0));
    let tension=0;
    if (H.state==="thinking") tension=-2;
    if (H.emotion==="alert") tension=3;

    const m = 8 + lvl*28 + tension + microTwitch*2;
    ctx.strokeStyle="rgba(230,255,240,0.88)";
    ctx.beginPath();
    ctx.ellipse(0,45,22+m*0.35,6+m*0.15,0,0,Math.PI*2);
    ctx.stroke();

    ctx.globalAlpha = 0.45;
    ctx.strokeStyle = "rgba(234,240,255,.65)";
    ctx.beginPath();
    ctx.moveTo(0, -4);
    ctx.quadraticCurveTo(6, 18, 0, 28);
    ctx.stroke();
    ctx.globalAlpha = 1;

    if (H.state==="thinking"){
      ctx.globalAlpha=0.65;
      ctx.fillStyle=`hsla(${hue},90%,70%,0.6)`;
      for(let i=0;i<3;i++){
        ctx.beginPath();
        ctx.arc(-18+i*18, 70, 3+Math.sin(t*6+i)*1.2, 0, Math.PI*2);
        ctx.fill();
      }
      ctx.globalAlpha=1;
    }

    ctx.restore();
    requestAnimationFrame(draw);
  }
  draw();
}
