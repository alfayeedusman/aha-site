import csv, gzip, ipaddress, sqlite3, sys, os
src, out = sys.argv[1], sys.argv[2]
def b16(ip):
    a = ipaddress.ip_address(ip)
    return (b'\0'*10 + b'\xff\xff' + a.packed) if a.version == 4 else a.packed
rows = []
with gzip.open(src, 'rt') as f:
    for s, e, cc in csv.reader(f):
        if cc == 'ZZ': continue
        rows.append([b16(s), b16(e), cc])
rows.sort()
merged = []
for r in rows:
    if merged and merged[-1][2] == r[2] and int.from_bytes(merged[-1][1], 'big') + 1 == int.from_bytes(r[0], 'big'):
        merged[-1][1] = r[1]
    else:
        merged.append(r)
if os.path.exists(out): os.remove(out)
db = sqlite3.connect(out)
db.execute('CREATE TABLE r (s BLOB PRIMARY KEY, e BLOB NOT NULL, cc TEXT NOT NULL) WITHOUT ROWID')
db.executemany('INSERT INTO r VALUES (?,?,?)', merged)
db.commit(); db.execute('VACUUM'); db.close()
print(len(rows), '->', len(merged))
