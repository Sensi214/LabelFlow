import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import multer from "multer";
import crypto from "crypto";
import sharp from "sharp";
import Replicate from "replicate";

dotenv.config();

const app = express();
const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 15 * 1024 * 1024 } });

app.use(cors({
  origin: process.env.ALLOWED_ORIGIN || "*",
  methods: ["GET", "POST"],
  allowedHeaders: ["Content-Type", "x-wp-secret"]
}));

app.use(express.json({ limit: "20mb" }));

const replicate = new Replicate({
  auth: process.env.REPLICATE_API_TOKEN
});

function checkSecret(req, res, next) {
  const required = process.env.WP_SECRET_KEY;
  if (!required) return next();
  const provided = req.headers["x-wp-secret"];
  if (provided !== required) {
    return res.status(401).json({ error: "Unauthorized" });
  }
  next();
}

app.get("/", (req, res) => {
  res.json({
    ok: true,
    name: "Uniq LabelFlow Render Backend",
    routes: [
      "POST /generate-label-copy",
      "POST /generate-mockup",
      "POST /resize-label",
      "POST /archive-label"
    ]
  });
});

app.post("/generate-label-copy", checkSecret, async (req, res) => {
  const {
    brand = "SENSI Candle Co",
    scent = "Lavender Dream",
    style = "luxury boutique",
    notes = "calming floral candle"
  } = req.body || {};

  const result = {
    brand,
    scent,
    headline: scent,
    shortDescription: `A ${style} candle experience crafted with ${notes}.`,
    labelDescription: `${scent} is designed for a refined atmosphere, blending premium presentation with a memorable scent story.`,
    warning: "Burn within sight. Keep away from children, pets, drafts, and flammable objects. Trim wick to 1/4 inch before lighting.",
    suggestedTags: ["candle", "soy candle", scent.toLowerCase(), "hand poured", "gift candle"],
    suggestedStyle: {
      palette: ["warm cream", "charcoal", "soft gold"],
      fontDirection: "Luxury serif headline with clean sans-serif body text",
      layout: "Centered brand, bold scent name, small warning footer"
    }
  };

  res.json({ success: true, result });
});

app.post("/generate-mockup", checkSecret, upload.single("label"), async (req, res) => {
  try {
    const prompt = req.body?.prompt || "realistic luxury candle jar mockup, boutique product photography, soft studio lighting";
    const model = process.env.REPLICATE_MOCKUP_MODEL || "black-forest-labs/flux-schnell";

    const output = await replicate.run(model, {
      input: { prompt }
    });

    res.json({ success: true, model, output });
  } catch (err) {
    res.status(500).json({ error: "Replicate mockup generation failed", details: err.message });
  }
});

app.post("/resize-label", checkSecret, upload.single("label"), async (req, res) => {
  try {
    if (!req.file) return res.status(400).json({ error: "Missing label file." });

    const width = parseInt(req.body.width || "700", 10);
    const height = parseInt(req.body.height || "600", 10);

    const resized = await sharp(req.file.buffer)
      .resize(width, height, { fit: "contain", background: "#ffffff" })
      .png()
      .toBuffer();

    const hash = crypto.createHash("sha256").update(resized).digest("hex");

    res.json({
      success: true,
      width,
      height,
      sha256: hash,
      imageBase64: `data:image/png;base64,${resized.toString("base64")}`
    });
  } catch (err) {
    res.status(500).json({ error: "Resize failed", details: err.message });
  }
});

app.post("/archive-label", checkSecret, upload.single("label"), async (req, res) => {
  try {
    if (!req.file) return res.status(400).json({ error: "Missing label file." });

    const {
      labelName = "Candle Label",
      creator = "Uniq LabelFlow User",
      category = "Custom",
      size = "700x600"
    } = req.body || {};

    const sha256 = crypto.createHash("sha256").update(req.file.buffer).digest("hex");
    const timestamp = new Date().toISOString();

    res.json({
      success: true,
      record: {
        labelName,
        creator,
        category,
        size,
        timestamp,
        sha256,
        filename: req.file.originalname,
        metadata: {
          claimOfAuthorship: creator,
          firstPublicationDate: timestamp.slice(0, 10),
          generatedBy: "Uniq LabelFlow"
        }
      }
    });
  } catch (err) {
    res.status(500).json({ error: "Archive failed", details: err.message });
  }
});

const port = process.env.PORT || 3000;
app.listen(port, () => {
  console.log(`Uniq LabelFlow backend running on port ${port}`);
});
