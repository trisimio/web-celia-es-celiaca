# Web Celia es Celiaca — Claude Code Reference

> Web de la banda madrileña de pop-rock alternativo/punk Celia es Celiaca.

---

## Quick Reference

| Campo | Valor |
|-------|-------|
| URL | https://celiaesceliaca.com |
| Deploy | Hostinger Business via SSH+rsync — `./deploy.sh` |
| Stack | HTML estatico + CSS + JS vanilla + GSAP CDN |
| Servidor local | `npx serve .` o `python3 -m http.server 8000` |

---

## Estructura

```
web-celia-es-celiaca/
├── index.html              # Single-page con 8 secciones
├── css/style.css           # Design system + secciones + responsive
├── js/app.js               # GSAP, lightbox, nav, form, YouTube lazy
├── assets/images/          # Placeholders (reemplazar con fotos reales)
├── netlify.toml            # Deploy + headers seguridad
├── robots.txt
└── README.md
```

---

## Design System

| Token | Valor | Uso |
|-------|-------|-----|
| --coral | #FBA4A2 | Primario |
| --purple | #8A59F8 | Secundario |
| --magenta | #D946C8 | Acento |
| --black | #08080C | Fondo principal |
| --surface | #111118 | Cards |
| --white | #F0F0F5 | Texto principal |

**Tipografia:** Bebas Neue (headings) + Archivo Black (taglines) + Space Grotesk (body) + JetBrains Mono (labels)

---

## Info banda

- Genero: Pop-rock alternativo / punk
- Tagline: "El hijo bastardo de Kylie Minogue y Motorhead en un dia complicado a mediados de enero"
- Motto: "MUERTE AL PAN!"
- Sello: Subterfuge Records
- Miembros: Celia (voz/guitarra), Bor (guitarra), Jesus Antunez (bateria, ex-Dover), Alvaro Gomez (bajo, ex-Dover), Miguel L. Garrido (guitarra)
- Discos: Kosmos (2016), Despechos de Autor (2019), Melodias EP (2023), Pretendientes Contundentes (2026)

---

## Deploy

```bash
./deploy.sh    # sync delta a Hostinger via rsync over SSH
```

- Host: `u305349901@92.113.28.238:65002`
- Path remoto: `domains/celiaesceliaca.com/public_html/`
- Auth: SSH key `~/.ssh/id_ed25519` (registrada en hPanel > Avanzado > SSH Access)
- Solo sube archivos modificados; `--delete` elimina en remoto lo que no este en local.
- `netlify.toml` se conserva por si se reactiva el deploy alternativo, pero NO se sube.
