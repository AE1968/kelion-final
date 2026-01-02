# KELION AI v1.0.3 - STRUCTURA COMPLETĂ A PROIECTULUI

## DATA ACTUALIZARE: 2026-01-02

---

## 📁 ARBORELE DE FIȘIERE

```
k_v1.0.2/                          # FOLDER PRINCIPAL (versiunea e v1.0.3!)
│
├── 📄 VERSION.md                  # ⚠️ CITEȘTE ÎNTÂI - Info versiune pentru AI
├── 📄 CERINTE_v1.0.3.md           # Cerințe vs Implementare
├── 📄 STRUCTURE.md                # ACEST FIȘIER
├── 📄 TODO_v1.0.3.md              # Lista de task-uri
│
├── 📄 config.php                  # ⭐ CONFIGURĂRI PRINCIPALE
├── 📄 index.php                   # Entry point → redirect la k.php
├── 📄 k.php                       # ⭐ CONTROLLER PRINCIPAL (toate rutele)
│
├── 📄 Dockerfile                  # Container Docker pentru Railway
├── 📄 railway.toml                # Configurări Railway deploy
├── 📄 .htaccess                   # Configurări Apache
├── 📄 .gitignore                  # Fișiere ignorate de Git
│
├── 📄 health.php                  # Health check endpoint (/health.php)
├── 📄 hologram.glb                # Model 3D hologramă (14MB)
│
├── 📁 .github/
│   └── 📁 workflows/
│       └── 📄 kelion-final.yml    # GitHub Actions - Deploy automat
│
├── 📁 app/                        # ⭐ BACKEND LOGIC
│   ├── 📄 bootstrap.php           # Inițializare aplicație
│   │
│   ├── 📁 lib/                    # LIBRĂRII
│   │   ├── 📄 auth.php            # Autentificare (login/logout/sessions)
│   │   ├── 📄 db.php              # Database SQLite (scheme, queries)
│   │   ├── 📄 mailer.php          # Email (SMTP) - MINIMAL
│   │   ├── 📄 openai.php          # OpenAI API (chat, TTS)
│   │   ├── 📄 security.php        # CSRF, rate limiting, sanitizare
│   │   └── 📄 util.php            # Funcții helper
│   │
│   └── 📁 views/
│       └── 📄 layout.php          # Template HTML (header/footer)
│
├── 📁 public/                     # ⭐ FRONTEND
│   ├── 📁 assets/
│   │   ├── 📄 hologram.js         # Hologramă 2D (vechi)
│   │   ├── 📄 hologram3d.js       # ⭐ Hologramă 3D (Three.js)
│   │   └── 📄 style.css           # Stiluri CSS
│   │
│   ├── 📁 Textures/
│   │   ├── 📄 MaleHeadHolo_Color.png
│   │   ├── 📄 MaleHeadHolo_Opacity.png
│   │   └── 📄 MaleHead_Realistic.png
│   │
│   └── 📄 eye_texture.png
│
└── 📁 storage/                    # Creat automat de Docker
    └── kelion.sqlite              # Baza de date SQLite
```

---

## 📄 DESCRIERE DETALIATĂ FIȘIERE

### 🔵 FIȘIERE PRINCIPALE

| Fișier | Rol | Ce face |
|--------|-----|---------|
| `config.php` | Configurări | API keys, versiune, DB path, SMTP, PayPal, securitate |
| `k.php` | Controller | Toate rutele aplicației (home, login, app, admin, etc.) |
| `index.php` | Entry | `<?php require 'k.php';` - redirect simplu |

### 🔵 APP/LIB - BACKEND

| Fișier | Rol | Funcții principale |
|--------|-----|-------------------|
| `bootstrap.php` | Init | Încarcă config, pornește sesiune, conectează DB |
| `auth.php` | Autentificare | `login_attempt()`, `logout_now()`, `current_user()`, `require_login()`, `require_admin()` |
| `db.php` | Database | `db()` singleton, `ensure_schema()` - creează tabele |
| `openai.php` | AI | `openai_answer()` - chat, `openai_tts_mp3()` - voce |
| `security.php` | Securitate | `csrf_token()`, `csrf_check()`, `rate_limit_hit()` |
| `mailer.php` | Email | `send_mail()` - SMTP (dezactivat) |
| `util.php` | Helper | `h()` escape HTML, `asset()` URL assets |

### 🔵 PUBLIC - FRONTEND

| Fișier | Rol | Ce face |
|--------|-----|---------|
| `hologram3d.js` | 3D Engine | Three.js - încarcă GLB, animații, efecte glow |
| `style.css` | Stiluri | Design futurist, culori cyan/magenta |

---

## 🛤️ RUTE DISPONIBILE (k.php)

| Rută | URL | Acces | Descriere |
|------|-----|-------|-----------|
| `home` | `/k.php` sau `/k.php?r=home` | Public | Pagină principală cu hologramă |
| `login` | `/k.php?r=login` | Public | Formular login |
| `login_post` | POST | Public | Procesare login |
| `logout` | POST | Auth | Delogare |
| `app` | `/k.php?r=app` | Subscription | Chat AI cu hologramă |
| `vault` | `/k.php?r=vault` | Subscription | Istoric conversații |
| `vault_view` | `/k.php?r=vault_view&id=X` | Subscription | Vezi o conversație |
| `reconnect` | `/k.php?r=reconnect` | Auth | Reactivare subscription |
| `pay_bank` | `/k.php?r=pay_bank&sid=X` | Auth | Detalii plată bancară |
| `admin` | `/k.php?r=admin` | Admin | Dashboard admin |
| `admin_create_user` | POST | Admin | Creare user nou |
| `admin_confirm_bank` | POST | Admin | Confirmare plată bancară |
| `account` | `/k.php?r=account` | Auth | Setări cont, GDPR |
| `privacy` | `/k.php?r=privacy` | Public | Politică confidențialitate |
| `terms` | `/k.php?r=terms` | Public | Termeni și condiții |
| `safety` | `/k.php?r=safety` | Public | Politică siguranță |
| `gdpr_export` | `/k.php?r=gdpr_export` | Auth | Export date personale |

### API Endpoints (JSON)

| Rută | Metodă | Descriere |
|------|--------|-----------|
| `api_set_voice` | POST | Setează vocea TTS preferată |
| `api_ask` | POST | Trimite mesaj la AI, primește răspuns |
| `api_tts` | POST | Convertește text în MP3 (voce) |

---

## 🗄️ STRUCTURA DATABASE (SQLite)

### Tabele

| Tabel | Descriere |
|-------|-----------|
| `users` | Utilizatori (id, username, email, passhash, role, etc.) |
| `plans` | Planuri subscription (id, name, price, duration) |
| `subscriptions` | Abonamente active (user_id, plan_id, status, dates) |
| `payments` | Plăți (user_id, amount, method, status) |
| `conversations` | Conversații AI (user_id, title) |
| `conversation_messages` | Mesaje (conversation_id, role, text) |
| `consents` | Consimțăminte GDPR |
| `traffic_events` | Log vizualizări |

---

## 🔐 FLUXURI UTILIZATOR

### 1. LOGIN FLOW
```
[Home] → [Click LOGIN] → [Login Page] → [Enter credentials] → [POST login_post]
    ↓ success                                                        ↓ fail
[Redirect to /app]                                           [Show error message]
```

### 2. CHAT AI FLOW
```
[App Page] → [Type message] → [Click SEND] → [POST api_ask]
                                                  ↓
                                        [OpenAI processes]
                                                  ↓
                                        [Display response]
                                                  ↓
                                        [POST api_tts] → [Play audio]
```

### 3. SUBSCRIPTION FLOW
```
[User without subscription] → [Redirect to /reconnect]
       ↓
[Choose plan] → [Choose payment method]
       ↓                    ↓
[Bank Transfer]        [PayPal - disabled]
       ↓
[Show bank details + reference code]
       ↓
[Admin confirms in /admin] → [Subscription activated]
```

### 4. ADMIN FLOW
```
[Login as admin] → [Go to /admin]
       ↓
[Dashboard: users list, traffic stats]
       ↓
[Create new user] or [Confirm bank payment]
```

---

## 🔧 VARIABILE DE MEDIU (Railway)

| Variable | Descriere |
|----------|-----------|
| `OPENAI_API_KEY` | API key OpenAI |
| `PAYPAL_CLIENT_ID` | PayPal Client ID |
| `PAYPAL_CLIENT_SECRET` | PayPal Secret |
| `PAYPAL_WEBHOOK_ID` | PayPal Webhook |

---

## ❌ CE NU EXISTĂ ÎN COD

1. **STT (Speech-to-Text)** - Nu e implementat, doar browser Web Speech API
2. **Lip Sync** - Hologramul nu sincronizează buzele cu vocea
3. **Email notifications** - SMTP dezactivat
4. **PayPal integration** - Dezactivat în config
5. **Password reset** - Nu există forgot password
6. **Registration** - Nu există self-registration (doar admin crează useri)

---

## 📝 NOTE PENTRU AI

1. **Versiunea curentă: v1.0.3** (verifică config.php)
2. **Deploy automat** - orice push pe `main` triggerează Railway
3. **Testează pe https://kelionai.app** după modificări
4. **Parola admin: Andrada_1968!** (schimbată din admin1234)
