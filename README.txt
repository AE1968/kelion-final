KELION AI PLATFORM (Single Entry Package)
====================================

Files
-----
- k.php                      : single entry
- config.php                 : config (add OpenAI key here)
- app/                       : backend libs (db/auth/security/openai)
- public/assets/             : futuristic UI (css + hologram)
- storage/                   : sqlite + logs (auto created)

Run locally
-----------
1) Install PHP 8.x
2) In this folder:
   php -S 127.0.0.1:8080
3) Open:
   http://127.0.0.1:8080/k.php

Default accounts
----------------
- demo / demo
- admin / admin1234   (CHANGE FOR PRODUCTION)

Enable OpenAI (you add your API key)
------------------------------------
Edit config.php:
  openai.api_key = "YOUR_KEY"
Or set environment variable:
  OPENAI_API_KEY=...

Voice switching (native per language)
-------------------------------------
There is a Voice dropdown in the App.
Voices are from OpenAI Audio API: alloy, ash, ballad, coral, echo, fable, nova, onyx, sage, shimmer, verse, marin, cedar.

Version + Futuristic clock/date
-------------------------------
- app.version in config.php is shown top-left (HUD)
- time + date update live

Recommendations (next steps)
----------------------------
1) PayPal: enable + implement webhook verification for auto activation
2) SMTP: enable email verification + password reset
3) SMS OTP (Twilio) for stronger login
4) Add admin 2FA + audit log
5) GDPR export/delete
6) Upgrade hologram to 3D GLB (Three.js/Babylon.js) + blendshapes


Age requirement
---------------
- Service is 18+ only. Users must confirm age in Account settings before using the App.
