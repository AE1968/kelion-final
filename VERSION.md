# KELION AI - VERSIUNE CURENTĂ

## ⚠️ CITEȘTE ÎNTÂI ACEST FIȘIER ⚠️

**VERSIUNE ACTIVĂ: v1.0.7**  
**FOLDER LOCAL: k_v1.0.2** (numele folderului NU e versiunea!)  
**SITE LIVE: https://kelionai.app**  
**REPO GITHUB: AE1968/kelion-final**  
**HOSTING: Railway (deploy automat din GitHub)**

---

## CREDENȚIALE ACTIVE

| Serviciu | User | Password |
|----------|------|----------|
| Admin | `admin` | `Andrada_1968!` |
| Demo | `demo` | `demo` |

---

## FUNCȚIONALITĂȚI v1.0.7

### ✅ IMPLEMENTAT
- [x] **Hologramă 3D** - Three.js + GLB model (14MB)
- [x] **Lip Sync** - Web Audio API, morph targets, jaw bone
- [x] **Chat AI** - GPT-4o (OpenAI)
- [x] **TTS** - ElevenLabs + OpenAI fallback
- [x] **STT** - OpenAI Whisper API
- [x] **PayPal Payments** - LIVE mode
- [x] **Bank Transfer** - Template activ
- [x] **Contact Form** - Cu logging și email
- [x] **SMTP Email** - privateemail.com
- [x] **Watchdog System** - Panic mode pentru erori
- [x] **CI/CD** - GitHub Actions → Railway

---

## TEHNOLOGII FOLOSITE

- **Backend:** PHP 8.2
- **Database:** SQLite3
- **AI:** OpenAI API (GPT-4o, TTS, STT), ElevenLabs
- **Frontend:** HTML5, CSS3, JavaScript, Three.js
- **Deploy:** Docker → Railway
- **CI/CD:** GitHub Actions (automat la push pe `main`)

---

## FIȘIERE IMPORTANTE

| Fișier | Descriere |
|--------|-----------|
| `config.php` | Configurări (versiune, API keys, etc.) |
| `k.php` | Controller principal (toate rutele) |
| `app/lib/openai.php` | Integrare OpenAI |
| `app/lib/auth.php` | Autentificare |
| `app/lib/db.php` | Database (SQLite) |
| `public/assets/hologram3d.js` | Hologramă 3D cu Lip Sync |

---

## API ROUTES

| Route | Method | Description |
|-------|--------|-------------|
| `r=home` | GET | Pagina principală cu hologramă |
| `r=login` | GET/POST | Login utilizator |
| `r=app` | GET | Chat AI interface |
| `r=vault` | GET | Istoric conversații |
| `r=admin` | GET | Admin dashboard |
| `r=api_ask` | POST | Chat cu AI |
| `r=api_tts` | POST | Text-to-Speech |
| `r=api_stt` | POST | Speech-to-Text |
| `r=contact_submit` | POST | Formular contact |

---

## ULTIMA ACTUALIZARE

**Data:** 2026-01-03  
**Versiune:** v1.0.7  
**Modificări recente:**
- Fix critical JS syntax error în hologram3d.js
- Lip Sync complet funcțional
- STT (Speech-to-Text) implementat
- Watchdog system cu visual panic mode
- Version display curat (fără sufixe)

---

## REGULI PENTRU AI

1. **CITEȘTE ACEST FIȘIER** la începutul fiecărei sesiuni
2. **Versiunea e în config.php**, nu în numele folderului
3. **Nu modifica parola admin** fără aprobare explicită
4. **După orice modificare**, fă `git push` - deploy-ul e automat
5. **Testează pe https://kelionai.app** după deploy
