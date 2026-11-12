// Builds the Word voiceover script from a JSON file written by voice_docx.py.   node voice_docx.js data.json out.docx
const fs = require("fs");
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType, ShadingType, BorderStyle, AlignmentType,
  HeadingLevel, PageBreak, Footer, PageNumber, TabStopType,
} = require("docx");

const data = JSON.parse(fs.readFileSync(process.argv[2], "utf8"));
const COL = { Firoz: "1D4ED8", Urmy: "B45309" };           // blue / amber: easy to tell apart on a printout
const FONT = "Calibri";
const W = 9638;                                              // A4 text width in DXA with 2 cm margins (11906 - 2 * 1134 - rounding)

const t = (text, o = {}) => new TextRun({ text, font: FONT, ...o });
const p = (children, o = {}) => new Paragraph({ children: Array.isArray(children) ? children : [t(children)], ...o });
const thin = { style: BorderStyle.SINGLE, size: 4, color: "CBD5E1" };
const cell = (text, w, o = {}) => new TableCell({
  width: { size: w, type: WidthType.DXA }, margins: { top: 70, bottom: 70, left: 110, right: 110 },
  borders: { top: thin, bottom: thin, left: thin, right: thin },
  shading: o.fill ? { type: ShadingType.CLEAR, fill: o.fill, color: "auto" } : undefined,
  children: [p([t(text, { size: 20, bold: !!o.bold, color: o.color })], { alignment: o.align })],
});
const table = (widths, rows, head = true) => new Table({
  width: { size: widths.reduce((a, b) => a + b, 0), type: WidthType.DXA }, columnWidths: widths,
  rows: rows.map((r, i) => new TableRow({ tableHeader: head && i === 0, children: r.map((c, j) => cell(c, widths[j], { bold: head && i === 0, fill: head && i === 0 ? "E2E8F0" : undefined })) })),
});
const bullet = (text) => new Paragraph({ numbering: { reference: "b", level: 0 }, spacing: { after: 60 }, children: [t(text, { size: 22 })] });

function line(l) {
  const c = COL[l.speaker];
  return [
    p([t(`Line ${l.n}`, { bold: true, size: 22 }), t("   " + l.speaker.toUpperCase(), { bold: true, size: 22, color: c }),
       t(`   starts ${l.start}  (about ${l.dur} s)   file: ${l.file}`, { size: 20, color: "64748B" })],
      { spacing: { before: 200, after: 40 }, keepNext: true }),
    p([t("On screen: " + l.screen, { italics: true, size: 19, color: "64748B" })], { spacing: { after: 60 }, keepNext: true }),
    p([t(l.text, { size: 26 })], { spacing: { after: 100, line: 300 }, indent: { left: 200 },
      border: { left: { style: BorderStyle.SINGLE, size: 24, color: c, space: 8 } } }),
  ];
}

const kids = [];
kids.push(p([t("DRMS Demo Video: Voiceover Script", { bold: true, size: 44 })], { spacing: { after: 60 } }));
kids.push(p([t("Disaster Mapping & Relief Management System  |  Group 3  |  CSE 2206 Database Systems Project", { size: 22, color: "475569" })], { spacing: { after: 60 } }));
kids.push(p([t(`Final video length ${data.total}.  Firoz speaks about ${data.fm} min, Urmy about ${data.um} min.`, { size: 22, color: "475569" })], { spacing: { after: 240 } }));

kids.push(p([t("How to record your voice", { bold: true, size: 30 })], { heading: HeadingLevel.HEADING_1, spacing: { before: 120, after: 100 }, keepNext: true }));
[
  "The video has no sound and no text on screen. Only your voices are added. No camera is needed.",
  "Each person records only their own lines (blue = Firoz, amber = Urmy). Record every line as its own file.",
  "Name the file with the line number and your initial, for example 07_U.m4a or 12_F.wav. Any of m4a, mp3 or wav is fine.",
  "Quiet room, no fan or echo. Phone voice recorder or a headset, about 20 cm from your mouth, the same place and device for every line.",
  "Speak calmly, about 130 words a minute. Leave one second of silence before and after each line. If you stumble, record the whole line again.",
  "Do not read the grey 'On screen' notes. They only tell you what the picture shows while you speak.",
  "Each line is placed at the start time shown. If a line is a little longer than its slot, the picture is held a moment longer, so nothing is cut.",
].forEach(x => kids.push(bullet(x)));

kids.push(p([t("Pronunciation", { bold: true, size: 26 })], { spacing: { before: 200, after: 80 }, keepNext: true }));
kids.push(table([2800, 6838], [["Word", "Say"], ...data.pron]));

kids.push(p([t("Time per part", { bold: true, size: 26 })], { spacing: { before: 240, after: 80 }, keepNext: true }));
kids.push(table([5000, 2300, 2338], [["Part", "Length", "Brief asks"], ...data.parts]));

kids.push(new Paragraph({ children: [new PageBreak()] }));
kids.push(p([t("Full script in video order", { bold: true, size: 34 })], { heading: HeadingLevel.HEADING_1, spacing: { after: 60 }, keepNext: true }));
let part = null;
for (const l of data.lines) {
  if (l.part !== part) {
    part = l.part;
    kids.push(p([t(data.partNames[part], { bold: true, size: 28, color: "0F172A" })], { heading: HeadingLevel.HEADING_2, spacing: { before: 320, after: 40 }, keepNext: true,
      border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: "94A3B8", space: 2 } } }));
  }
  kids.push(...line(l));
}

for (const who of ["Firoz", "Urmy"]) {
  const mine = data.lines.filter(l => l.speaker === who);
  kids.push(new Paragraph({ children: [new PageBreak()] }));
  kids.push(p([t(`${data.full[who]}: only your lines`, { bold: true, size: 34, color: COL[who] })], { heading: HeadingLevel.HEADING_1, spacing: { after: 60 }, keepNext: true }));
  kids.push(p([t(`${mine.length} lines, about ${data[who === "Firoz" ? "fm" : "um"]} minutes of speaking. File names: ${mine.map(l => l.file).join(", ")}.`, { size: 21, color: "475569" })], { spacing: { after: 120 } }));
  for (const l of mine) {
    kids.push(p([t(`${l.n}.  `, { bold: true, size: 24, color: COL[who] }), t(`(starts ${l.start})  `, { size: 19, color: "64748B" }), t(l.text, { size: 26 })],
      { spacing: { before: 140, after: 100, line: 300 }, indent: { left: 360, hanging: 360 } }));
  }
}

const doc = new Document({
  creator: "Group 3", title: "DRMS demo video voiceover script",
  styles: {
    default: { document: { run: { font: FONT, size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true, run: { font: FONT, size: 34, bold: true }, paragraph: { spacing: { before: 240, after: 120 }, keepNext: true, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true, run: { font: FONT, size: 28, bold: true }, paragraph: { spacing: { before: 240, after: 80 }, keepNext: true, outlineLevel: 1 } },
    ],
  },
  numbering: { config: [{ reference: "b", levels: [{ level: 0, format: "bullet", text: "•", alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 540, hanging: 270 } } } }] }] },
  sections: [{
    properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 1134, bottom: 1134, left: 1134, right: 1134 } } },
    footers: { default: new Footer({ children: [p([t("DRMS Group 3 voiceover script   |   page ", { size: 18, color: "94A3B8" }), new TextRun({ children: [PageNumber.CURRENT], font: FONT, size: 18, color: "94A3B8" })], { alignment: AlignmentType.CENTER })] }) },
    children: kids,
  }],
});
Packer.toBuffer(doc).then(b => { fs.writeFileSync(process.argv[3], b); console.log("wrote", process.argv[3]); });
