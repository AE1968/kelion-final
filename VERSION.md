# KELION AI - VERSIUNE CURENTĂ

## ⚠️ CITEȘTE ÎNTÂI ACEST FIȘIER ⚠️

**VERSIUNE ACTIVĂ: v1.0.3**  
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

## TEHNOLOGII FOLOSITE

- **Backend:** PHP 8.2
- **Database:** SQLite3
- **AI:** OpenAI API (GPT-4.1-mini, TTS, STT)
- **Frontend:** HTML5, CSS3, JavaScript, Three.js (hologramă 3D)
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
| `public/assets/hologram3d.js` | Hologramă 3D |

---

## ULTIMA ACTUALIZARE

**Data:** 2026-01-02  
**Modificări recente:**
- Deploy automat configurat
- Parola admin schimbată
- Text "Admin" ascuns din login
- Buton CONTACT adăugat

---

## REGULI PENTRU AI

1. **CITEȘTE ACEST FIȘIER** la începutul fiecărei sesiuni
2. **Versiunea e în config.php**, nu în numele folderului
3. **Nu modifica parola admin** fără aprobare explicită
4. **După orice modificare**, fă `git push` - deploy-ul e automat
5. **Testează pe https://kelionai.app** după deploy
