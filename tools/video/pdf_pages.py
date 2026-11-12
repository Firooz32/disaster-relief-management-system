"""Renders pages of a PDF to PNG with pdf.js in headless Chromium (no poppler needed).
    py -3 tools/video/pdf_pages.py <file.pdf> <out prefix> [first last]"""
import asyncio, base64, sys
from pathlib import Path
from playwright.async_api import async_playwright

pdf, prefix = Path(sys.argv[1]), sys.argv[2]
first, last = (int(sys.argv[3]), int(sys.argv[4])) if len(sys.argv) > 4 else (1, 3)
HTML = """<html><body><script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script></body></html>"""

async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); pg = await b.new_page(viewport={"width": 900, "height": 1200})
        await pg.set_content(HTML); await pg.wait_for_function("window.pdfjsLib")
        data = base64.b64encode(pdf.read_bytes()).decode()
        n = await pg.evaluate("""async d => { pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            window.doc = await pdfjsLib.getDocument({data: atob(d).split('').map(c => c.charCodeAt(0)) && Uint8Array.from(atob(d), c => c.charCodeAt(0))}).promise; return window.doc.numPages }""", data)
        print("pages", n)
        for i in range(first, min(last, n) + 1):
            png = await pg.evaluate("""async i => { const page = await window.doc.getPage(i), vp = page.getViewport({scale: 1.1}), c = document.createElement('canvas');
                c.width = vp.width; c.height = vp.height; await page.render({canvasContext: c.getContext('2d'), viewport: vp}).promise; return c.toDataURL('image/png').split(',')[1] }""", i)
            Path(f"{prefix}_{i}.png").write_bytes(base64.b64decode(png))
        await b.close()
asyncio.run(main())
