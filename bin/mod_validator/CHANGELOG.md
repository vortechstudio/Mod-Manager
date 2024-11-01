# Transport Fever 2 - Mod Validator - Public - Changelog

## Version 0.10.0 (2024-10-17)

### Changes:
- **HTML summary generation:** Added generation of summary HTML page.
- **Improved warning and failure descriptions:** Added warnings and failures descriptions for better user guidance.
- **DDS mipmap fixer:** Added option --fix-mipmaps to generate required mipmaps in DDS files. This is an experimental feature. **Warning:** This option does overwrite existing files.
- **Mod structure whitelist:** Added `modio_preview.png` to the whitelist.

## Version 0.9.0 (2024-09-27) - Initial Public Release 

### Features:
- **Initial public release** of the TF2 Mod Validator tool.
- **Mod structure:** Checks mod structure and correct placement of files.
- **Mod severity:** Checks if mod severityRemove is set to proper value.
- **DDS textures:** Check for the correct texture format.
- **TGA textures:** Check for the correct texture format.
- **WAV files:** Check for the correct audio format.
- **Material files (MTL):** Checks if material files reference DDS textures.
