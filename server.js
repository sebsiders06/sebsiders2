const path = require("path");
const express = require("express");
const nodemailer = require("nodemailer");
require("dotenv").config();

const app = express();
const PORT = Number(process.env.PORT || 3000);

// Basic in-memory anti-spam throttling by IP.
const requestLog = new Map();
const RATE_LIMIT_WINDOW_MS = 60 * 1000;
const RATE_LIMIT_MAX_REQUESTS = 3;

app.use(express.json({ limit: "100kb" }));
app.use(express.urlencoded({ extended: false, limit: "100kb" }));
app.use(express.static(__dirname));

function getClientIp(req) {
  const forwarded = req.headers["x-forwarded-for"];
  if (typeof forwarded === "string" && forwarded.trim()) {
    return forwarded.split(",")[0].trim();
  }
  return req.ip || req.socket?.remoteAddress || "unknown";
}

function isRateLimited(ip) {
  const now = Date.now();
  const entries = (requestLog.get(ip) || []).filter((time) => now - time < RATE_LIMIT_WINDOW_MS);

  if (entries.length >= RATE_LIMIT_MAX_REQUESTS) {
    requestLog.set(ip, entries);
    return true;
  }

  entries.push(now);
  requestLog.set(ip, entries);
  return false;
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function buildEmailHtml({ nom, email, telephone, sujet, message }) {
  const rows = [
    ["Nom", nom],
    ["Email", email],
    ["Téléphone", telephone || "Non renseigné"],
    ["Sujet", sujet || "Demande de contact"],
    ["Message", escapeHtml(message).replace(/\n/g, "<br>")]
  ];

  const tableRows = rows
    .map(
      ([label, value]) =>
        `<tr><td style="padding:8px;border:1px solid #d0d7de;background:#f8fafc;font-weight:700;width:160px;">${escapeHtml(
          label
        )}</td><td style="padding:8px;border:1px solid #d0d7de;background:#ffffff;">${value}</td></tr>`
    )
    .join("");

  return `<!DOCTYPE html>
  <html lang="fr">
    <body style="margin:0;padding:24px;font-family:Arial,sans-serif;color:#1f2937;background:#ffffff;">
      <h2 style="margin:0 0 16px;font-size:20px;color:#1e3a5f;">Nouvelle demande de contact</h2>
      <p style="margin:0 0 16px;">Une demande a été envoyée depuis le site Formation SST.</p>
      <table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:720px;border:1px solid #d0d7de;">
        ${tableRows}
      </table>
    </body>
  </html>`;
}

function buildEmailText({ nom, email, telephone, sujet, message }) {
  return [
    "Nouvelle demande de contact",
    "",
    `Nom : ${nom}`,
    `Email : ${email}`,
    `Téléphone : ${telephone || "Non renseigné"}`,
    `Sujet : ${sujet || "Demande de contact"}`,
    "",
    "Message :",
    message
  ].join("\n");
}

function validatePayload(body) {
  const nom = String(body.nom || "").trim().slice(0, 200);
  const email = String(body.email || "").trim().slice(0, 254);
  const telephone = String(body.telephone || "").trim().slice(0, 30);
  const sujet = String(body.sujet || "").trim().slice(0, 200);
  const message = String(body.message || "").trim().slice(0, 2000);
  const website = String(body.website || "").trim();

  if (website) {
    return { error: "Envoi bloqué." };
  }
  if (!nom) {
    return { error: "Veuillez indiquer votre nom." };
  }
  if (!email) {
    return { error: "Veuillez indiquer votre adresse email." };
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return { error: "Veuillez saisir une adresse email valide." };
  }
  if (telephone && !/^[0-9+\s().-]{6,30}$/.test(telephone)) {
    return { error: "Veuillez saisir un numéro de téléphone valide." };
  }
  if (!message) {
    return { error: "Veuillez rédiger votre message." };
  }

  return { data: { nom, email, telephone, sujet, message } };
}

function createTransporter() {
  const { SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS } = process.env;

  if (!SMTP_HOST || !SMTP_PORT || !SMTP_USER || !SMTP_PASS) {
    throw new Error("SMTP configuration is incomplete.");
  }

  return nodemailer.createTransport({
    host: SMTP_HOST,
    port: Number(SMTP_PORT),
    secure: String(SMTP_SECURE).toLowerCase() === "true",
    auth: {
      user: SMTP_USER,
      pass: SMTP_PASS
    }
  });
}

app.post("/contact", async (req, res) => {
  const ip = getClientIp(req);

  if (isRateLimited(ip)) {
    return res.status(429).json({
      ok: false,
      message: "Trop de tentatives. Merci de patienter avant de réessayer."
    });
  }

  const { data, error } = validatePayload(req.body || {});
  if (error) {
    return res.status(400).json({ ok: false, message: error });
  }

  const to = process.env.CONTACT_TO || "philippe.clemente@orange.fr";
  const from = process.env.SMTP_FROM || process.env.SMTP_USER;
  const subject = `Demande de contact - ${data.sujet || "Formation SST"}`;

  try {
    const transporter = createTransporter();

    await transporter.sendMail({
      from,
      to,
      replyTo: data.email,
      subject,
      html: buildEmailHtml(data),
      text: buildEmailText(data)
    });

    return res.json({
      ok: true,
      message: "Demande envoyée"
    });
  } catch (error) {
    console.error("Email send error:", error);
    return res.status(500).json({
      ok: false,
      message: "L'envoi a échoué. Merci de réessayer plus tard."
    });
  }
});

app.get("*", (req, res) => {
  if (req.path === "/" || req.path === "/index.html") {
    return res.sendFile(path.join(__dirname, "index.html"));
  }

  const filePath = path.join(__dirname, req.path);
  return res.sendFile(filePath, (error) => {
    if (error) {
      res.status(404).send("Page non trouvée.");
    }
  });
});

app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});
