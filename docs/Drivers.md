Here is a list of available drivers with description in selection priority.

| Driver       | Memory-effective streaming            | appending | updating | encrypt | creation | type | formats   |
|--------------|-------------------------------------- |-----------|----------|---------|----------|-----------|------|
| Zip          | ✔                                     | ✔         | ✔        | ✔    | ✔          | extension | zip |
| Rar          | ✔                                     |           |          | ✔    |           | extension   | rar |
| TarByPhar    |                                       | ✔         | ✔        |     | ✔           | extensions | tar, tar-gz, tar-bz2 |
| TarByPear    |                                       |           |          |     | ✔          | library + extensions | tar, tar-gz, tar-bz2, tar-Z, tar-xz |
| SevenZip     |                                       | ✔         | ✔        | ✔    | ✔          | library + program | zip, rar, tar, iso, uefi, ... |
| AlchemyZippy |                                       | ✔         | ✔        |     | ✔          | library + programs | zip, tar, tar-gz, tar-bz2 |
| Gzip         | ✔                                     |           |          |     | ✔            | extension | gz |
| Bzip2        | ✔                                     |           |          |     | ✔            | extension | bz2 |
| Lzma2        | ✔                                     |           |          |     | ✔            | extension | xz |
| Iso          |                                       |           |          |     |            | library | iso |
| Cab          |                                       |           |          |     |            | library | cab |

Example for `zip` with two drivers support: If on your system both `Zip` and `AlchemyZippy` driver available, the first will be used for `zip` file.

# Proposals for installation drivers
## In docker/VDS case
Install extensions (`zip, rar, phar, zlib, bzip2`), program (`p7zip`) and SevenZip driver.

## In common hosting case
Install TarByPear, AlchemyZippy libraries.

# Details about drivers
## Zip
- Type: php-extension (`zip`)
- Formats: zip
- All functions available
- Can open archives with passwords.

## Rar
- Type: php-extension (`rar`)
- Formats: rar
- The read-only mode (not available: creation, appending, updating).
- Can open archives with passwords.

## TarByPhar
- Type: php-extension (`phar`) and optionally php-extensions (`zlib`, `bzip2`)
- Formats: tar, tar.gz, tar.bz2
- Has a bug [#71966](https://bugs.php.net/bug.php?id=71966&thanks=10) in `ext-phar` ([related issue](https://github.com/wapmorgan/UnifiedArchive/issues/12)) - an archive will `./` in paths cannot be opened.

## TarByPear
- Type: library ([`pear/archive_tar`](https://packagist.org/packages/pear/archive_tar)) and optionally php-extensions (`zlib`, `bzip2`)
- Formats: tar, tar.gz, tar.bz2, tar.xz, tar.Z
- Works with binary data in php-land (= eats a lot of memory for big archives).
- Not available: updating.

## SevenZip
- Type: library ([`gemorroj/archive7z`](https://packagist.org/packages/gemorroj/archive7z)) and console program p7zip ([`7z`](http://p7zip.sourceforge.net/))
- Formats: 7z, XZ, BZIP2, GZIP, TAR, ZIP, WIM, AR, ARJ, CAB, CHM, CPIO, CramFS, DMG, EXT, FAT, GPT, HFS, IHEX, ISO, LZH, LZMA, MBR, MSI, NSIS, NTFS, QCOW2, RAR, RPM, SquashFS, UDF, UEFI, VDI, VHD, VMDK, WIM, XAR and Z
- Works via command line (= should be available `proc_*` functions).
- Can open archives with passwords.

## AlchemyZippy
- Type: library ([`alchemy/zippy`](https://packagist.org/packages/alchemy/zippy)) and console programs:
    - `tar`
    - `zip`
- Formats: zip, tar, tar.bz2, tar.gz
- Works via command line (= should be available `proc_*` functions).
- All functions

## Cab
- Type: library ([`wapmorgan/cab-archive`](https://packagist.org/packages/wapmorgan/cab-archive))
- Formats: cab
- Works with binary data in php-land (= eats a lot of memory for big archives).
- Getting files content and extraction is supported only on PHP 7.0.22+, 7.1.8+, 7.2.0.
- The read-only mode (not available: creation, appending, updating).

## Iso
- Type: library ([`phpclasses/php-iso-file`](https://packagist.org/packages/phpclasses/php-iso-file))
- Formats: iso
- Works with binary data in php-land (= eats a lot of memory for big archives).
- The read-only mode (not available: creation, appending, updating).

## One-file formats
These drivers support only compressed (not archived) data.

### Bzip2
- Type: php-extension (`bzip2`)
- Formats: bz2

### Gzip
- Type: php-extension (`zlib`)
- Formats: gz

### Lzma2
- Type: php-extension ([`xz`](https://github.com/payden/php-xz))
- Formats: xz