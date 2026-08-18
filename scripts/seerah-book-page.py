"""Extract CCITT-G4 page images from the scanned seerah PDFs, no poppler needed.
ponytail: wraps the raw G4 stream in a minimal TIFF header; works because every
page is exactly one full-page /CCITTFaxDecode XObject (K=-1). If a volume ever
ships a mixed-filter page, switch to pdftoppm."""
import re, struct, sys, io
from PIL import Image

def pages(pdf_path):
    d = open(pdf_path, 'rb').read()
    out = []
    for m in re.finditer(rb'(\d+)\s+0\s+obj\s*(<<.*?>>)\s*stream\r?\n', d, re.S):
        hdr = m.group(2)
        if b'CCITTFaxDecode' not in hdr:
            continue
        w = int(re.search(rb'/Width\s+(\d+)', hdr).group(1))
        h = int(re.search(rb'/Height\s+(\d+)', hdr).group(1))
        start = m.end()
        end = d.find(b'endstream', start)
        out.append((w, h, d[start:end].rstrip(b'\r\n')))
    return out

def to_tiff(w, h, data):
    # little-endian TIFF, one strip, Compression=4 (G4), PhotometricInterpretation=0 (WhiteIsZero)
    tags = [(256, 3, 1, w), (257, 3, 1, h), (258, 3, 1, 1), (259, 3, 1, 4),
            (262, 3, 1, 0), (273, 4, 1, 8), (277, 3, 1, 1), (278, 3, 1, h),
            (279, 4, 1, len(data))]
    n = len(tags)
    ifd_off = 8 + len(data)
    buf = io.BytesIO()
    buf.write(b'II*\x00' + struct.pack('<I', ifd_off))
    buf.write(data)
    buf.write(struct.pack('<H', n))
    for tag, typ, cnt, val in tags:
        buf.write(struct.pack('<HHII', tag, typ, cnt, val))
    buf.write(struct.pack('<I', 0))
    return buf.getvalue()

def render(pdf_path, first, last, outdir, scale=0.62):
    import os
    os.makedirs(outdir, exist_ok=True)
    ps = pages(pdf_path)
    for i in range(first - 1, min(last, len(ps))):
        w, h, data = ps[i]
        img = Image.open(io.BytesIO(to_tiff(w, h, data)))
        img = img.convert('L')
        if scale != 1:
            img = img.resize((int(w * scale), int(h * scale)), Image.LANCZOS)
        f = f"{outdir}/p{i+1:04d}.png"
        img.save(f, optimize=True)
        print(f, img.size)
    print("total pages in pdf:", len(ps))

if __name__ == '__main__':
    render(sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), sys.argv[4])
