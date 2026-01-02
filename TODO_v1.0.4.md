# KELION AI - TODO LIST
## Versiune: v1.0.4
## Data: 2026-01-02

---

## ✅ IMPLEMENTAT (Sesiunea curentă)

### v1.0.4 - Funcționalități noi
- [x] **Lip Sync** - Sincronizare gură hologramă cu audio TTS
  - Web Audio API pentru analiză în timp real
  - Animație morph targets / jaw bone
  - Efecte vizuale glow bazate pe intensitate voce
- [x] **STT (Speech-to-Text)** - OpenAI API
  - MediaRecorder pentru înregistrare audio
  - Backend API endpoint `/api_stt`
  - Fallback la browser SpeechRecognition
- [x] **Contact Form** - Procesare completă
  - Validare email/mesaj
  - Logging în `storage/contact_log.txt`
  - Notificare email (când SMTP e activat)
- [x] **Mailer îmbunătățit** - SMTP + logging

### v1.0.3 - Sesiune anterioară
- [x] Deploy automat CI/CD pe Railway
- [x] Parola admin schimbată în `Andrada_1968!`
- [x] Text "Admin: admin1234" ascuns din Login
- [x] Buton CONTACT pe pagina principală

---

## 🟡 DE CONFIGURAT (Necesită date de la user)

| Element | Status | Ce trebuie |
|---------|--------|------------|
| **SMTP Email** | ⚙️ Dezactivat | Host, User, Password |
| **PayPal** | ⚙️ Dezactivat | Client ID, Secret |
| **Bank Transfer** | ⚠️ Incomplet | IBAN, Sort Code, Account Number |
| **OpenAI API Key** | ✅ Configurat | Variabilă Railway |

---

## 🔵 DE TESTAT PE LIVE

- [ ] Login cu `admin` / `Andrada_1968!`
- [ ] Login cu `demo` / `demo`  
- [ ] Chat AI (trimite mesaj → primește răspuns)
- [ ] TTS (ascultă vocea AI)
- [ ] STT (buton Dictate → vorbește → text apare)
- [ ] Lip Sync (observă hologramul când vorbește)
- [ ] Contact Form (trimite mesaj → verifică log)
- [ ] Vault (istoric conversații)
- [ ] Admin Dashboard

---

## 📋 CREDENȚIALE

| Serviciu | User | Password |
|----------|------|----------|
| Admin | `admin` | `Andrada_1968!` |
| Demo | `demo` | `demo` |

---

## 📁 FIȘIERE MODIFICATE

```
k_v1.0.2/
├── config.php              # v1.0.4
├── k.php                   # +API endpoints (api_stt, contact_submit)
├── app/lib/
│   ├── openai.php          # +openai_stt() function
│   └── mailer.php          # Complet rescris cu SMTP
└── public/assets/
    └── hologram3d.js       # +Lip Sync system
```

---

## ❓ ÎNTREBĂRI RĂMASE

1. **Self-Registration** - Utilizatorii să se poată înregistra singuri? (Acum doar admin creează)
2. **Password Reset** - Forgot password flow? (Nu există încă)
3. **Lip Sync pe pagina App** - Hologramul 2D din App nu are lip sync (doar cel 3D din Home)

---

## URMĂTORII PAȘI

1. `git add . && git commit && git push` → Deploy automat
2. Testare pe https://kelionai.app
3. Configurare SMTP dacă e necesar
4. Configurare PayPal dacă e necesar
5. Self-registration și Password Reset (dacă solicitat)
