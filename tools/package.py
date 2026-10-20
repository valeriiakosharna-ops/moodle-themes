"""Build catalogued variants with deterministic ZIP metadata and a Moodle plugin root."""
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_STORED
import hashlib
import json
import re

root = Path(__file__).resolve().parents[1]
catalog = json.loads((root / "catalog.json").read_text())
checksums = []
for variant in catalog["variants"]:
    theme = (root / variant["source"]).resolve()
    destination = (root / variant["archive"]).resolve()
    theme.relative_to(root / "themes")
    destination.relative_to(root / "packages")
    assert theme.name == variant["theme"], "Plugin directory must match its component"
    version = (theme / "version.php").read_text()
    assert re.search(r"\$plugin->version\s*=\s*" + str(variant["version"]) + r"\s*;", version), "Catalog version mismatch"
    assert "'theme_" + variant["theme"] + "'" in version, "Catalog component mismatch"
    destination.parent.mkdir(parents=True, exist_ok=True)
    with ZipFile(destination, "w", ZIP_STORED) as archive:
        for path in sorted(theme.rglob("*")):
            if path.is_file():
                assert not path.is_symlink(), "Do not package symlinks"
                name = str(Path(variant["theme"]) / path.relative_to(theme))
                info = ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
                info.create_system = 3
                info.external_attr = 0o100644 << 16
                archive.writestr(info, path.read_bytes())
    digest = hashlib.sha256(destination.read_bytes()).hexdigest()
    checksums.append(f"{digest}  {destination.relative_to(root / 'packages')}")
    print(destination.relative_to(root))
(root / "packages/SHA256SUMS").write_text("\n".join(checksums) + "\n")
