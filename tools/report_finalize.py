"""
Final edit of the team's report (DRMS_Final_Report .docx) -> submission/report/DRMS_Final_Report.docx.

    py -3 tools/report_finalize.py

What it changes: cover typos and member contributions, preface with the team table, screenshots in chapter 3, a landscape page for the
ERD, sample-row counts, one schema fact (relief_distribution.Batch_ID is NOT NULL), chapter 7 rebuilt from the real sample data
(submission/report/Query_Log.md + the constraint tests + the video operations), chapter 8 and the appendices, plain wording.
The original file is never written.
"""
from __future__ import annotations

import copy
import json
import re
import struct
import zipfile
from pathlib import Path

from lxml import etree

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "DRMS_Final_Report .docx"
OUT = ROOT / "submission" / "report" / "DRMS_Final_Report.docx"
SHOTS = ROOT / "submission" / "report" / "screenshots"
WORK = ROOT / "Voice Clip" / "_work"

NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
      "wp": "http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing",
      "a": "http://schemas.openxmlformats.org/drawingml/2006/main",
      "pic": "http://schemas.openxmlformats.org/drawingml/2006/picture",
      "r": "http://schemas.openxmlformats.org/officeDocument/2006/relationships"}
W = "{%s}" % NS["w"]
XMLNS = " ".join(f'xmlns:{k}="{v}"' for k, v in NS.items())
CODE_FONT = '<w:rFonts w:ascii="Courier New" w:eastAsia="Courier New" w:hAnsi="Courier New" w:cs="Courier New"/>'


def X(xml: str) -> etree._Element:
    return etree.fromstring(f"<root {XMLNS}>{xml}</root>")[0]


def tag(name: str) -> str:
    return W + name


# ------------------------------------------------------------------------------------------------ paragraph helpers
def strip_marks(p) -> None:
    for e in p.findall(tag("bookmarkStart")) + p.findall(tag("bookmarkEnd")):
        p.remove(e)


def set_text(p, text: str) -> None:
    """Replace the text of a paragraph, keeping its properties, bookmarks and the first text run's look."""
    first = None
    for r in p.findall(tag("r")):
        if first is None and r.find(tag("t")) is not None:
            first = r
        else:
            p.remove(r)
    if first is None:
        first = etree.SubElement(p, tag("r"))
    for e in first.findall(tag("lastRenderedPageBreak")) + first.findall(tag("t"))[1:]:
        first.remove(e)
    t = first.find(tag("t"))
    if t is None:
        t = etree.SubElement(first, tag("t"))
    t.text = text
    t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")


def rich(p, text: str, size: int = 22) -> None:
    """Set paragraph text; `back-ticked` parts become Courier New like the inline code of the original."""
    for r in p.findall(tag("r")):
        p.remove(r)
    for i, part in enumerate(re.split(r"`", text)):
        if not part:
            continue
        rpr = f'<w:rPr>{CODE_FONT}<w:sz w:val="{size}"/><w:szCs w:val="{size}"/></w:rPr>' if i % 2 else ""
        r = X(f'<w:r>{rpr}<w:t xml:space="preserve"></w:t></w:r>')
        r.find(tag("t")).text = part
        p.append(r)


def para(tpl, text: str, code: bool = False):
    c = copy.deepcopy(tpl)
    strip_marks(c)
    for e in c.findall(tag("r")):
        c.remove(e)
    rich(c, text)
    return c


def code_para(tpl, line: str, last: bool):
    c = copy.deepcopy(tpl)
    set_text(c, line)
    rpr = c.find(tag("r")).find(tag("rPr"))
    for sz in rpr.findall(tag("sz")) + rpr.findall(tag("szCs")):
        sz.set(tag("val"), "16")
    if last:
        kn = c.find(tag("pPr")).find(tag("keepNext"))
        if kn is not None:
            c.find(tag("pPr")).remove(kn)
    return c


def keep_next(p):
    ppr = p.find(tag("pPr"))
    if ppr.find(tag("keepNext")) is None:
        ppr.insert(0, etree.Element(tag("keepNext")))
    return p


def after(ref, *els):
    for e in els:
        ref.addnext(e)
        ref = e
    return ref


# ------------------------------------------------------------------------------------------------ table helpers
def cell_xml(w_: int, paras: list[str], sz: int, header: bool, fill: str | None = None, bold: bool = False, borders: bool = True,
             align: str | None = None) -> str:
    bd = ('<w:tcBorders>' + "".join(f'<w:{s} w:val="single" w:sz="4" w:space="0" w:color="8C8C8C"/>' for s in ("top", "left", "bottom", "right"))
          + "</w:tcBorders>") if borders else ""
    shd = f'<w:shd w:val="clear" w:color="auto" w:fill="{fill}"/>' if fill else ""
    ps = ""
    for t in paras:
        rpr = ("<w:b/><w:bCs/>" if (header or bold) else "") + ('<w:color w:val="FFFFFF"/>' if header else "") + f'<w:sz w:val="{sz}"/><w:szCs w:val="{sz}"/>'
        jc = f'<w:jc w:val="{align}"/>' if align else ""
        ps += (f'<w:p><w:pPr><w:spacing w:after="20" w:line="252" w:lineRule="auto"/>{jc}</w:pPr><w:r><w:rPr>{rpr}</w:rPr>'
               f'<w:t xml:space="preserve">{esc(t)}</w:t></w:r></w:p>')
    return (f'<w:tc><w:tcPr><w:tcW w:w="{w_}" w:type="dxa"/>{bd}{shd}<w:tcMar><w:top w:w="50" w:type="dxa"/><w:left w:w="90" w:type="dxa"/>'
            f'<w:bottom w:w="50" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tcMar><w:vAlign w:val="center"/></w:tcPr>{ps}</w:tc>')


def esc(s: str) -> str:
    return s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")


def table(widths: list[int], header: list[str] | None, rows: list[list], sz: int = 20, first_col_bold: bool = False):
    """rows: list of rows; a cell is a string or a list of strings (several paragraphs)."""
    grid = "".join(f'<w:gridCol w:w="{x}"/>' for x in widths)
    tr = ""
    if header:
        cells = "".join(cell_xml(w_, [h], sz, True, "1F3864") for w_, h in zip(widths, header))
        tr += f'<w:tr><w:trPr><w:cantSplit/><w:tblHeader/></w:trPr>{cells}</w:tr>'
    for row in rows:
        cells = "".join(cell_xml(w_, c if isinstance(c, list) else [c], sz, False, bold=(first_col_bold and i == 0))
                        for i, (w_, c) in enumerate(zip(widths, row)))
        tr += f'<w:tr><w:trPr><w:cantSplit/></w:trPr>{cells}</w:tr>'
    return X(f'<w:tbl><w:tblPr><w:tblW w:w="{sum(widths)}" w:type="dxa"/><w:tblBorders>'
             + "".join(f'<w:{s} w:val="single" w:sz="4" w:space="0" w:color="auto"/>' for s in ("top", "left", "bottom", "right", "insideH", "insideV"))
             + f'</w:tblBorders><w:tblLayout w:type="fixed"/><w:tblCellMar><w:left w:w="10" w:type="dxa"/><w:right w:w="10" w:type="dxa"/></w:tblCellMar>'
               f'<w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/></w:tblPr>'
               f'<w:tblGrid>{grid}</w:tblGrid>{tr}</w:tbl>')


def set_cell(tc, text: str) -> None:
    set_text(tc.findall(tag("p"))[0], text)


def cell_texts(tr) -> list[str]:
    return ["".join(t.text or "" for t in tc.iter(tag("t"))) for tc in tr.findall(tag("tc"))]


# ------------------------------------------------------------------------------------------------ pictures
def png_size(path: Path) -> tuple[int, int]:
    with open(path, "rb") as f:
        head = f.read(24)
    return struct.unpack(">II", head[16:24])


def picture(tpl, rid: str, cx: int, cy: int, did: int, name: str, descr: str):
    p = copy.deepcopy(tpl)
    for e in p.iter():
        if e.tag == "{%s}extent" % NS["wp"] or e.tag == "{%s}ext" % NS["a"]:
            e.set("cx", str(cx)); e.set("cy", str(cy))
        elif e.tag == "{%s}docPr" % NS["wp"]:
            e.set("id", str(did)); e.set("name", name); e.set("descr", descr)
            e.attrib.pop("title", None)
        elif e.tag == "{%s}blip" % NS["a"]:
            e.set("{%s}embed" % NS["r"], rid)
    return p


# ------------------------------------------------------------------------------------------------ the edit
def main() -> None:
    zin = zipfile.ZipFile(SRC)
    doc = etree.fromstring(zin.read("word/document.xml"))
    rels = etree.fromstring(zin.read("word/_rels/document.xml.rels"))
    settings = etree.fromstring(zin.read("word/settings.xml"))
    update_fields = settings.find(tag("updateFields"))
    if update_fields is None:
        update_fields = etree.SubElement(settings, tag("updateFields"))
    update_fields.set(tag("val"), "true")
    body = doc.find(tag("body"))
    K = list(body)
    team = json.load(open(ROOT / "submission" / "team.json", encoding="utf-8"))
    mem = {m["name"]: m for m in team["members"]}
    U, F = mem["Archy Mondal Urmy"], mem["Taohiduzzaman Firoz"]

    # templates (deep copies taken before anything is removed)
    T_BODY, T_BULLET, T_CODE = copy.deepcopy(K[34]), copy.deepcopy(K[44]), copy.deepcopy(K[262])
    T_H1, T_H2, T_CAP, T_PIC = copy.deepcopy(K[30]), copy.deepcopy(K[96]), copy.deepcopy(K[69]), copy.deepcopy(K[56])
    SECT = K[-1]

    def body_p(text):
        return para(T_BODY, text)

    def bullet(text):
        return para(T_BULLET, text)

    def h1(text):
        return para(T_H1, text)

    def h2(text):
        return para(T_H2, text)

    # ---- cover ------------------------------------------------------------------------------------------------
    set_text(K[0], "Final Report on Disaster Mapping and Relief Management System")
    set_text(K[5], "Course Title : Database Systems Project/Fieldwork")
    set_text(K[19], "Group ID : 3")
    for r in K[29].findall(tag("r")):
        if (r.findtext(tag("t")) or "").startswith("Submite"):
            r.find(tag("t")).text = "Submission Date :"
    cover = X('<w:tbl><w:tblPr><w:tblW w:w="9638" w:type="dxa"/><w:jc w:val="center"/><w:tblLayout w:type="fixed"/>'
              '<w:tblLook w:val="0000"/></w:tblPr><w:tblGrid><w:gridCol w:w="4819"/><w:gridCol w:w="4819"/></w:tblGrid>'
              '<w:tr><w:tc><w:tcPr><w:tcW w:w="4819" w:type="dxa"/></w:tcPr></w:tc><w:tc><w:tcPr><w:tcW w:w="4819" w:type="dxa"/></w:tcPr></w:tc></w:tr></w:tbl>')
    for tc, m in zip(cover.iter(tag("tc")), (U, F)):
        sizes = [(m["name"], 28, True), (f"Student ID : {m['student_id']}", 26, False), (m["role"], 22, False),
                 (f"Contribution : {m['contribution_percent']}%", 24, False)]
        for t, sz, b in sizes:
            tc.append(X(f'<w:p><w:pPr><w:spacing w:after="60"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr>{"<w:b/>" if b else ""}'
                        f'<w:sz w:val="{sz}"/><w:szCs w:val="{sz}"/></w:rPr><w:t>{esc(t)}</w:t></w:r></w:p>'))
    K[22].addprevious(cover)
    body.remove(K[22]); body.remove(K[23])

    # ---- preface: team table and video parts ----------------------------------------------------------------
    pm = json.load(open(WORK / "placement.json"))["map"]
    seg = json.load(open(WORK / "segments.json"))

    def mmss(t):
        t = int(round(t))
        return f"{t // 60}:{t % 60:02d}"

    runs = []
    spoken = {"F": 0.0, "U": 0.0}
    for (stem, s), (k, (a, b)) in zip(seg.items(), pm.items()):
        spoken[stem[-1]] += s["dur"]
        if runs and runs[-1][0] == stem[-1]:
            runs[-1][2] = b
        else:
            runs.append([stem[-1], a, b])
    labels = {("U", 0): "technology stack", ("F", 0): "introduction, problem statement and scope", ("F", 1): "ERD, cardinality, normalization and the documented exceptions",
              ("U", 1): "keys, cascade rules, triggers, indexes, then the live CRUD demo with phpMyAdmin", ("F", 2): "reports in the UI: Live Report, path map, disaster report",
              ("U", 2): "the technical hurdle and how we fixed it", ("F", 3): "future scope and closing"}
    seen = {"F": 0, "U": 0}
    parts = {"F": [], "U": []}
    for sp, a, b in runs:
        parts[sp].append(f"{mmss(a)} to {mmss(b)}: {labels[(sp, seen[sp])]}")
        seen[sp] += 1
    length = list(pm.values())[-1][1]
    team_tbl = table([1500, 4069, 4069], ["", f"{U['name']} ({U['student_id']})", f"{F['name']} ({F['student_id']})"], [
        ["Role", U["role"], F["role"]],
        ["Share of the work", f"{U['contribution_percent']}%", f"{F['contribution_percent']}%"],
        ["Contributions", ["- " + c for c in U["contributions"]], ["- " + c for c in F["contributions"]]],
        ["Parts in the video", parts["U"], parts["F"]],
        ["Speaking time", mmss(spoken["U"]), mmss(spoken["F"])]], sz=18, first_col_bold=True)
    intro = body_p(f"The ER design and the sample data scenario were worked out together. The table below shows who did what. Each of us presented our own "
                   f"parts in the video, which runs {mmss(length)} in total; the speaking time is almost equal.")
    pref = h1("Team members and contributions")
    K[30].addprevious(pref)
    after(pref, intro, team_tbl, X("<w:p><w:pPr><w:spacing w:after=\"60\"/></w:pPr></w:p>"))
    set_text(K[30], "YouTube presentation")

    # ---- abstract, chapter 1 and 2 text -------------------------------------------------------------------------
    rich(K[34], "This report describes our database project, the Disaster Mapping and Relief Management System (DRMS). The system stores disasters, the areas "
                "they hit, the affected families, the shelters and the relief goods that are given out. It is a PHP web application on a MySQL database, and "
                "we ran it on WampServer.")
    rich(K[35], "The database has 25 tables, normalized to third normal form. The report gives the ER diagram and the relational schema, explains our design "
                "choices, shows queries and constraint tests run on the sample data, and describes the problems we hit and what we would add next. The video "
                "of our presentation is linked in the preface.")
    rich(K[40], "Bangladesh, and especially coastal districts such as Khulna and Satkhira, faces cyclones, floods, tidal surges and river erosion almost every "
                "year. When one strikes, a lot of information arrives at once: the warning signals, the direction of the storm, which houses are damaged, which "
                "families need a place to stay, how full each shelter is, and what relief is available.")
    rich(K[41], "On paper registers or separate spreadsheets, simple questions take too long to answer. Which area is in danger now? Which shelter still has "
                "space? Has this family already received relief? How much drinking water is left, and has it expired? Without clear records, shelters overfill, "
                "some families receive relief twice while others get none, and nobody can trace the stock.")
    rich(K[42], "We keep everything in one relational database. The database itself checks the important rules, and a web interface shows the current situation "
                "in a live report and on a map.")
    rich(K[50], "The system follows an incident from the first report until it is closed. It records the affected areas and families, shelter check-in and "
                "check-out, field updates and tasks, relief stock in batches, and every distribution of relief to shelters and families. The weather page shows "
                "data from an outside weather service, which is not stored in our database. All sample data in the project is fictional.")
    rich(K[55], "The system has three layers. The browser shows the pages and sends requests. PHP scripts check the business rules and talk to MySQL through "
                "prepared mysqli statements. MySQL stores the data and enforces the integrity rules.")
    for tr in K[53].findall(tag("tr")):
        if cell_texts(tr)[0] == "Database tools":
            set_cell(tr.findall(tag("tc"))[1], "phpMyAdmin (shown beside the UI during the demo); database/schema.sql and database/sample_data.sql")

    # ---- chapter 3: screenshots --------------------------------------------------------------------------------
    rich(K[67], "The figures below show the main pages of the running system with the sample data from database/sample_data.sql.")
    figs = [(68, 69, "signin", "Figure 3.1: Sign-in page"),
            (70, 71, "dashboard", "Figure 3.2: Dashboard with live status cards and table index"),
            (73, 74, "livereport", "Figure 3.3: Live Report with the warning signals and the disaster path"),
            (75, 76, "map", "Figure 3.4: Map of areas and shelters"),
            (78, 79, "table", "Figure 3.5: A table page (Shelter) with search, sort and CSV export"),
            (80, 81, "form_error", "Figure 3.6: Add record form refusing a family size of -2 (CHECK constraint)"),
            (83, 84, "disaster", "Figure 3.7: Disaster Report for Cyclone #1"),
            (85, 86, "pma_beside_ui", "Figure 3.8: phpMyAdmin (left) and the application (right) after the capacity of shelter 5 was changed from 11 to 20")]
    media = {}
    for n, (ti, ci, name, cap) in enumerate(figs):
        f = SHOTS / f"shot_{name}.png"
        w_, h_ = png_size(f)
        cx = 5940000 if name == "pma_beside_ui" else 5400000
        rid = f"rId{101 + n}"
        media[f"word/media/image{11 + n}.png"] = f.read_bytes()
        etree.SubElement(rels, "{http://schemas.openxmlformats.org/package/2006/relationships}Relationship", Id=rid,
                         Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image", Target=f"media/image{11 + n}.png")
        pic = picture(T_PIC, rid, cx, round(cx * h_ / w_), 101 + n, f"shot_{name}", cap.split(": ", 1)[1])
        K[ti].addprevious(pic)
        body.remove(K[ti])
        set_text(K[ci], cap)

    # ---- chapter 4: ERD on a landscape page --------------------------------------------------------------------
    portrait = copy.deepcopy(SECT)
    land = copy.deepcopy(SECT)
    sz = land.find(tag("pgSz"))
    sz.set(tag("w"), "16838"); sz.set(tag("h"), "11906"); sz.set(tag("orient"), "landscape")
    K[87].addnext(X("<w:p><w:pPr></w:pPr></w:p>"))
    brk = K[87].getnext()
    brk.find(tag("pPr")).append(portrait)
    lp = X("<w:p><w:pPr></w:pPr></w:p>")
    lp.find(tag("pPr")).append(land)
    K[91].addprevious(lp)
    body.remove(K[91])
    for e in K[89].iter("{%s}extent" % NS["wp"], "{%s}ext" % NS["a"]):
        e.set("cx", "7380000"); e.set("cy", "5207000")
    set_text(K[92], "4.1 Notation and entities")
    K[92].find(tag("pPr")).find(tag("pStyle")).set(tag("val"), "Heading2")

    # ---- chapter 5: sample rows, Batch_ID ---------------------------------------------------------------------
    counts = dict(area=4, disaster=3, shelter=5, affected_person=5, shelter_assignment=5, damage=5, early_warning=5, disaster_track=5,
                  incident_update=4, incident_task=4, rescue_org=3, relief_item=4, relief_batch=4, relief_distribution=4, emergency_item=4,
                  disaster_area=5, disaster_shelter=5, disaster_affected_person=5, disaster_rescue_org=4, relief_recipient=4,
                  rescue_org_relief_item=5, shelter_emergency_item=4, affected_person_emergency_item=4, users=3, audit_log=4)
    for tr in K[109].findall(tag("tr"))[1:]:
        tcs = tr.findall(tag("tc"))
        set_cell(tcs[-1], str(counts[cell_texts(tr)[1]]))
    rich(K[110], "The sample rows are the records in `database/sample_data.sql`, which is loaded after `schema.sql`. The table `users` holds the three demo "
                 "accounts and `audit_log` the four log entries that belong to the sample data.")
    rich(K[112], "The tables below come from the DDL in `database/schema.sql`. PK means primary key and FK means foreign key.", 22)
    for tr in K[98].findall(tag("tr")):
        t = cell_texts(tr)
        if t[0] == "draws_from":
            set_cell(tr.findall(tag("tc"))[4], "Mandatory")
    # relief_distribution definition: Batch_ID is NOT NULL
    hit = False
    for i in range(113, 222):
        ppr = K[i].find(tag("pPr")) if K[i].tag == tag("p") else None
        if ppr is not None and ppr.find(tag("pStyle")) is not None and "relief_distribution" in "".join(K[i].itertext()):
            tbl = K[i + 1]
            for tr in tbl.findall(tag("tr")):
                t = cell_texts(tr)
                if t[0] == "Batch_ID":
                    set_cell(tr.findall(tag("tc"))[2], "NO"); hit = True
    assert hit, "relief_distribution table not found"

    # ---- chapter 6 ---------------------------------------------------------------------------------------------
    rich(K[234], "Besides the keys, many rules are written in the database itself, so wrong data is rejected even when someone types it directly in phpMyAdmin.")
    rich(K[242], "Some rules involve a third table, which a foreign key or a CHECK constraint cannot express. For these we wrote eight BEFORE INSERT / UPDATE "
                 "triggers. A trigger stops the statement with an error (SQLSTATE 45000) when the rule is broken.")
    rich(K[257], "We kept two controlled exceptions to strict 3NF and documented both. `relief_distribution.Relief_ID` is already decided by the batch "
                 "(`Batch_ID` determines `Relief_ID`), so it is a transitive dependency. We kept it because batches were added after distributions, and the "
                 "column keeps old reports and the supplier rule simple; the application refuses a batch of another item. `relief_item.Quantity` is the stock "
                 "total and equals the sum of the batch quantities. The forms cannot edit it, it is written together with a batch receipt in one transaction, "
                 "and an inventory discrepancy alert fires if the numbers ever differ.")
    K[257].addnext(body_p("Values such as the free places of a shelter are never stored. They are always calculated from the capacity and the people checked in. "
                          "Before a new distribution is saved, the application compares the item stock and the batch quantity, so the stock cannot be exceeded."))

    # ---- chapter 7: rebuilt from the sample data ------------------------------------------------------------------
    H7 = {260: "7.1 CRUD operations", 275: "7.2 Multi-table queries and reports", 341: "7.3 Constraint tests"}
    T_LABEL = keep_next(copy.deepcopy(K[261]))
    anchor = K[259].getprevious()
    for i in range(259, 361):
        body.remove(K[i])
    new = []

    def label(text):
        new.append(para(T_LABEL, text))

    def code(sql: str):
        lines = sql.strip("\n").split("\n")
        for n, ln in enumerate(lines):
            new.append(code_para(T_CODE, ln, n == len(lines) - 1))

    def result(text):
        new.append(body_p(text))

    def rtable(header, rows, sz=18):
        n = len(header)
        wts = [max(8, len(h) + 2, *(len(str(r[i])) + 2 for r in rows)) for i, h in enumerate(header)]
        widths = [round(9638 * x / sum(wts)) for x in wts]
        widths[-1] += 9638 - sum(widths)
        new.append(table(widths, header, rows, sz=sz if n <= 6 else 16))
        new.append(X('<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>'))

    new.append(body_p("This chapter lists the database operations we ran on the sample data from `database/sample_data.sql`: the four CRUD operations, seven "
                      "queries over several tables, constraint tests, and the steps of the live demo in the video. All output was copied from MySQL 8.2.0 after "
                      "loading `schema.sql` and `sample_data.sql` into an empty database."))
    new.append(para(T_H2, H7[260]))
    label("Create: adding a new area (the Add record form of the application runs an INSERT like this):")
    code("INSERT INTO area (Area_Name, Location, Risk_Level, Latitude, Longitude)\nVALUES ('Demo Area', 'Khulna, Bangladesh', 'Medium', 22.700000, 89.500000);")
    result("Result: 1 row affected. Area_ID is generated automatically; it is 5, because the sample data has 4 areas.")
    label("Read: the disasters, newest first (the sample data has three):")
    code("SELECT Disaster_ID, Disaster_Type, Disaster_Date, Status FROM disaster ORDER BY Disaster_Date DESC LIMIT 5;")
    rtable(["Disaster_ID", "Disaster_Type", "Disaster_Date", "Status"], [["1", "Cyclone", "2026-10-24", "Active"], ["2", "Flood", "2026-10-02", "Under control"],
                                                                          ["3", "Cyclone", "2026-09-18", "Closed"]])
    label("Update: raising the risk level of the new area:")
    code("UPDATE area SET Risk_Level = 'High' WHERE Area_Name = 'Demo Area';")
    result("Result: 1 row affected.")
    label("Delete: removing the test area (allowed, because no shelter or family refers to it):")
    code("DELETE FROM area WHERE Area_Name = 'Demo Area';")
    result("Result: 1 row affected.")

    new.append(para(T_H2, H7[275]))
    log = (ROOT / "submission" / "report" / "Query_Log.md").read_text(encoding="utf-8")
    for blk in re.split(r"\n## ", log)[1:]:
        m = re.match(r"Q(\d+)\. (.*)\n\nTables: (.*)\n\n```sql\n(.*?)\n```\n\n(\|.*?\n)\n(\d+) row", blk, re.S)
        n, title, tables_, sql, md = m.group(1), m.group(2), m.group(3), m.group(4), m.group(5)
        rows = [[c.strip() for c in ln.strip().strip("|").split("|")] for ln in md.strip().split("\n")]
        label(f"Query {n}: {title}. Tables: {tables_}.")
        code(sql)
        rtable(rows[0], rows[2:])

    new.append(para(T_H2, H7[341]))
    new.append(body_p("Each statement below is rejected by the database. The messages are the ones MySQL 8 gives, and the key names in them come from our schema."))
    tests = [
        ("Test 1, CHECK constraint (the capacity must be positive):",
         "INSERT INTO shelter (Shelter_Name, Location, Capacity, Area_ID)\nVALUES ('Test Shelter', 'Khulna', 0, 1);",
         "ERROR 3819: Check constraint 'chk_shelter_capacity' is violated."),
        ("Test 2, FOREIGN KEY on insert (the disaster does not exist):",
         "INSERT INTO early_warning (Disaster_ID, Alert_Type, Signal_Level, Alert_DateTime)\nVALUES (999, 'Test', 5, '2026-11-13 10:00:00');",
         "ERROR 1452: Cannot add or update a child row: a foreign key constraint fails (... fk_early_warning_disaster ...)."),
        ("Test 3, UNIQUE key (duplicate organisation name):",
         "INSERT INTO rescue_org (Org_Name) VALUES ('Coastal Volunteers Network');",
         "ERROR 1062: Duplicate entry 'Coastal Volunteers Network' for key 'rescue_org.uq_rescue_org_name'."),
        ("Test 4, RESTRICT (the shelter already has assignments):",
         "DELETE FROM shelter WHERE Shelter_ID = 1;",
         "ERROR 1451: Cannot delete or update a parent row: a foreign key constraint fails (... fk_assignment_shelter ...)."),
        ("Test 5, trigger (area 4 is not an affected area of disaster 1):",
         "INSERT INTO damage (Disaster_ID, Damage_Type, Area_ID, Quantity, Estimated_Loss)\nVALUES (1, 'Test', 4, 1, 1000);",
         "ERROR 1644 (45000): That area is not affected by this disaster. Add it in \"Disaster Area\" first."),
        ("Test 6, trigger (shelter 1 is not opened for disaster 2):",
         "INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In)\nVALUES (2, 5, 1, '2026-10-05');",
         "ERROR 1644 (45000): That shelter is not used for this disaster. Add it in \"Disaster Shelter\" first."),
        ("Test 7, functional unique index (family 1 already has an open stay):",
         "INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In)\nVALUES (1, 1, 1, '2026-10-30');",
         "ERROR 1062: Duplicate entry '1' for key 'shelter_assignment.uq_assignment_open_stay'."),
    ]
    for lab, sql, res in tests:
        label(lab)
        code(sql)
        result("Result: " + res)

    new.append(para(T_H2, "7.4 Operations shown in the video"))
    new.append(body_p("The live part of the video repeats these steps with phpMyAdmin beside the application. The times are positions in the video. The data is "
                      "the sample data, and every step starts from the same freshly loaded database."))
    t = lambda k: mmss(pm[k][0])
    ops = [
        (t("p3_create"), "Affected Person, Add record: Shirin Akter, family size 4, area Dacope.", "New row 6 in `affected_person`; `audit_log` has a new row with the user and the values."),
        (t("p3_link"), "Disaster Affected Person, Add: disaster 1, person 6.", "New row in the junction table."),
        (t("p3_capacity"), "Shelter Assignment: person 6 in the Dacope school shelter is refused (11 staying + 4 new > 11 places).", "SQL shows 11 people staying there."),
        (t("p3_assign"), "Same form with Koyra Government Shelter is saved.", "New open stay in `shelter_assignment`."),
        (t("p3_read"), "Shelter Assignment list sorted by Check In and searched for 2026-10-24.", "3 matching records."),
        (t("p3_update"), "Shelter, Edit: capacity of the Dacope school from 11 to 20.", "`shelter` shows 20; `audit_log` keeps the old and new value."),
        (t("p3_check"), "Add family with size -2 is refused.", "`UPDATE shelter SET Capacity = 0` fails with `chk_shelter_capacity`."),
        (t("p3_trigger"), "Stay for the flood in the Koyra shelter is rejected.", "Trigger message: That shelter is not used for this disaster."),
        (t("p3_restrict"), "Signed in as admin, delete the Dacope school shelter: blocked by 2 stays and 2 distributions (RESTRICT).", "Nothing is deleted."),
        (t("p3_cascade"), "Delete the closed Cyclone #3; the page lists 4 dependent rows first (CASCADE).", "Warnings of Cyclone #3 go from 1 to 0."),
    ]
    new.append(table([900, 4400, 4338], ["Time", "Operation in the application", "What phpMyAdmin shows"], [[a, b, c.replace("`", "")] for a, b, c in ops], sz=18))
    after(anchor, *new)

    # ---- chapter 8 and appendices -----------------------------------------------------------------------------------
    set_text(K[361], "8. Challenges, resolutions and future scope")
    set_text(K[362], "8.1 Challenges and how we solved them")
    rich(K[363], "Problem 1: one open stay per family. At first `shelter_assignment` had a unique key on `Disaster_ID` and `Person_ID`. It looked right, but a "
                 "family that left one shelter could never be placed in another during the same cyclone, which is exactly what happens when an embankment "
                 "breaks. The real rule is that a family has only one open stay at a time. We replaced the key with a functional unique index, "
                 "`uq_assignment_open_stay`, that covers `Person_ID` only while `Check_Out` is empty, and wrote an upgrade that changes existing databases "
                 "without losing data. Test 7 in chapter 7 shows MySQL enforcing it.")
    rich(K[364], "Problem 2: two users saving at the same time. Two operators could put two families into the last free places of one shelter, or give out more "
                 "of an item than the stock, because both checks pass before either row is saved. We run each add or edit inside a transaction that first "
                 "locks the person, shelter and item rows with `SELECT ... FOR UPDATE`, always in the same order (person first, then shelter or item). The "
                 "second user waits and then sees the updated numbers, and the fixed order avoids deadlock. A test with two simultaneous users proves it: one "
                 "is saved and the other is refused with the capacity message.")
    rich(K[365], "Problem 3: rules that a foreign key cannot check. A shelter should receive people and relief only for a disaster for which it was opened, and "
                 "a damage record should refer only to an area that was hit by that disaster. A foreign key only checks that the referred row exists, not that "
                 "the two rows belong together. We used the junction tables (`disaster_shelter`, `disaster_area`, `disaster_affected_person`, "
                 "`disaster_rescue_org`) as the list of allowed combinations and wrote eight triggers that check against them. The same rules are also in "
                 "`inc/rules.php`, so the user gets a clear message instead of a database error.")
    rich(K[366], "Problem 4: tracing the relief stock. One quantity per item does not tell where the goods came from or when they expire. We added the table "
                 "`relief_batch`, where every lot has a batch code, a source, a received date and an expiry date. Each distribution now refers to a batch, so "
                 "an expired or over-issued batch cannot be dispatched.")
    K[366].addnext(body_p("Problem 5: a readable ER diagram. With 25 tables the diagram became crowded when every foreign key was drawn as an attribute. We used Chen "
                          "notation, where relationships are diamonds and foreign keys are not shown as attributes, and drew the junction tables as named "
                          "diamonds. The two system tables are left out of the diagram and explained in the schema chapter."))
    testing = h2("8.2 Testing")
    testing_p = body_p("Two scripts in the source code check the work. `tests/smoke.php` runs 115 checks on the schema, the business rules, the triggers, the "
                       "reports and the security helpers. `tests/concurrency.php` lets two users save into the last places of a shelter at the same time. Both "
                       "pass on a fresh install. They need the larger data set that the application installs into an empty database, not the short "
                       "`sample_data.sql`, so they run on a disposable database as described in the source README.")
    K[367].addprevious(testing)
    testing.addnext(testing_p)
    set_text(K[367], "8.3 Future scope")
    future = ["Sending SMS or push alerts to families and volunteers when a warning signal reaches 8 or changes.",
              "Importing official cyclone tracks and river-level data to create warnings and track points automatically.",
              "Matching the needs of each family with the relief that is available.",
              "A small mobile app that lets field volunteers send updates from the shelters, even with a poor connection.",
              "Storing flood-zone polygons with spatial data types for a more exact map of the areas.",
              "Regular database backups, replication and a limited-privilege database user for real use.",
              "Charts that compare relief coverage and response time of past disasters."]
    for b in K[368:374]:
        body.remove(b)
    after(K[367], *[bullet(x) for x in future])
    set_text(K[374], "8.4 Conclusion")
    rich(K[375], "We built 25 tables normalized to 3NF, and their keys, cascading rules, constraints, indexes and triggers keep the data consistent. The web "
                 "application on top gives full CRUD operations, a live report and a map, so the state of a disaster can be followed from one page.")
    set_text(K[376], "Appendix A: How to run the project")
    rich(K[377], "Requirements: Windows with WampServer, PHP 8.1 or newer (mysqli and mbstring) and MySQL 8.0.16 or newer. The steps are the same as in the README of the source code.")
    steps = ["Start WampServer and wait until the icon turns green.",
             "In phpMyAdmin (or with the MySQL client) import `database/schema.sql` and then `database/sample_data.sql`. This creates the database "
             "`disaster_and_relief_management` with 25 tables, 8 triggers and 3 to 5 sample rows per table.",
             "Copy the `source_code` folder to `C:\\wamp64\\www\\disaster_and_relief_management`. Copy `config.sample.php` to `config.php`. The settings come from the "
             "variables DRMS_DB_HOST, DRMS_DB_PORT, DRMS_DB_USER, DRMS_DB_PASS and DRMS_DB_NAME (defaults: 127.0.0.1, 3306, root, empty password, "
             "disaster_and_relief_management).",
             "Open http://localhost/disaster_and_relief_management/ and sign in with demo_admin / DemoAdmin2026 (admin), demo_editor / DemoEditor2026 "
             "(editor) or demo_viewer / DemoViewer2026 (viewer).",
             "Without Apache, run `php -S 127.0.0.1:8099 router.php` inside `source_code` and open http://127.0.0.1:8099/.",
             "Open phpMyAdmin (http://localhost/phpmyadmin) to watch the database change while you use the application."]
    for b in K[378:384]:
        body.remove(b)
    after(K[377], *[bullet(x) for x in steps])
    set_text(K[384], "Appendix B: Submission checklist")
    for tr in K[385].findall(tag("tr")):
        t_ = cell_texts(tr)
        if t_[0] == "YouTube URL":
            set_cell(tr.findall(tag("tc"))[1], "Link and QR code on the preface page; the link is also entered in the course portal")

    # ---- headings in sentence case --------------------------------------------------------------------------------
    names = {"Table of Contents": "Table of contents", "1.1 Background and Problem Statement": "1.1 Background and problem statement",
             "2. System Architecture Overview": "2. System architecture overview", "2.1 Technology Stack": "2.1 Technology stack",
             "2.3 Functional Modules": "2.3 Functional modules", "2.4 Roles and Security": "2.4 Roles and security",
             "3. User Interface Screenshots": "3. User interface screenshots", "4. Entity-Relationship Diagram (ERD)": "4. Entity-relationship diagram (ERD)",
             "4.2 One-to-Many Relationships": "4.2 One-to-many relationships", "4.3 Many-to-Many Relationships": "4.3 Many-to-many relationships",
             "5. Relational Schema": "5. Relational schema", "5.1 Mapping from ER Model to Relations": "5.1 Mapping from the ER model to relations",
             "5.2 Overview of the 25 Tables": "5.2 Overview of the 25 tables", "5.3 Table Definitions": "5.3 Table definitions",
             "6. Database Design Decisions": "6. Database design decisions", "6.1 Primary and Foreign Keys": "6.1 Primary and foreign keys",
             "6.2 Cardinality and Cascading Rules": "6.2 Cardinality and cascading rules", "6.4 Integrity Triggers": "6.4 Integrity triggers",
             "6.6 Business Rules in the Application": "6.6 Business rules in the application", "7. Query and Operation Logs": "7. Query and operation logs"}
    for p in body.iter(tag("p")):
        st = p.find(tag("pPr")).find(tag("pStyle")) if p.find(tag("pPr")) is not None else None
        if st is not None and st.get(tag("val")) in ("Heading1", "Heading2"):
            txt = "".join(p.itertext())
            if txt in names:
                set_text(p, names[txt])

    # ---- write ---------------------------------------------------------------------------------------------------
    OUT.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as zout:
        for item in zin.infolist():
            data = zin.read(item.filename)
            if item.filename == "word/document.xml":
                data = etree.tostring(doc, xml_declaration=True, encoding="UTF-8", standalone=True)
            elif item.filename == "word/_rels/document.xml.rels":
                data = etree.tostring(rels, xml_declaration=True, encoding="UTF-8", standalone=True)
            elif item.filename == "word/settings.xml":
                data = etree.tostring(settings, xml_declaration=True, encoding="UTF-8", standalone=True)
            zout.writestr(item, data)
        for name, data in media.items():
            zout.writestr(name, data)
    print("written", OUT)


if __name__ == "__main__":
    main()
